<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceTodaySeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today()->toDateString();

        // Clear today's records first to allow re-running
        AttendanceLog::where('work_date', $today)->delete();

        // Base coords: around Petaling Jaya / KL area
        $officeCoords = [
            [3.1073, 101.6067], // PJ
            [3.1478, 101.6953], // KLCC
            [3.0966, 101.5867], // Subang
        ];

        $records = [
            // On time, checked out, within radius
            ['user_id' => 3,  'in' => '08:12', 'out' => '17:05', 'status' => 'on_time',    'lat' => 3.1075, 'lng' => 101.6068, 'radius' => true],
            ['user_id' => 4,  'in' => '08:45', 'out' => '17:30', 'status' => 'on_time',    'lat' => 3.1079, 'lng' => 101.6071, 'radius' => true],
            ['user_id' => 6,  'in' => '08:55', 'out' => '17:15', 'status' => 'on_time',    'lat' => 3.1480, 'lng' => 101.6950, 'radius' => true],
            ['user_id' => 7,  'in' => '08:30', 'out' => null,    'status' => 'on_time',    'lat' => 3.1481, 'lng' => 101.6955, 'radius' => true],
            ['user_id' => 9,  'in' => '08:50', 'out' => null,    'status' => 'on_time',    'lat' => 3.0968, 'lng' => 101.5870, 'radius' => true],

            // Late, still at work
            ['user_id' => 8,  'in' => '09:20', 'out' => null,    'status' => 'late',       'lat' => 3.1082, 'lng' => 101.6065, 'radius' => true],
            ['user_id' => 10, 'in' => '09:45', 'out' => null,    'status' => 'late',       'lat' => 3.1076, 'lng' => 101.6070, 'radius' => true],
            ['user_id' => 13, 'in' => '10:02', 'out' => null,    'status' => 'late',       'lat' => 3.1484, 'lng' => 101.6948, 'radius' => true],

            // Checked in outside radius (remote / different location)
            ['user_id' => 11, 'in' => '08:40', 'out' => '17:00', 'status' => 'on_time',   'lat' => 3.2100, 'lng' => 101.7200, 'radius' => false],
            ['user_id' => 22, 'in' => '09:10', 'out' => null,    'status' => 'late',       'lat' => 3.0500, 'lng' => 101.5500, 'radius' => false],

            // Early leave
            ['user_id' => 12, 'in' => '08:55', 'out' => '14:30', 'status' => 'early_leave', 'lat' => 3.0965, 'lng' => 101.5869, 'radius' => true],

            // Checked in, no GPS
            ['user_id' => 14, 'in' => '09:00', 'out' => null,    'status' => 'on_time',   'lat' => null,   'lng' => null,     'radius' => null],
            ['user_id' => 25, 'in' => '08:30', 'out' => '17:00', 'status' => 'on_time',   'lat' => null,   'lng' => null,     'radius' => null],
        ];

        // Users 15–37 not in the list above are absent (no record inserted)

        foreach ($records as $r) {
            $checkInAt  = $r['in']  ? Carbon::parse($today . ' ' . $r['in'])  : null;
            $checkOutAt = $r['out'] ? Carbon::parse($today . ' ' . $r['out']) : null;

            AttendanceLog::create([
                'user_id'                => $r['user_id'],
                'work_date'              => $today,
                'check_in_at'            => $checkInAt,
                'check_in_latitude'      => $r['lat'],
                'check_in_longitude'     => $r['lng'],
                'check_in_within_radius' => $r['radius'],
                'check_out_at'           => $checkOutAt,
                'check_out_latitude'     => $checkOutAt ? $r['lat'] : null,
                'check_out_longitude'    => $checkOutAt ? $r['lng'] : null,
                'check_out_within_radius'=> $checkOutAt ? $r['radius'] : null,
                'status'                 => $r['status'],
            ]);
        }

        $this->command->info('Seeded ' . count($records) . ' attendance records for today (' . $today . ').');
    }
}
