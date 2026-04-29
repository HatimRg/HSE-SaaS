<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $admin = User::where('email', 'admin@demo.com')->first();
        $engineer = User::where('email', 'engineer@demo.com')->first();

        $projects = [
            [
                'name' => 'Casablanca Tower A',
                'code' => 'CTA-2024',
                'description' => 'Construction of Tower A in Casablanca Financial District',
                'location' => 'Casablanca, Morocco',
                'client_name' => 'Maroc Telecom',
                'start_date' => now()->subMonths(6),
                'end_date' => now()->addYear(),
                'status' => 'active',
                'budget' => 15000000,
            ],
            [
                'name' => 'Rabat Highway Extension',
                'code' => 'RHE-2024',
                'description' => 'Highway extension project from Rabat to Salé',
                'location' => 'Rabat, Morocco',
                'client_name' => 'Ministry of Transport',
                'start_date' => now()->subMonths(3),
                'end_date' => now()->addMonths(18),
                'status' => 'active',
                'budget' => 45000000,
            ],
            [
                'name' => 'Marrakech Resort',
                'code' => 'MRK-2024',
                'description' => 'Luxury resort construction in Marrakech',
                'location' => 'Marrakech, Morocco',
                'client_name' => 'Atlas Hotels',
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(24),
                'status' => 'new',
                'budget' => 25000000,
            ],
            [
                'name' => 'Agadir Port Renovation',
                'code' => 'APR-2023',
                'description' => 'Port facilities renovation and expansion',
                'location' => 'Agadir, Morocco',
                'client_name' => 'Port Authority',
                'start_date' => now()->subYear(),
                'end_date' => now()->subMonth(),
                'status' => 'completed',
                'budget' => 30000000,
            ],
        ];

        foreach ($projects as $data) {
            $project = Project::create([
                ...$data,
                'company_id' => $company->id,
                'manager_id' => $admin->id,
            ]);

            // Assign team members
            $project->team()->attach($admin->id, ['role_in_project' => 'Manager']);
            $project->team()->attach($engineer->id, ['role_in_project' => 'Engineer']);
        }
    }
}
