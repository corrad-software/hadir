<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStaffRequest;
use App\Http\Traits\ApiResponse;
use App\Models\DivisionTransfer;
use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    use ApiResponse;

    public function stats(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::STAFF_VIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $total             = User::count();
        $withDivision      = User::whereNotNull('division_id')->count();
        $withSupervisor    = User::whereNotNull('supervisor_id')->count();
        $withJobTitle      = User::whereNotNull('job_title_id')->count();
        $withJobStatus     = User::whereNotNull('job_status_id')->count();

        return $this->sendOk([
            'total'           => $total,
            'withDivision'    => $withDivision,
            'withSupervisor'  => $withSupervisor,
            'withJobTitle'    => $withJobTitle,
            'withJobStatus'   => $withJobStatus,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::STAFF_VIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $page       = (int) $request->input('page', 1);
        $limit      = (int) $request->input('limit', 20);
        $q          = $request->input('q');
        $divisionId = $request->input('division_id');
        $officeId   = $request->input('office_id');
        $supervisorId = $request->input('supervisor_id');
        $jobStatus  = $request->input('job_status');
        $jobTitle   = $request->input('job_title');

        $query = User::with(['division', 'supervisor', 'jobStatus', 'jobTitle'])
            ->orderBy('name');

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($divisionId !== null) {
            $query->where('division_id', $divisionId === '' ? null : (int) $divisionId);
        }

        if ($officeId !== null) {
            $query->where('office_id', $officeId === '' ? null : (int) $officeId);
        }

        if ($supervisorId !== null) {
            $query->where('supervisor_id', $supervisorId === '' ? null : (int) $supervisorId);
        }

        if ($jobStatus) {
            $query->where('job_status_id', (int) $jobStatus);
        }

        if ($jobTitle) {
            $query->where('job_title_id', (int) $jobTitle);
        }

        $total = $query->count();
        $users = $query->skip(($page - 1) * $limit)->take($limit)->get();

        // Load pending transfers for this page of users
        $userIds = $users->pluck('id')->toArray();
        $pendingTransfers = DivisionTransfer::with('toDivision')
            ->whereIn('user_id', $userIds)
            ->where('processed', false)
            ->orderByDesc('effective_date')
            ->get()
            ->keyBy('user_id');

        $rows = $users->map(fn ($u) => $this->formatStaff($u, $pendingTransfers->get($u->id)));

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::STAFF_VIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $user = User::with(['division', 'supervisor', 'jobStatus', 'jobTitle'])->find($id);
        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Staff member not found');
        }

        $pendingTransfer = DivisionTransfer::with('toDivision')
            ->where('user_id', $user->id)
            ->where('processed', false)
            ->orderByDesc('effective_date')
            ->first();

        return $this->sendOk($this->formatStaff($user, $pendingTransfer));
    }

    public function update(UpdateStaffRequest $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::STAFF_MANAGE)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $user = User::find($id);
        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Staff member not found');
        }

        $data = $request->validated();

        // Profile fields — save directly
        $user->fill($request->only([
            'name', 'phone', 'dob', 'sex',
            'job_title_id', 'job_status_id', 'office_id',
            'address_line1', 'address_line2', 'address_township',
            'address_postcode', 'address_state',
        ]));
        $user->save();

        // Division change: create a transfer effective tomorrow (do NOT update division_id directly)
        if (array_key_exists('division_id', $data) && $data['division_id'] != $user->division_id) {
            // Cancel any existing unprocessed transfer for this user
            DivisionTransfer::where('user_id', $user->id)
                ->where('processed', false)
                ->delete();

            if ($data['division_id'] !== null) {
                DivisionTransfer::create([
                    'user_id' => $user->id,
                    'from_division_id' => $user->division_id,
                    'to_division_id' => $data['division_id'],
                    'effective_date' => Carbon::tomorrow()->toDateString(),
                    'processed' => false,
                    'created_by' => $request->user()->id,
                ]);
            } else {
                // Removing from division takes effect immediately (no attendance impact)
                $user->division_id = null;
            }
        }

        // Supervisor assignment is always immediate
        if (array_key_exists('supervisor_id', $data)) {
            $user->supervisor_id = $data['supervisor_id'];
        }

        $user->save();

        $pendingTransfer = DivisionTransfer::with('toDivision')
            ->where('user_id', $user->id)
            ->where('processed', false)
            ->orderByDesc('effective_date')
            ->first();

        return $this->sendOk($this->formatStaff($user->fresh(['division', 'supervisor', 'jobStatus', 'jobTitle']), $pendingTransfer));
    }

    public function transfers(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::STAFF_VIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $user = User::find($id);
        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Staff member not found');
        }

        $transfers = DivisionTransfer::with('toDivision')
            ->where('user_id', $id)
            ->orderByDesc('effective_date')
            ->limit(20)
            ->get()
            ->map(fn ($t) => $this->formatTransfer($t));

        return $this->sendOk($transfers);
    }

    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::STAFF_MANAGE)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $request->validate([
            'file' => 'required|file|mimes:png,jpg,jpeg,gif,webp|max:2048',
        ]);

        $user = User::find($id);
        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Staff member not found');
        }

        if ($user->photo_url) {
            $oldPath = 'uploads/'.basename($user->photo_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $filename = 'avatar-'.$user->id.'-'.time().'.'.$ext;
        $file->storeAs('uploads', $filename, 'public');

        $user->update(['photo_url' => '/storage/uploads/'.$filename]);

        return $this->sendOk(['photoUrl' => $user->photo_url]);
    }

    public function removePhoto(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::STAFF_MANAGE)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $user = User::find($id);
        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'Staff member not found');
        }

        if ($user->photo_url) {
            $oldPath = 'uploads/'.basename($user->photo_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
            $user->update(['photo_url' => null]);
        }

        return $this->sendOk(['success' => true]);
    }

    private function formatStaff(User $user, ?DivisionTransfer $pendingTransfer = null): array
    {
        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'role'           => $user->role,
            'isActive'       => $user->is_active,
            'photoUrl'       => $user->photo_url,
            'dob'            => $user->dob?->toDateString(),
            'phone'          => $user->phone,
            'sex'            => $user->sex,
            'jobTitleId'     => $user->job_title_id,
            'jobTitleName'   => $user->jobTitle?->name,
            'jobStatusId'    => $user->job_status_id,
            'jobStatusName'  => $user->jobStatus?->name,
            'addressLine1'    => $user->address_line1,
            'addressLine2'    => $user->address_line2,
            'addressTownship' => $user->address_township,
            'addressPostcode' => $user->address_postcode,
            'addressState'    => $user->address_state,
            'officeId'       => $user->office_id,
            'officeName'     => $user->office_id ? Office::find($user->office_id)?->name : null,
            'divisionId'     => $user->division_id,
            'divisionName'   => $user->division?->name,
            'supervisorId'   => $user->supervisor_id,
            'supervisorName' => $user->supervisor?->name,
            'pendingTransfer' => $pendingTransfer ? $this->formatTransfer($pendingTransfer) : null,
        ];
    }

    private function formatTransfer(DivisionTransfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'userId' => $transfer->user_id,
            'fromDivisionId' => $transfer->from_division_id,
            'toDivisionId' => $transfer->to_division_id,
            'toDivisionName' => $transfer->toDivision?->name,
            'effectiveDate' => $transfer->effective_date?->toDateString(),
            'processed' => $transfer->processed,
            'createdAt' => $transfer->created_at?->toIso8601String(),
        ];
    }
}
