<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobStatusRequest;
use App\Http\Requests\UpdateJobStatusRequest;
use App\Http\Traits\ApiResponse;
use App\Models\JobStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobStatusController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $q     = $request->input('q');

        $query = JobStatus::orderBy('name');

        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        $total = $query->count();
        $rows  = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return $this->sendOk($rows, [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function store(StoreJobStatusRequest $request): JsonResponse
    {
        $status = JobStatus::create($request->validated());

        return $this->sendCreated($status);
    }

    public function show(int $id): JsonResponse
    {
        $status = JobStatus::find($id);
        if (! $status) {
            return $this->sendError(404, 'NOT_FOUND', 'Job status not found');
        }

        return $this->sendOk($status);
    }

    public function update(UpdateJobStatusRequest $request, int $id): JsonResponse
    {
        $status = JobStatus::find($id);
        if (! $status) {
            return $this->sendError(404, 'NOT_FOUND', 'Job status not found');
        }
        $status->update($request->validated());

        return $this->sendOk($status);
    }

    public function destroy(int $id): JsonResponse
    {
        JobStatus::where('id', $id)->delete();

        return $this->sendOk(['success' => true]);
    }
}
