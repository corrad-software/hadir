<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobTitleRequest;
use App\Http\Requests\UpdateJobTitleRequest;
use App\Http\Traits\ApiResponse;
use App\Models\JobTitle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobTitleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $q     = $request->input('q');

        $query = JobTitle::orderBy('name');

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

    public function store(StoreJobTitleRequest $request): JsonResponse
    {
        $title = JobTitle::create($request->validated());

        return $this->sendCreated($title);
    }

    public function show(int $id): JsonResponse
    {
        $title = JobTitle::find($id);
        if (! $title) {
            return $this->sendError(404, 'NOT_FOUND', 'Job title not found');
        }

        return $this->sendOk($title);
    }

    public function update(UpdateJobTitleRequest $request, int $id): JsonResponse
    {
        $title = JobTitle::find($id);
        if (! $title) {
            return $this->sendError(404, 'NOT_FOUND', 'Job title not found');
        }
        $title->update($request->validated());

        return $this->sendOk($title);
    }

    public function destroy(int $id): JsonResponse
    {
        JobTitle::where('id', $id)->delete();

        return $this->sendOk(['success' => true]);
    }
}
