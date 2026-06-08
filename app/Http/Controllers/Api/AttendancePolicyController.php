<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendancePolicyRequest;
use App\Http\Requests\UpdateAttendancePolicyRequest;
use App\Http\Traits\ApiResponse;
use App\Models\AttendancePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendancePolicyController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_POLICY_VIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $policies = AttendancePolicy::orderByDesc('is_active')->orderByDesc('created_at')->get();

        return $this->sendOk($policies->map(fn ($p) => $this->formatPolicy($p)));
    }

    public function store(StoreAttendancePolicyRequest $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_POLICY_EDIT)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $policy = AttendancePolicy::create([
            'name'                 => $request->input('name'),
            'work_days'            => $request->input('work_days'),
            'start_time'           => $request->input('start_time'),
            'end_time'             => $request->input('end_time'),
            'grace_period_minutes' => $request->input('grace_period_minutes', 15),
            'is_active'            => $request->boolean('is_active', false),
        ]);

        return $this->sendOk($this->formatPolicy($policy), [], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_POLICY_VIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $policy = AttendancePolicy::find($id);
        if (! $policy) {
            return $this->sendError(404, 'NOT_FOUND', 'Policy not found');
        }

        return $this->sendOk($this->formatPolicy($policy));
    }

    public function update(UpdateAttendancePolicyRequest $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_POLICY_EDIT)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $policy = AttendancePolicy::find($id);
        if (! $policy) {
            return $this->sendError(404, 'NOT_FOUND', 'Policy not found');
        }

        $policy->update($request->only([
            'name', 'work_days', 'start_time', 'end_time',
            'grace_period_minutes', 'is_active',
        ]));

        return $this->sendOk($this->formatPolicy($policy->fresh()));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_POLICY_EDIT)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $policy = AttendancePolicy::find($id);
        if (! $policy) {
            return $this->sendError(404, 'NOT_FOUND', 'Policy not found');
        }

        $policy->delete();

        return $this->sendOk(['success' => true]);
    }

    public function activate(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_POLICY_EDIT)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $policy = AttendancePolicy::find($id);
        if (! $policy) {
            return $this->sendError(404, 'NOT_FOUND', 'Policy not found');
        }

        DB::connection('pgsql_attendance')->transaction(function () use ($policy) {
            AttendancePolicy::query()->update(['is_active' => false]);
            $policy->update(['is_active' => true]);
        });

        return $this->sendOk($this->formatPolicy($policy->fresh()));
    }

    private function formatPolicy(AttendancePolicy $policy): array
    {
        return [
            'id'                   => $policy->id,
            'name'                 => $policy->name,
            'work_days'            => $policy->work_days,
            'start_time'           => $policy->start_time,
            'end_time'             => $policy->end_time,
            'grace_period_minutes' => $policy->grace_period_minutes,
            'offices_count'        => $policy->offices()->count(),
            'is_active'            => $policy->is_active,
            'created_at'           => $policy->created_at,
            'updated_at'           => $policy->updated_at,
        ];
    }
}
