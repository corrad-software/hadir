<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\AttendancePolicy;
use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;
use RuntimeException;

class AttendanceService
{
    public function getActivePolicy(): ?AttendancePolicy
    {
        return AttendancePolicy::where('is_active', true)->first();
    }

    public function getEffectivePolicyForUser(int $userId): ?AttendancePolicy
    {
        $user = User::with('division')->find($userId);

        if (! $user || ! $user->division_id) {
            return $this->getActivePolicy();
        }

        $division = $user->division;

        if (! $division || ! $division->attendance_policy_id) {
            return $this->getActivePolicy();
        }

        $policy = AttendancePolicy::on('pgsql_attendance')->find($division->attendance_policy_id);

        return $policy ?? $this->getActivePolicy();
    }

    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function isWithinRadius(AttendancePolicy $policy, ?float $lat, ?float $lng): ?bool
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $offices = Office::where('policy_id', $policy->id)->get();

        if ($offices->isEmpty()) {
            return null;
        }

        foreach ($offices as $office) {
            $distance = $this->distanceMeters($office->latitude, $office->longitude, $lat, $lng);
            if ($distance <= $office->radius_meters) {
                return true;
            }
        }

        return false;
    }

    public function computeCheckinStatus(AttendancePolicy $policy, Carbon $checkinTime): string
    {
        $deadline = Carbon::parse($checkinTime->toDateString().' '.$policy->start_time)
            ->addMinutes($policy->grace_period_minutes);

        return $checkinTime->lte($deadline) ? 'on_time' : 'late';
    }

    public function computeCheckoutStatus(AttendancePolicy $policy, AttendanceLog $log): string
    {
        if ($log->check_out_at === null) {
            return $log->status;
        }

        $endTime = Carbon::parse($log->work_date->toDateString().' '.$policy->end_time);

        if ($log->check_out_at->lt($endTime)) {
            return 'early_leave';
        }

        return $log->status === 'pending' ? 'on_time' : $log->status;
    }

    public function checkIn(int $userId, ?float $lat, ?float $lng): AttendanceLog
    {
        if ($lat === null || $lng === null) {
            throw new RuntimeException('GPS location is required to check in');
        }

        $today = Carbon::today()->toDateString();

        $existing = AttendanceLog::where('user_id', $userId)
            ->where('work_date', $today)
            ->first();

        if ($existing) {
            throw new RuntimeException('Already checked in today');
        }

        $now = Carbon::now();
        $policy = $this->getEffectivePolicyForUser($userId);

        $withinRadius = $policy ? $this->isWithinRadius($policy, $lat, $lng) : null;
        $status = ($policy ? $this->computeCheckinStatus($policy, $now) : 'pending');
        $approvalStatus = ($status === 'late') ? 'pending_approval' : null;

        return AttendanceLog::create([
            'user_id' => $userId,
            'work_date' => $today,
            'check_in_at' => $now,
            'check_in_latitude' => $lat,
            'check_in_longitude' => $lng,
            'check_in_within_radius' => $withinRadius,
            'status' => $status,
            'approval_status' => $approvalStatus,
        ]);
    }

    public function checkOut(int $userId, ?float $lat, ?float $lng): AttendanceLog
    {
        if ($lat === null || $lng === null) {
            throw new RuntimeException('GPS location is required to check out');
        }

        $today = Carbon::today()->toDateString();

        $log = AttendanceLog::where('user_id', $userId)
            ->where('work_date', $today)
            ->first();

        if (! $log) {
            throw new RuntimeException('No check-in found for today');
        }

        if ($log->check_out_at !== null) {
            throw new RuntimeException('Already checked out today');
        }

        $policy = $this->getEffectivePolicyForUser($userId);

        $withinRadius = $policy ? $this->isWithinRadius($policy, $lat, $lng) : null;

        $log->check_out_at = Carbon::now();
        $log->check_out_latitude = $lat;
        $log->check_out_longitude = $lng;
        $log->check_out_within_radius = $withinRadius;

        if ($policy) {
            $newStatus = $this->computeCheckoutStatus($policy, $log);
            $log->status = $newStatus;

            if ($newStatus === 'early_leave') {
                // Upgrade to pending_approval unless already approved/rejected from late check-in
                if ($log->approval_status === null) {
                    $log->approval_status = 'pending_approval';
                }
            }
        }

        $log->save();

        return $log->fresh();
    }
}
