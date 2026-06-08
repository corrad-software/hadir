<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInRequest;
use App\Http\Requests\RejectAttendanceRequest;
use App\Http\Requests\UpdateAttendanceLogRequest;
use App\Http\Traits\ApiResponse;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceLogController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AttendanceService $attendanceService,
    ) {}

    public function today(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_VIEW_OWN)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $log = AttendanceLog::where('user_id', $request->user()->id)
            ->where('work_date', Carbon::today()->toDateString())
            ->first();

        if (! $log) {
            return $this->sendError(404, 'NOT_FOUND', 'No attendance record for today');
        }

        return $this->sendOk($this->formatLog($log));
    }

    public function todayStats(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_VIEW_ALL)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $today = Carbon::today()->toDateString();

        $checkedIn     = AttendanceLog::where('work_date', $today)->whereNotNull('check_in_at')->count();
        $late          = AttendanceLog::where('work_date', $today)->where('status', 'late')->count();
        $outsideRadius = AttendanceLog::where('work_date', $today)
            ->where(function ($q) {
                $q->where('check_in_within_radius', false)
                  ->orWhere('check_out_within_radius', false);
            })
            ->count();

        return $this->sendOk([
            'checkedIn'     => $checkedIn,
            'late'          => $late,
            'outsideRadius' => $outsideRadius,
        ]);
    }

    public function my(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_VIEW_OWN)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $from = $request->input('from');
        $to = $request->input('to');

        $query = AttendanceLog::where('user_id', $request->user()->id)
            ->orderByDesc('work_date');

        if ($from) {
            $query->where('work_date', '>=', $from);
        }
        if ($to) {
            $query->where('work_date', '<=', $to);
        }

        $total = $query->count();
        $rows = $query->skip(($page - 1) * $limit)->take($limit)->get()
            ->map(fn ($log) => $this->formatLog($log));

        return $this->sendOk($rows, ['page' => $page, 'limit' => $limit, 'total' => $total]);
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_CHECKIN)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        try {
            $log = $this->attendanceService->checkIn(
                $request->user()->id,
                $request->input('latitude') !== null ? (float) $request->input('latitude') : null,
                $request->input('longitude') !== null ? (float) $request->input('longitude') : null,
            );
        } catch (\RuntimeException $e) {
            return $this->sendError(422, 'CHECKIN_ERROR', $e->getMessage());
        }

        return $this->sendOk($this->formatLog($log));
    }

    public function checkOut(CheckInRequest $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_CHECKIN)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        try {
            $log = $this->attendanceService->checkOut(
                $request->user()->id,
                $request->input('latitude') !== null ? (float) $request->input('latitude') : null,
                $request->input('longitude') !== null ? (float) $request->input('longitude') : null,
            );
        } catch (\RuntimeException $e) {
            return $this->sendError(422, 'CHECKOUT_ERROR', $e->getMessage());
        }

        return $this->sendOk($this->formatLog($log));
    }

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_VIEW_ALL)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $userId = $request->input('user_id');
        $from = $request->input('from');
        $to = $request->input('to');
        $status = $request->input('status');

        $query = AttendanceLog::orderByDesc('work_date')->orderByDesc('check_in_at');

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($from) {
            $query->where('work_date', '>=', $from);
        }
        if ($to) {
            $query->where('work_date', '<=', $to);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $approvalStatus = $request->input('approval_status');
        if ($approvalStatus !== null) {
            if ($approvalStatus === 'none') {
                $query->whereNull('approval_status');
            } else {
                $query->where('approval_status', $approvalStatus);
            }
        }

        $mySubordinates = $request->boolean('my_subordinates');
        if ($mySubordinates) {
            $subordinateIds = User::where('supervisor_id', $request->user()->id)->pluck('id')->toArray();
            $query->whereIn('user_id', $subordinateIds);
        }

        $total = $query->count();
        $rows  = $query->skip(($page - 1) * $limit)->take($limit)->get();

        $userIds = $rows->pluck('user_id')->filter()->unique()->toArray();
        $users   = User::whereIn('id', $userIds)->get()->keyBy('id');

        return $this->sendOk(
            $rows->map(fn ($log) => $this->formatLogWithUser($log, $users->get($log->user_id))),
            ['page' => $page, 'limit' => $limit, 'total' => $total],
        );
    }

    public function report(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_VIEW_ALL)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        $rows = AttendanceLog::whereBetween('work_date', [$from, $to])
            ->selectRaw('work_date, status, count(*) as count')
            ->groupBy('work_date', 'status')
            ->orderBy('work_date')
            ->get();

        $byDate = [];
        foreach ($rows as $row) {
            $date = $row->work_date instanceof \Carbon\Carbon
                ? $row->work_date->toDateString()
                : (string) $row->work_date;
            if (! isset($byDate[$date])) {
                $byDate[$date] = ['work_date' => $date, 'on_time' => 0, 'late' => 0, 'early_leave' => 0, 'absent' => 0, 'pending' => 0, 'total' => 0];
            }
            $byDate[$date][$row->status] = (int) $row->count;
            $byDate[$date]['total'] += (int) $row->count;
        }

        return $this->sendOk(array_values($byDate));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_VIEW_ALL)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $log = AttendanceLog::find($id);
        if (! $log) {
            return $this->sendError(404, 'NOT_FOUND', 'Record not found');
        }

        return $this->sendOk($this->formatLogWithUser($log));
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_APPROVE)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $log = AttendanceLog::find($id);
        if (! $log) {
            return $this->sendError(404, 'NOT_FOUND', 'Record not found');
        }

        if ($log->approval_status !== 'pending_approval') {
            return $this->sendError(422, 'INVALID_STATE', 'Record is not pending approval');
        }

        // Non-HR managers can only approve their own subordinates
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_MANAGE)) {
            $staffUser = User::find($log->user_id);
            if (! $staffUser || $staffUser->supervisor_id !== $request->user()->id) {
                return $this->sendError(403, 'FORBIDDEN', 'You can only approve your direct reports');
            }
        }

        $log->update([
            'approval_status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return $this->sendOk($this->formatLogWithUser($log->fresh()));
    }

    public function reject(RejectAttendanceRequest $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_APPROVE)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $log = AttendanceLog::find($id);
        if (! $log) {
            return $this->sendError(404, 'NOT_FOUND', 'Record not found');
        }

        if ($log->approval_status !== 'pending_approval') {
            return $this->sendError(422, 'INVALID_STATE', 'Record is not pending approval');
        }

        if (! $request->user()->hasPermission(Permission::ATTENDANCE_MANAGE)) {
            $staffUser = User::find($log->user_id);
            if (! $staffUser || $staffUser->supervisor_id !== $request->user()->id) {
                return $this->sendError(403, 'FORBIDDEN', 'You can only reject your direct reports');
            }
        }

        $log->update([
            'approval_status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return $this->sendOk($this->formatLogWithUser($log->fresh()));
    }

    public function update(UpdateAttendanceLogRequest $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_MANAGE)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $log = AttendanceLog::find($id);
        if (! $log) {
            return $this->sendError(404, 'NOT_FOUND', 'Record not found');
        }

        $log->update($request->only(['status', 'notes']));

        return $this->sendOk($this->formatLogWithUser($log->fresh()));
    }

    public function teamToday(Request $request): JsonResponse
    {
        $me = $request->user();

        if (! $me->hasPermission(Permission::ATTENDANCE_VIEW_TEAM)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        if ($me->hasPermission(Permission::ATTENDANCE_VIEW_ALL)) {
            // Admin / HR: see entire org
            $members = User::orderBy('name')->get();
        } elseif (User::where('supervisor_id', $me->id)->exists()) {
            // Supervisor: see direct reports
            $members = User::where('supervisor_id', $me->id)->orderBy('name')->get();
        } elseif ($me->division_id) {
            // Regular user in a division: see division colleagues
            $members = User::where('division_id', $me->division_id)->orderBy('name')->get();
        } else {
            // No division, no subordinates: only see self
            $members = User::where('id', $me->id)->get();
        }

        $today = Carbon::today()->toDateString();
        $logs  = AttendanceLog::whereIn('user_id', $members->pluck('id'))
            ->where('work_date', $today)
            ->get()
            ->keyBy('user_id');

        $result = $members->map(function (User $member) use ($logs) {
            $log = $logs->get($member->id);
            return [
                'id'          => $member->id,
                'name'        => $member->name,
                'photoUrl'    => $member->photo_url,
                'checkedIn'   => $log && $log->check_in_at !== null,
                'checkedOut'  => $log && $log->check_out_at !== null,
                'status'      => $log?->status ?? 'absent',
                'checkInAt'   => $log?->check_in_at?->toIso8601String(),
                'checkOutAt'  => $log?->check_out_at?->toIso8601String(),
                'latitude'    => $log?->check_in_latitude,
                'longitude'   => $log?->check_in_longitude,
                'withinRadius' => $log?->check_in_within_radius,
            ];
        });

        return $this->sendOk($result);
    }

    private function formatLog(AttendanceLog $log): array
    {
        return [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'work_date' => $log->work_date?->toDateString(),
            'check_in_at' => $log->check_in_at?->toIso8601String(),
            'check_in_latitude' => $log->check_in_latitude,
            'check_in_longitude' => $log->check_in_longitude,
            'check_in_within_radius' => $log->check_in_within_radius,
            'check_out_at' => $log->check_out_at?->toIso8601String(),
            'check_out_latitude' => $log->check_out_latitude,
            'check_out_longitude' => $log->check_out_longitude,
            'check_out_within_radius' => $log->check_out_within_radius,
            'status' => $log->status,
            'notes' => $log->notes,
            'approval_status' => $log->approval_status,
            'approved_by' => $log->approved_by,
            'approved_at' => $log->approved_at?->toIso8601String(),
            'rejection_reason' => $log->rejection_reason,
            'created_at' => $log->created_at,
            'updated_at' => $log->updated_at,
        ];
    }

    private function formatLogWithUser(AttendanceLog $log, ?User $user = null): array
    {
        $data = $this->formatLog($log);
        $user ??= User::find($log->user_id);
        $data['user'] = $user ? ['id' => $user->id, 'name' => $user->name, 'email' => $user->email] : null;
        return $data;
    }
}
