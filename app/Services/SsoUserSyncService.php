<?php

namespace App\Services;

use App\Models\Role;
use App\Models\SsoUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SsoUserSyncService
{
    /**
     * @return array{user: User, created: bool}
     */
    public function syncUser(SsoUser $ssoUser, ?Collection $rolesByName = null): array
    {
        $rolesByName ??= Role::all()->keyBy('name');

        $ssoRole = $this->normalizeRole($ssoUser->role);
        $roleId = $rolesByName->get($ssoRole)?->id;
        $photoUrl = $this->normalizePhotoUrl($ssoUser->avatarUrl ?? null);

        $user = User::where('email', $ssoUser->email)->first();
        $created = $user === null;

        if ($created) {
            $user = User::create([
                'name'      => $ssoUser->name,
                'email'     => $ssoUser->email,
                'password'  => Str::random(32),
                'role'      => $ssoRole,
                'role_id'   => $roleId,
                'photo_url' => $photoUrl,
                'is_active' => true,
            ]);
        } else {
            $user->update([
                'name'      => $ssoUser->name,
                'role'      => $ssoRole,
                'role_id'   => $roleId,
                'photo_url' => $photoUrl,
                'is_active' => true,
            ]);
        }

        return ['user' => $user->fresh(), 'created' => $created];
    }

    /**
     * @return array{created: int, updated: int, failed: int, total: int}
     */
    public function syncAll(): array
    {
        $roles = Role::all()->keyBy('name');
        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach (SsoUser::all() as $ssoUser) {
            if (empty($ssoUser->email)) {
                $failed++;

                continue;
            }

            try {
                $result = $this->syncUser($ssoUser, $roles);
                if ($result['created']) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                logger()->warning('SSO sync skipped for '.$ssoUser->email.': '.$e->getMessage());
                $failed++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed'  => $failed,
            'total'   => $created + $updated,
        ];
    }

    public function normalizeRole(mixed $role): string
    {
        $normalized = strtolower(trim((string) ($role ?? 'user')));

        return $normalized !== '' ? $normalized : 'user';
    }

    public function normalizePhotoUrl(mixed $avatarUrl): ?string
    {
        if ($avatarUrl === null || $avatarUrl === '') {
            return null;
        }

        $value = trim((string) $avatarUrl);

        return strlen($value) <= 255 ? $value : null;
    }
}
