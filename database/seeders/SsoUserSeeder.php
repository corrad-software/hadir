<?php

namespace Database\Seeders;

use App\Models\SsoUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SsoUserSeeder extends Seeder
{
    /**
     * Seed the initial SSO admin used for staff login.
     */
    public function run(): void
    {
        $email = env('SSO_ADMIN_EMAIL');
        $password = env('SSO_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            return;
        }

        SsoUser::updateOrCreate(
            ['email' => $email],
            [
                'id' => env('SSO_ADMIN_ID', (string) Str::uuid()),
                'name' => env('SSO_ADMIN_NAME', 'Administrator'),
                'passwordHash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'role' => env('SSO_ADMIN_ROLE', 'admin'),
                'avatarUrl' => null,
            ]
        );
    }
}
