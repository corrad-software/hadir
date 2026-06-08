<?php

namespace App\Enums;

class Permission
{
    // Users
    const USERS_VIEW = 'users.view';

    const USERS_CREATE = 'users.create';

    const USERS_EDIT = 'users.edit';

    const USERS_DELETE = 'users.delete';

    // Roles
    const ROLES_VIEW = 'roles.view';

    const ROLES_CREATE = 'roles.create';

    const ROLES_EDIT = 'roles.edit';

    const ROLES_DELETE = 'roles.delete';

    // Settings
    const SETTINGS_VIEW = 'settings.view';

    const SETTINGS_EDIT = 'settings.edit';

    // Audit
    const AUDIT_READ = 'audit.read';

    // Attendance
    const ATTENDANCE_VIEW_OWN = 'attendance.view_own';

    const ATTENDANCE_CHECKIN = 'attendance.checkin';

    const ATTENDANCE_VIEW_ALL = 'attendance.view_all';

    const ATTENDANCE_MANAGE = 'attendance.manage';

    const ATTENDANCE_POLICY_VIEW = 'attendance.policy_view';

    const ATTENDANCE_POLICY_EDIT = 'attendance.policy_edit';

    const ATTENDANCE_APPROVE = 'attendance.approve';

    const ATTENDANCE_VIEW_TEAM = 'attendance.view_team';

    // HR / Divisions
    const HR_VIEW = 'hr.view';

    const HR_MANAGE = 'hr.manage';

    // Staff
    const STAFF_VIEW = 'staff.view';

    const STAFF_MANAGE = 'staff.manage';

    // HR Configuration
    const JOB_STATUSES_VIEW = 'job_statuses.view';

    const JOB_STATUSES_MANAGE = 'job_statuses.manage';

    const JOB_TITLES_VIEW = 'job_titles.view';

    const JOB_TITLES_MANAGE = 'job_titles.manage';

    // Corrections
    const CORRECTION_REQUEST = 'correction.request';

    const CORRECTION_REVIEW = 'correction.review';

    public static function all(): array
    {
        return [
            self::USERS_VIEW, self::USERS_CREATE, self::USERS_EDIT, self::USERS_DELETE,
            self::ROLES_VIEW, self::ROLES_CREATE, self::ROLES_EDIT, self::ROLES_DELETE,
            self::SETTINGS_VIEW, self::SETTINGS_EDIT,
            self::AUDIT_READ,
            self::ATTENDANCE_VIEW_OWN, self::ATTENDANCE_CHECKIN,
            self::ATTENDANCE_VIEW_TEAM, self::ATTENDANCE_VIEW_ALL,
            self::ATTENDANCE_MANAGE,
            self::ATTENDANCE_POLICY_VIEW, self::ATTENDANCE_POLICY_EDIT,
            self::ATTENDANCE_APPROVE,
            self::HR_VIEW, self::HR_MANAGE,
            self::STAFF_VIEW, self::STAFF_MANAGE,
            self::JOB_STATUSES_VIEW, self::JOB_STATUSES_MANAGE,
            self::JOB_TITLES_VIEW, self::JOB_TITLES_MANAGE,
            self::CORRECTION_REQUEST, self::CORRECTION_REVIEW,
        ];
    }
}
