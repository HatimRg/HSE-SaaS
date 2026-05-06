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
        $admin = User::firstOrCreate(
            ['company_id' => $company->id, 'email' => 'admin@demo.com'],
            [
            'role_id' => $adminRole?->id,
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'phone' => '+212 600 000 001',
            'password' => Hash::make('password'),
            'project_access_type' => 'all',
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // HSE Manager - has access to ALL projects
        User::firstOrCreate(
            ['company_id' => $company->id, 'email' => 'hse@demo.com'],
            [
            'role_id' => $hseManagerRole?->id,
            'first_name' => 'HSE',
            'last_name' => 'Manager',
            'phone' => '+212 600 000 002',
            'password' => Hash::make('password'),
            'project_access_type' => 'all',
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Engineer - has access to specific projects only
        $engineer = User::firstOrCreate(
            ['company_id' => $company->id, 'email' => 'engineer@demo.com'],
            [
            'role_id' => $engineerRole?->id,
            'first_name' => 'Project',
            'last_name' => 'Engineer',
            'phone' => '+212 600 000 003',
            'password' => Hash::make('password'),
            'project_access_type' => 'projects',
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Supervisor - has access to specific projects only
        $supervisorRole = Role::where('name', 'supervisor')->first();
        $supervisor = User::firstOrCreate(
            ['company_id' => $company->id, 'email' => 'supervisor@demo.com'],
            [
            'role_id' => $supervisorRole?->id,
            'first_name' => 'Site',
            'last_name' => 'Supervisor',
            'phone' => '+212 600 000 004',
            'password' => Hash::make('password'),
            'project_access_type' => 'projects',
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // HR Director - has access to ALL projects
        $hrDirectorRole = Role::where('name', 'hr_director')->first();
        User::firstOrCreate(
            ['company_id' => $company->id, 'email' => 'hr@demo.com'],
            [
            'role_id' => $hrDirectorRole?->id,
            'first_name' => 'HR',
            'last_name' => 'Director',
            'phone' => '+212 600 000 005',
            'password' => Hash::make('password'),
            'project_access_type' => 'all',
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Assign specific projects to engineer and supervisor (project_access_type = 'projects')
        $projects = \App\Models\Project::limit(2)->get();
        $engineer->assignedProjects()->syncWithoutDetaching($projects->pluck('id'));
        $supervisor->assignedProjects()->syncWithoutDetaching($projects->pluck('id'));
    }
}
