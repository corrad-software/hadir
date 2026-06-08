<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use Illuminate\Database\Seeder;

class JobTitleSeeder extends Seeder
{
    public function run(): void
    {
        $titles = [
            // Engineering
            'Software Engineer',
            'Senior Software Engineer',
            'Lead Software Engineer',
            'Frontend Engineer',
            'Backend Engineer',
            'Full Stack Engineer',
            'Mobile Engineer',
            'DevOps Engineer',
            'Site Reliability Engineer',
            'QA Engineer',
            'Security Engineer',

            // Architecture & Leadership
            'Software Architect',
            'Principal Engineer',
            'Engineering Manager',
            'Chief Technology Officer',

            // Product & Design
            'Product Manager',
            'Product Owner',
            'UI/UX Designer',
            'UI/UX Researcher',

            // Data
            'Data Analyst',
            'Data Engineer',
            'Data Scientist',
            'Machine Learning Engineer',

            // Management & Operations
            'Project Manager',
            'Scrum Master',
            'Technical Lead',
            'IT Support Specialist',
            'System Administrator',

            // Business
            'Business Analyst',
            'Sales Executive',
            'Account Manager',
            'Marketing Executive',
            'Human Resources Executive',
            'Finance Executive',
            'Chief Executive Officer',
            'Chief Operating Officer',
        ];

        foreach ($titles as $name) {
            JobTitle::firstOrCreate(['name' => $name]);
        }
    }
}
