<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_POLICY_VIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $page     = (int) $request->input('page', 1);
        $limit    = (int) $request->input('limit', 20);
        $q        = $request->input('q');
        $policyId = $request->input('policy_id');

        $query = Office::with('policy');

        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        if ($policyId) {
            $query->where('policy_id', (int) $policyId);
        }

        $total = $query->count();
        $rows  = $query->orderBy('name')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn ($o) => $this->formatOffice($o));

        return $this->sendOk($rows, [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function store(StoreOfficeRequest $request): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_POLICY_EDIT)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $data           = $request->validated();
        $data['radius_meters'] = $data['radius_meters'] ?? 200;

        $office = Office::create($data);
        $office->load('policy');

        return $this->sendCreated($this->formatOffice($office));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_POLICY_VIEW)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $office = Office::with('policy')->find($id);
        if (! $office) {
            return $this->sendError(404, 'NOT_FOUND', 'Office not found');
        }

        return $this->sendOk($this->formatOffice($office));
    }

    public function update(UpdateOfficeRequest $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_POLICY_EDIT)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        $office = Office::find($id);
        if (! $office) {
            return $this->sendError(404, 'NOT_FOUND', 'Office not found');
        }

        $office->update($request->validated());
        $office->load('policy');

        return $this->sendOk($this->formatOffice($office->fresh()));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasPermission(Permission::ATTENDANCE_POLICY_EDIT)) {
            return $this->sendError(403, 'FORBIDDEN', 'Access denied');
        }

        Office::where('id', $id)->delete();

        return $this->sendOk(['success' => true]);
    }

    private function formatOffice(Office $office): array
    {
        return [
            'id'            => $office->id,
            'name'          => $office->name,
            'latitude'      => $office->latitude,
            'longitude'     => $office->longitude,
            'radius_meters' => $office->radius_meters,
            'policy_id'     => $office->policy_id,
            'policy_name'   => $office->policy?->name,
            'created_at'    => $office->created_at,
            'updated_at'    => $office->updated_at,
        ];
    }
}
