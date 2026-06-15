<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Traits\ApiResponse;
use App\Models\SsoUser;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * Authenticate a user and start a session.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $ssoUser = SsoUser::where('email', $email)->first();

        if (! $ssoUser || ! password_verify($password, $ssoUser->passwordHash)) {
            return $this->sendError(401, 'INVALID_CREDENTIALS', 'Invalid email or password');
        }

        try {
            // Sync local user from SSO data (name included in create to satisfy NOT NULL)
            $user = User::firstOrCreate(
                ['email' => $ssoUser->email],
                [
                    'name' => $ssoUser->name,
                    'password' => Str::random(32),
                ]
            );

            $ssoRole = strtolower($ssoUser->role);
            $roleModel = \App\Models\Role::where('name', $ssoRole)->first();
            $user->update([
                'name'      => $ssoUser->name,
                'role'      => $ssoRole,
                'role_id'   => $roleModel?->id,
                'photo_url' => $ssoUser->avatarUrl ?: null,
                'is_active' => true,
            ]);

            Auth::login($user);
            $request->session()->regenerate();

            $this->auditService->logAuth('login', $user);

            return $this->sendOk([
                'user' => $this->userPayload($user),
            ]);
        } catch (QueryException $e) {
            logger()->error('Login DB error: '.$e->getMessage());

            return $this->sendError(
                503,
                'DB_ERROR',
                'Database error during login. Run migrations and verify MySQL grants for kedatangan + sso_hadir.'
            );
        } catch (Throwable $e) {
            logger()->error('Login error: '.$e->getMessage());

            return $this->sendError(500, 'LOGIN_ERROR', 'Login failed. Check API logs in Coolify.');
        }
    }

    /**
     * Log the user out and invalidate the session.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user) {
            $this->auditService->logAuth('logout', $user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->sendOk(['success' => true]);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        // Keep role in sync with SSO on every session check
        $ssoUser = SsoUser::where('email', $user->email)->first();
        if ($ssoUser) {
            $ssoRole = strtolower($ssoUser->role);
            if ($ssoRole !== $user->role) {
                $roleModel = \App\Models\Role::where('name', $ssoRole)->first();
                $user->update([
                    'role'    => $ssoRole,
                    'role_id' => $roleModel?->id,
                ]);
            }
        }

        return $this->sendOk([
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Update the authenticated user's profile (name and/or email).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = [];
        if ($request->has('name')) {
            $data['name'] = $request->input('name');
        }
        if ($request->has('email')) {
            $data['email'] = $request->input('email');
        }

        $user->update($data);
        $user->refresh();

        return $this->sendOk([
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        return $this->sendError(400, 'SSO_MANAGED', 'Password is managed by the SSO provider and cannot be changed here');
    }

    /**
     * Upload an avatar for the authenticated user.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:png,jpg,jpeg,gif,webp|max:2048',
        ]);

        $user = $request->user();

        // Remove old avatar if exists
        if ($user->photo_url) {
            $oldPath = 'uploads/'.basename($user->photo_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $filename = 'avatar-'.time().'.'.$ext;

        $file->storeAs('uploads', $filename, 'public');

        $user->update([
            'photo_url' => '/storage/uploads/'.$filename,
        ]);
        $user->refresh();

        return $this->sendOk([
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Remove the authenticated user's avatar.
     */
    public function removeAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->photo_url) {
            $oldPath = 'uploads/'.basename($user->photo_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $user->update(['photo_url' => null]);
        $user->refresh();

        return $this->sendOk([
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Build the user payload for API responses.
     */
    protected function userPayload($user): array
    {
        $hasSupervisees = false;
        if (Schema::hasColumn('users', 'supervisor_id')) {
            try {
                $hasSupervisees = User::where('supervisor_id', $user->id)->exists();
            } catch (Throwable) {
                $hasSupervisees = false;
            }
        }

        return [
            'id'              => $user->id,
            'email'           => $user->email,
            'name'            => $user->name,
            'photo_url'       => $user->photo_url,
            'role'            => $user->role,
            'has_supervisees' => $hasSupervisees,
        ];
    }
}
