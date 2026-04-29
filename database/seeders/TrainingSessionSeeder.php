<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Project;
use App\Models\TrainingSession;
use Illuminate\Database\Seeder;

class TrainingSessionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $project = Project::first();

        $sessions = [
            [
                'title' => 'Safety Induction - March 2024',
                'type' => 'induction',
                'category' => 'mandatory',
                'start_date' => now()->subDays(15),
                'end_date' => now()->subDays(15)->addHours(4),
                'duration_hours' => 4,
                'location' => 'Training Room A',
                'max_participants' => 30,
                'status' => 'completed',
            ],
            [
                'title' => 'First Aid Certification',
                'type' => 'first_aid',
                'category' => 'mandatory',
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(6),
                'duration_hours' => 16,
                'location' => 'Medical Center',
                'max_participants' => 15,
                'status' => 'planned',
            ],
            [
                'title' => 'Fire Safety Training',
                'type' => 'fire_safety',
                'category' => 'mandatory',
                'start_date' => now()->addDays(10),
                'end_date' => now()->addDays(10)->addHours(3),
                'duration_hours' => 3,
                'location' => 'Site Assembly Point',
                'max_participants' => 50,
                'status' => 'planned',
            ],
            [
                'title' => 'Toolbox Talk - Crane Safety',
                'type' => 'toolbox_talk',
                'category' => 'recommended',
                'start_date' => now()->addDays(2),
                'end_date' => now()->addDays(2)->addMinutes(30),
                'duration_hours' => 0.5,
                'location' => 'Crane Operations Area',
                'max_participants' => 20,
                'status' => 'planned',
            ],
            [
                'title' => 'HSE Awareness Campaign',
                'type' => 'hse_awareness',
                'category' => 'recommended',
                'start_date' => now()->subDays(30),
                'end_date' => now()->subDays(25),
                'duration_hours' => 2,
                'location' => 'Canteen',
                'max_participants' => 100,
                'status' => 'completed',
            ],
        ];

        foreach ($sessions as $session) {
            TrainingSession::create([
                ...$session,
                'company_id' => $company->id,
                'project_id' => $project->id,
            ]);
        }
    }
}
