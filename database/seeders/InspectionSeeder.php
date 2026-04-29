<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Inspection;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class InspectionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $project = Project::first();
        $user = User::where('email', 'hse@demo.com')->first();

        $inspections = [
            [
                'reference' => Inspection::generateReference(),
                'date' => now()->subDays(7),
                'type' => 'safety',
                'location' => 'Building A',
                'inspector_name' => 'HSE Manager',
                'result' => 'pass',
                'score' => 92.5,
                'next_inspection_date' => now()->addDays(21),
                'status' => 'completed',
            ],
            [
                'reference' => Inspection::generateReference(),
                'date' => now()->subDays(3),
                'type' => 'ppe',
                'location' => 'Main Entrance',
                'inspector_name' => 'Safety Officer',
                'result' => 'pass',
                'score' => 88.0,
                'next_inspection_date' => now()->addDays(4),
                'status' => 'completed',
            ],
            [
                'reference' => Inspection::generateReference(),
                'date' => now()->subDay(),
                'type' => 'equipment',
                'location' => 'Equipment Yard',
                'inspector_name' => 'Maintenance Supervisor',
                'result' => 'partial',
                'score' => 75.0,
                'next_inspection_date' => now()->addDays(6),
                'status' => 'pending_actions',
            ],
            [
                'reference' => Inspection::generateReference(),
                'date' => now()->subDays(14),
                'type' => 'housekeeping',
                'location' => 'Storage Area',
                'inspector_name' => 'HSE Manager',
                'result' => 'fail',
                'score' => 45.0,
                'next_inspection_date' => now()->subDays(7),
                'status' => 'verified',
            ],
        ];

        foreach ($inspections as $inspection) {
            Inspection::create([
                ...$inspection,
                'company_id' => $company->id,
                'project_id' => $project->id,
                'user_id' => $user->id,
                'checklist' => $this->generateChecklist($inspection['type']),
            ]);
        }
    }

    private function generateChecklist(string $type): array
    {
        $checklists = [
            'safety' => [
                ['item' => 'Guard rails installed', 'status' => 'pass'],
                ['item' => 'Warning signs posted', 'status' => 'pass'],
                ['item' => 'First aid kit available', 'status' => 'pass'],
                ['item' => 'Emergency exits clear', 'status' => 'pass'],
            ],
            'ppe' => [
                ['item' => 'Hard hats worn', 'status' => 'pass'],
                ['item' => 'Safety boots worn', 'status' => 'pass'],
                ['item' => 'High vis vests worn', 'status' => 'pass'],
                ['item' => 'Gloves available', 'status' => 'fail'],
            ],
            'equipment' => [
                ['item' => 'Crane inspection current', 'status' => 'pass'],
                ['item' => 'Forklift certified', 'status' => 'pass'],
                ['item' => 'Generator maintained', 'status' => 'fail'],
            ],
            'housekeeping' => [
                ['item' => 'Walkways clear', 'status' => 'fail'],
                ['item' => 'Materials stacked properly', 'status' => 'fail'],
                ['item' => 'Waste disposed', 'status' => 'fail'],
            ],
        ];

        return $checklists[$type] ?? [];
    }
}
