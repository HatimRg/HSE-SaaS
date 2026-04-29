<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $adminRole = Role::where('name', 'admin')->first();
        $engineerRole = Role::where('name', 'engineer')->first();
        $hseManagerRole = Role::where('name', 'hse_manager')->first();

        // Super Admin - has access to ALL projects
        $admin = User::create([
            'company_id' => $company->id,
            'role_id' => $adminRole->id,
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'admin@demo.com',
            'phone' => '+212 600 000 001',
            'password' => Hash::make('password'),
            'project_access_type' => 'all',
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // HSE Manager - has access to ALL projects
        User::create([
            'company_id' => $company->id,
            'role_id' => $hseManagerRole->id,
            'first_name' => 'HSE',
            'last_name' => 'Manager',
            'email' => 'hse@demo.com',
            'phone' => '+212 600 000 002',
            'password' => Hash::make('password'),
            'project_access_type' => 'all',
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Engineer - has access to specific projects only
        $engineer = User::create([
            'company_id' => $company->id,
            'role_id' => $engineerRole->id,
            'first_name' => 'Project',
            'last_name' => 'Engineer',
            'email' => 'engineer@demo.com',
            'phone' => '+212 600 000 003',
            'password' => Hash::make('password'),
            'project_access_type' => 'projects',
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Supervisor - has access to specific projects only
        $supervisorRole = Role::where('name', 'supervisor')->first();
        $supervisor = User::create([
            'company_id' => $company->id,
            'role_id' => $supervisorRole->id,
            'first_name' => 'Site',
            'last_name' => 'Supervisor',
            'email' => 'supervisor@demo.com',
            'phone' => '+212 600 000 004',
            'password' => Hash::make('password'),
            'project_access_type' => 'projects',
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // HR Director - has access to ALL projects
        $hrDirectorRole = Role::where('name', 'hr_director')->first();
        User::create([
            'company_id' => $company->id,
            'role_id' => $hrDirectorRole->id,
            'first_name' => 'HR',
            'last_name' => 'Director',
            'email' => 'hr@demo.com',
            'phone' => '+212 600 000 005',
            'password' => Hash::make('password'),
            'project_access_type' => 'all',
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Assign specific projects to engineer and supervisor (project_access_type = 'projects')
        $projects = \App\Models\Project::limit(2)->get();
        $engineer->assignedProjects()->attach($projects->pluck('id'));
        $supervisor->assignedProjects()->attach($projects->pluck('id'));
    }
}
