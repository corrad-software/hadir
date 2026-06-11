<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Role;
use App\Models\SsoUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use ApiResponse;

    private function formatUser(User $user): array
    {
        return [
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'role'            => $user->role,
            'is_active'       => $user->is_active,
            'photo_url'       => $user->photo_url,
            'division_id'     => $user->division_id,
            'division_name'   => $user->division?->name,
            'supervisor_id'   => $user->supervisor_id,
            'supervisor_name' => $user->supervisor?->name,
            'created_at'      => $user->created_at,
            'updated_at'      => $user->updated_at,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $divisionId = $request->input('division_id');
        $query = User::query();

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($divisionId) {
            $query->where('division_id', (int) $divisionId);
        }

        $total = $query->count();

        $rows = $query->with(['division', 'supervisor'])
            ->orderBy($sortBy, $sortDir)
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn ($user) => $this->formatUser($user));

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = $data['role'] ?? 'user';

        if (SsoUser::where('email', $data['email'])->exists()) {
            return $this->sendError(422, 'EMAIL_EXISTS', 'An account with this email already exists');
        }

        $ssoUser = SsoUser::create([
            'id'           => (string) Str::uuid(),
            'email'        => $data['email'],
            'name'         => $data['name'],
            'passwordHash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'role'         => $role,
        ]);

        try {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => $data['password'],
                'role'      => $role,
                'role_id'   => $this->resolveRoleId($role),
                'is_active' => $data['is_active'] ?? true,
            ]);
        } catch (\Throwable $e) {
            $ssoUser->delete();
            throw $e;
        }

        return $this->sendCreated($this->formatUser($user));
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['division', 'supervisor'])->find($id);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'User not found');
        }

        return $this->sendOk($this->formatUser($user));
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'User not found');
        }

        $data = $request->validated();

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (isset($data['role'])) {
            $updateData['role']    = $data['role'];
            $updateData['role_id'] = $this->resolveRoleId($data['role']);
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }
        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return $this->sendOk($this->formatUser($user));
    }

    public function syncSso(): JsonResponse
    {
        $ssoUsers = SsoUser::all();
        $created  = 0;
        $updated  = 0;

        // Pre-load all roles to avoid N+1 inside the loop
        $roles = Role::all()->keyBy('name');

        foreach ($ssoUsers as $ssoUser) {
            $ssoRole   = strtolower($ssoUser->role);
            $roleId    = $roles->get($ssoRole)?->id;
            $photoUrl  = $ssoUser->avatarUrl ?: null;

            $user = User::where('email', $ssoUser->email)->first();

            if (! $user) {
                User::create([
                    'name'      => $ssoUser->name,
                    'email'     => $ssoUser->email,
                    'password'  => Hash::make(Str::random(32)),
                    'role'      => $ssoRole,
                    'role_id'   => $roleId,
                    'photo_url' => $photoUrl,
                    'is_active' => true,
                ]);
                $created++;
            } else {
                $user->update([
                    'name'      => $ssoUser->name,
                    'role'      => $ssoRole,
                    'role_id'   => $roleId,
                    'photo_url' => $photoUrl,
                    'is_active' => true,
                ]);
                $updated++;
            }
        }

        return $this->sendOk([
            'created' => $created,
            'updated' => $updated,
            'total'   => $created + $updated,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($request->user()->id === $id) {
            return $this->sendError(400, 'SELF_DELETE', 'You cannot delete your own account');
        }

        $user = User::find($id);

        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'User not found');
        }

        $user->delete();

        return $this->sendOk(['success' => true]);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $user = User::find($id);
        if (! $user) {
            return $this->sendError(404, 'NOT_FOUND', 'User not found');
        }

        // Update the SsoUser record (authentication source)
        $ssoUser = SsoUser::where('email', $user->email)->first();
        if (! $ssoUser) {
            return $this->sendError(404, 'NOT_FOUND', 'No SSO account found for this user');
        }

        $ssoUser->passwordHash = password_hash($request->input('password'), PASSWORD_BCRYPT, ['cost' => 12]);
        $ssoUser->save();

        return $this->sendOk(['success' => true]);
    }

    private function resolveRoleId(?string $roleName): ?int
    {
        if (! $roleName) return null;
        return Role::where('name', $roleName)->value('id');
    }
}
