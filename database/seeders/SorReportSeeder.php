<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Project;
use App\Models\SorReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class SorReportSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $project = Project::first();
        $user = User::where('email', 'engineer@demo.com')->first();

        $reports = [
            [
                'reference' => SorReport::generateReference(),
                'date' => now()->subDays(5),
                'title' => 'Missing Guard Rail',
                'description' => 'Guard rail missing on level 3 scaffolding',
                'type' => 'hazard',
                'severity' => 'high',
                'status' => 'in-progress',
                'location' => 'Building A - Level 3',
                'due_date' => now()->addDays(2),
            ],
            [
                'reference' => SorReport::generateReference(),
                'date' => now()->subDays(3),
                'title' => 'Worker Without Helmet',
                'description' => 'Worker observed without proper PPE in construction zone',
                'type' => 'violation',
                'severity' => 'medium',
                'status' => 'closed',
                'location' => 'Main Entrance',
                'corrective_action' => 'Worker was reminded and provided with helmet',
                'completed_at' => now()->subDays(2),
            ],
            [
                'reference' => SorReport::generateReference(),
                'date' => now()->subDays(1),
                'title' => 'Oil Spill',
                'description' => 'Oil spill observed near equipment storage',
                'type' => 'incident',
                'severity' => 'medium',
                'status' => 'open',
                'location' => 'Equipment Yard',
                'due_date' => now()->addDays(3),
            ],
            [
                'reference' => SorReport::generateReference(),
                'date' => now()->subDays(10),
                'title' => 'Near Miss - Falling Object',
                'description' => 'Tool dropped from height, no injuries',
                'type' => 'near_miss',
                'severity' => 'high',
                'status' => 'closed',
                'location' => 'Tower A',
                'corrective_action' => 'Tool tethering policy reinforced in toolbox talk',
                'completed_at' => now()->subDays(5),
            ],
        ];

        foreach ($reports as $report) {
            SorReport::create([
                ...$report,
                'company_id' => $company->id,
                'project_id' => $project->id,
                'user_id' => $user->id,
            ]);
        }
    }
}
