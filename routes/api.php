<?php

use App\Http\Controllers\Api\AttendanceCorrectionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AttendanceLogController;
use App\Http\Controllers\Api\AttendancePolicyController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DevelopersGuideController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\JobStatusController;
use App\Http\Controllers\Api\JobTitleController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
        Route::post('/password', [AuthController::class, 'changePassword']);
        Route::post('/avatar', [AuthController::class, 'uploadAvatar']);
        Route::delete('/avatar', [AuthController::class, 'removeAvatar']);
    });
});

// Settings GET is public (used by SPA before auth)
Route::get('/settings', [SettingController::class, 'index']);

// Protected admin routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/sync-sso', [UserController::class, 'syncSso']);
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);

    Route::put('/settings', [SettingController::class, 'update']);
    Route::get('/settings/admin-menu-prefs', [SettingController::class, 'adminMenuPrefs']);
    Route::put('/settings/admin-menu-prefs', [SettingController::class, 'updateAdminMenuPrefs']);

    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    Route::get('/developers-guide', [DevelopersGuideController::class, 'show']);
    Route::put('/developers-guide', [DevelopersGuideController::class, 'update']);

    // HR: Divisions and Staff
    Route::apiResource('divisions', DivisionController::class);
    Route::apiResource('job-statuses', JobStatusController::class);
    Route::apiResource('job-titles', JobTitleController::class);
    Route::get('/staff/stats', [StaffController::class, 'stats']);
    Route::get('/staff', [StaffController::class, 'index']);
    Route::get('/staff/{id}', [StaffController::class, 'show']);
    Route::put('/staff/{id}', [StaffController::class, 'update']);
    Route::post('/staff/{id}/photo', [StaffController::class, 'uploadPhoto']);
    Route::delete('/staff/{id}/photo', [StaffController::class, 'removePhoto']);
    Route::get('/staff/{id}/transfers', [StaffController::class, 'transfers']);

    // Attendance logs (named routes before {id} wildcard)
    Route::get('/attendance/today', [AttendanceLogController::class, 'today']);
    Route::get('/attendance/today/stats', [AttendanceLogController::class, 'todayStats']);
    Route::get('/attendance/team/today', [AttendanceLogController::class, 'teamToday']);
    Route::get('/attendance/my', [AttendanceLogController::class, 'my']);
    Route::post('/attendance/checkin', [AttendanceLogController::class, 'checkIn']);
    Route::post('/attendance/checkout', [AttendanceLogController::class, 'checkOut']);
    Route::get('/attendance/report', [AttendanceLogController::class, 'report']);
    Route::get('/attendance', [AttendanceLogController::class, 'index']);
    Route::post('/attendance/{id}/approve', [AttendanceLogController::class, 'approve']);
    Route::post('/attendance/{id}/reject', [AttendanceLogController::class, 'reject']);
    Route::get('/attendance/{id}', [AttendanceLogController::class, 'show']);
    Route::put('/attendance/{id}', [AttendanceLogController::class, 'update']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

    // Attendance corrections
    Route::get('/corrections', [AttendanceCorrectionController::class, 'index']);
    Route::post('/corrections', [AttendanceCorrectionController::class, 'store']);
    Route::post('/corrections/{id}/approve', [AttendanceCorrectionController::class, 'approve']);
    Route::post('/corrections/{id}/reject', [AttendanceCorrectionController::class, 'reject']);

    // Attendance policies
    Route::get('/attendance-policies', [AttendancePolicyController::class, 'index']);
    Route::post('/attendance-policies', [AttendancePolicyController::class, 'store']);
    Route::get('/attendance-policies/{id}', [AttendancePolicyController::class, 'show']);
    Route::put('/attendance-policies/{id}', [AttendancePolicyController::class, 'update']);
    Route::delete('/attendance-policies/{id}', [AttendancePolicyController::class, 'destroy']);
    Route::post('/attendance-policies/{id}/activate', [AttendancePolicyController::class, 'activate']);
    Route::apiResource('offices', OfficeController::class);
});
