<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Http\Traits\ApiResponse;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Notifications\CorrectionRequestedNotification;
use App\Notifications\CorrectionReviewedNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::CORRECTION_REQUEST)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $user   = $request->user();
        $page   = (int) $request->input('page', 1);
        $limit  = (int) $request->input('limit', 15);
        $status = $request->input('status');

        $query = AttendanceCorrection::query()->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        // Scope by role
        if (! $user->hasPermission(Permission::CORRECTION_REVIEW)) {
            // Regular user: own requests only
            $query->where('user_id', $user->id);
        } elseif (! $user->hasPermission(Permission::ATTENDANCE_VIEW_ALL)) {
            // Supervisor: own + direct reports
            $subordinateIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $query->whereIn('user_id', array_merge([$user->id], $subordinateIds));
        }
        // admin / hr_admin: see all (no extra filter)

        $total = $query->count();
        $rows  = $query->skip(($page - 1) * $limit)->take($limit)->get();

        // Batch-load related models to avoid N+1
        $logIds  = $rows->pluck('attendance_log_id')->unique()->toArray();
        $userIds = $rows->pluck('user_id')
            ->merge($rows->pluck('reviewed_by')->filter())
            ->unique()->toArray();

        $logs  = AttendanceLog::whereIn('id', $logIds)->get()->keyBy('id');
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $data = $rows->map(fn ($c) => $this->format(
            $c,
            $logs->get($c->attendance_log_id),
            $users->get($c->user_id)?->name ?? 'Unknown',
            $c->reviewed_by ? $users->get($c->reviewed_by)?->name : null,
        ));

        return $this->sendOk($data, [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function store(StoreAttendanceCorrectionRequest $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::CORRECTION_REQUEST)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $data = $request->validated();

        // Verify the log belongs to this user (unless reviewer)
        $log = AttendanceLog::find($data['attendance_log_id']);
        if (! $log) {
            return $this->sendError(404, 'NOT_FOUND', 'Attendance record not found');
        }
        if ($log->user_id !== $request->user()->id && ! $request->user()->hasPermission(Permission::CORRECTION_REVIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'You can only request corrections for your own records');
        }

        // Must supply at least one corrected time
        if (empty($data['corrected_check_in_at']) && empty($data['corrected_check_out_at'])) {
            return $this->sendError(422, 'VALIDATION_ERROR', 'Provide at least one corrected time');
        }

        // Block duplicate pending request for same log
        $exists = AttendanceCorrection::where('attendance_log_id', $log->id)
            ->where('status', 'pending')
            ->exists();
        if ($exists) {
            return $this->sendError(409, 'DUPLICATE', 'A pending correction already exists for this record');
        }

        $correction = AttendanceCorrection::create([
            'attendance_log_id'      => $log->id,
            'user_id'                => $request->user()->id,
            'corrected_check_in_at'  => $data['corrected_check_in_at'] ?? null,
            'corrected_check_out_at' => $data['corrected_check_out_at'] ?? null,
            'reason'                 => $data['reason'],
            'status'                 => 'pending',
        ]);

        // Notify reviewers: admins, hr_admins, and this user's supervisor
        $workDate = $this->workDateString($log);

        $reviewers = User::whereHas('roleModel', function ($q) {
            $q->whereIn('name', ['admin', 'hr_admin']);
        })->get();

        if ($log->user_id) {
            $submitter = User::find($log->user_id);
            if ($submitter?->supervisor_id) {
                $supervisor = User::find($submitter->supervisor_id);
                if ($supervisor && $reviewers->doesntContain('id', $supervisor->id)) {
                    $reviewers->push($supervisor);
                }
            }
        }

        $notification = new CorrectionRequestedNotification(
            $request->user()->name,
            $workDate,
            $correction->id,
        );

        foreach ($reviewers as $reviewer) {
            if ($reviewer->id !== $request->user()->id) {
                $reviewer->notify($notification);
            }
        }

        return $this->sendCreated($this->format($correction));
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::CORRECTION_REVIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $correction = AttendanceCorrection::find($id);
        if (! $correction) {
            return $this->sendError(404, 'NOT_FOUND', 'Correction not found');
        }
        if ($correction->status !== 'pending') {
            return $this->sendError(422, 'INVALID_STATE', 'Correction is not pending');
        }

        $log = AttendanceLog::find($correction->attendance_log_id);
        if (! $log) {
            return $this->sendError(404, 'NOT_FOUND', 'Original attendance record not found');
        }

        // Apply corrected times to the log
        if ($correction->corrected_check_in_at) {
            $log->check_in_at = $correction->corrected_check_in_at;
        }
        if ($correction->corrected_check_out_at) {
            $log->check_out_at = $correction->corrected_check_out_at;
        }
        $log->save();

        $correction->update([
            'status'      => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => Carbon::now(),
        ]);

        $requester = User::find($correction->user_id);
        $requester?->notify(new CorrectionReviewedNotification(
            'approved', $this->workDateString($log), $request->user()->name, $correction->id,
        ));

        return $this->sendOk($this->format($correction->fresh()));
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::CORRECTION_REVIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $correction = AttendanceCorrection::find($id);
        if (! $correction) {
            return $this->sendError(404, 'NOT_FOUND', 'Correction not found');
        }
        if ($correction->status !== 'pending') {
            return $this->sendError(422, 'INVALID_STATE', 'Correction is not pending');
        }

        $rejectionNote = $request->input('rejection_note', '');

        $correction->update([
            'status'         => 'rejected',
            'reviewed_by'    => $request->user()->id,
            'reviewed_at'    => Carbon::now(),
            'rejection_note' => $rejectionNote,
        ]);

        $log2 = AttendanceLog::find($correction->attendance_log_id);

        $requester = User::find($correction->user_id);
        $requester?->notify(new CorrectionReviewedNotification(
            'rejected', $this->workDateString($log2), $request->user()->name, $correction->id,
        ));

        return $this->sendOk($this->format($correction->fresh()));
    }

    private function workDateString(?AttendanceLog $log): string
    {
        if (! $log) return '';
        return $log->work_date instanceof \Carbon\Carbon
            ? $log->work_date->toDateString()
            : (string) $log->work_date;
    }

    private function format(
        AttendanceCorrection $c,
        ?AttendanceLog $log = null,
        ?string $userName = null,
        ?string $reviewerName = null,
    ): array {
        $log          ??= AttendanceLog::find($c->attendance_log_id);
        $userName     ??= User::find($c->user_id)?->name ?? 'Unknown';
        $reviewerName ??= ($c->reviewed_by ? User::find($c->reviewed_by)?->name : null);

        return [
            'id'                    => $c->id,
            'attendance_log_id'     => $c->attendance_log_id,
            'user_id'               => $c->user_id,
            'user_name'             => $userName,
            'work_date'             => $this->workDateString($log),
            'original_check_in_at'  => $log?->check_in_at?->toIso8601String(),
            'original_check_out_at' => $log?->check_out_at?->toIso8601String(),
            'corrected_check_in_at' => $c->corrected_check_in_at?->toIso8601String(),
            'corrected_check_out_at'=> $c->corrected_check_out_at?->toIso8601String(),
            'reason'                => $c->reason,
            'status'                => $c->status,
            'reviewed_by'           => $c->reviewed_by,
            'reviewed_by_name'      => $reviewerName,
            'reviewed_at'           => $c->reviewed_at?->toIso8601String(),
            'rejection_note'        => $c->rejection_note,
            'created_at'            => $c->created_at?->toIso8601String(),
        ];
    }
}
