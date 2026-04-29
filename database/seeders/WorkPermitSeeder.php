<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkPermit;
use Illuminate\Database\Seeder;

class WorkPermitSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $project = Project::first();
        $user = User::where('email', 'engineer@demo.com')->first();
        $admin = User::where('email', 'admin@demo.com')->first();

        $permits = [
            [
                'permit_number' => WorkPermit::generatePermitNumber(),
                'type' => 'hot_work',
                'title' => 'Welding on Level 5',
                'description' => 'Welding operations for steel structure',
                'location' => 'Building A - Level 5',
                'issued_date' => now(),
                'expiry_date' => now()->addDays(7),
                'status' => 'approved',
                'fire_watch_required' => true,
                'approved_at' => now(),
            ],
            [
                'permit_number' => WorkPermit::generatePermitNumber(),
                'type' => 'working_at_height',
                'title' => 'Facade Installation',
                'description' => 'Window installation on exterior facade',
                'location' => 'Building A - North Facade',
                'issued_date' => now()->subDay(),
                'expiry_date' => now()->addDays(6),
                'status' => 'approved',
                'fire_watch_required' => false,
                'approved_at' => now()->subDay(),
            ],
            [
                'permit_number' => WorkPermit::generatePermitNumber(),
                'type' => 'confined_space',
                'title' => 'Tank Inspection',
                'description' => 'Inspection of storage tank interior',
                'location' => 'Storage Area - Tank 3',
                'issued_date' => now()->subDays(2),
                'expiry_date' => now()->addDays(1),
                'status' => 'suspended',
                'fire_watch_required' => false,
                'suspended_at' => now(),
                'suspension_reason' => 'Atmospheric testing required',
            ],
            [
                'permit_number' => WorkPermit::generatePermitNumber(),
                'type' => 'electrical',
                'title' => 'Panel Installation',
                'description' => 'Installation of electrical panels',
                'location' => 'Electrical Room',
                'issued_date' => now(),
                'expiry_date' => now()->addDays(5),
                'status' => 'pending',
                'fire_watch_required' => false,
            ],
        ];

        foreach ($permits as $permit) {
            WorkPermit::create([
                ...$permit,
                'company_id' => $company->id,
                'project_id' => $project->id,
                'user_id' => $user->id,
                'approver_id' => isset($permit['approved_at']) ? $admin->id : null,
            ]);
        }
    }
}
