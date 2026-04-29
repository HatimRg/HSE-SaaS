<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\KpiReport;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class KpiReportSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $project = Project::first();
        $user = User::where('email', 'engineer@demo.com')->first();

        // Generate KPI reports for the last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $weekStart = now()->subMonths($i)->startOfMonth();
            $weekEnd = $weekStart->copy()->endOfMonth();

            $statuses = ['draft', 'submitted', 'approved', 'approved', 'approved'];
            $status = $i > 1 ? 'approved' : ($i === 1 ? 'submitted' : 'draft');

            KpiReport::create([
                'company_id' => $company->id,
                'project_id' => $project->id,
                'user_id' => $user->id,
                'period_start' => $weekStart,
                'period_end' => $weekEnd,
                'status' => $status,
                'total_hours' => rand(8000, 15000),
                'injuries' => rand(0, 2),
                'first_aids' => rand(0, 5),
                'near_misses' => rand(1, 10),
                'observations' => rand(5, 25),
                'lost_time_incidents' => rand(0, 1),
                'environmental_incidents' => rand(0, 1),
                'vehicles_damaged' => rand(0, 2),
                'vehicles_lost' => rand(0, 1),
                'manpower_count' => rand(80, 150),
                'remarks' => 'Weekly HSE performance report',
                'approved_by' => $status === 'approved' ? User::where('email', 'admin@demo.com')->first()->id : null,
                'approved_at' => $status === 'approved' ? now()->subMonths($i)->addDays(3) : null,
            ]);
        }
    }
}
