<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'description' => 'Full system access',
                'permissions' => [
                    'users.view', 'users.create', 'users.edit', 'users.delete',
                    'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                    'settings.view', 'settings.edit',
                    'audit.read',
                    'attendance.view_own', 'attendance.checkin',
                    'attendance.view_team', 'attendance.view_all', 'attendance.manage',
                    'attendance.policy_view', 'attendance.policy_edit',
                    'attendance.approve',
                    'hr.view', 'hr.manage',
                    'staff.view', 'staff.manage',
                    'correction.request', 'correction.review',
                ],
            ]
        );

        Role::updateOrCreate(
            ['name' => 'hr_admin'],
            [
                'description' => 'HR Admin — manage attendance records and policies',
                'permissions' => [
                    'attendance.view_own', 'attendance.checkin',
                    'attendance.view_team', 'attendance.view_all', 'attendance.manage',
                    'attendance.policy_view', 'attendance.policy_edit',
                    'attendance.approve',
                    'hr.view', 'hr.manage',
                    'staff.view', 'staff.manage',
                    'correction.request', 'correction.review',
                ],
            ]
        );

        Role::updateOrCreate(
            ['name' => 'user'],
            [
                'description' => 'Regular staff member',
                'permissions' => [
                    'attendance.view_own', 'attendance.checkin',
                    'attendance.view_team',
                    'attendance.approve',
                    'correction.request',
                ],
            ]
        );
    }
}
