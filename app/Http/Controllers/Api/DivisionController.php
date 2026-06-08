<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDivisionRequest;
use App\Http\Requests\UpdateDivisionRequest;
use App\Http\Traits\ApiResponse;
use App\Models\AttendancePolicy;
use App\Models\Division;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::HR_VIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $q = $request->input('q');
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 50);

        $query = Division::withCount(['children', 'users']);

        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        $total = $query->count();
        $rows = $query->orderBy('name')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function store(StoreDivisionRequest $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::HR_MANAGE)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $data = $request->validated();

        if (! empty($data['attendance_policy_id'])) {
            $policy = AttendancePolicy::on('pgsql_attendance')->find($data['attendance_policy_id']);
            if (! $policy) {
                return $this->sendError(422, 'VALIDATION_ERROR', 'Attendance policy not found');
            }
        }

        $division = Division::create($data);

        return $this->sendCreated($this->formatDivision($division));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::HR_VIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $division = Division::withCount(['children', 'users'])->find($id);
        if (! $division) {
            return $this->sendError(404, 'NOT_FOUND', 'Division not found');
        }

        return $this->sendOk($this->formatDivision($division));
    }

    public function update(UpdateDivisionRequest $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::HR_MANAGE)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $division = Division::find($id);
        if (! $division) {
            return $this->sendError(404, 'NOT_FOUND', 'Division not found');
        }

        $data = $request->validated();

        if (! empty($data['attendance_policy_id'])) {
            $policy = AttendancePolicy::on('pgsql_attendance')->find($data['attendance_policy_id']);
            if (! $policy) {
                return $this->sendError(422, 'VALIDATION_ERROR', 'Attendance policy not found');
            }
        }

        // Guard against circular parent (cannot set parent to a descendant)
        if (! empty($data['parent_id']) && $data['parent_id'] !== $division->parent_id) {
            if ($this->isDescendant($division, (int) $data['parent_id'])) {
                return $this->sendError(422, 'VALIDATION_ERROR', 'Cannot set a descendant as parent');
            }
        }

        $division->update($data);

        return $this->sendOk($this->formatDivision($division->loadCount(['children', 'users'])));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::HR_MANAGE)) {
            return $this->sendError(403, 'FORBIDDEN', 'Insufficient permissions');
        }

        $division = Division::withCount(['children', 'users'])->find($id);
        if (! $division) {
            return $this->sendError(404, 'NOT_FOUND', 'Division not found');
        }

        if ($division->children_count > 0) {
            return $this->sendError(422, 'HAS_CHILDREN', "Cannot delete division with {$division->children_count} sub-division(s)");
        }

        if ($division->users_count > 0) {
            return $this->sendError(422, 'HAS_USERS', "Cannot delete division with {$division->users_count} active staff member(s)");
        }

        $division->delete();

        return $this->sendOk(['success' => true]);
    }

    private function isDescendant(Division $division, int $candidateParentId): bool
    {
        $children = $division->children()->pluck('id')->toArray();

        if (in_array($candidateParentId, $children)) {
            return true;
        }

        foreach (Division::whereIn('id', $children)->get() as $child) {
            if ($this->isDescendant($child, $candidateParentId)) {
                return true;
            }
        }

        return false;
    }

    private function formatDivision(Division $division): array
    {
        return [
            'id' => $division->id,
            'name' => $division->name,
            'parentId' => $division->parent_id,
            'attendancePolicyId' => $division->attendance_policy_id,
            'childrenCount' => $division->children_count ?? 0,
            'usersCount' => $division->users_count ?? 0,
            'createdAt' => $division->created_at?->toIso8601String(),
            'updatedAt' => $division->updated_at?->toIso8601String(),
        ];
    }
}
