<?php

namespace Database\Seeders;

use App\Models\JobStatus;
use Illuminate\Database\Seeder;

class JobStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'Permanent',
            'Contract',
            'Probation',
            'Internship',
            'Part-time',
        ];

        foreach ($statuses as $name) {
            JobStatus::firstOrCreate(['name' => $name]);
        }
    }
}
