<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create a super admin account with full permissions
     */
    public function run(): void
    {
        // Create default company if not exists
        $company = Company::firstOrCreate(
            ['domain' => 'super.hse-saas.com'],
            [
                'name' => 'HSE Super Admin Company',
                'address' => '123 Admin Street, Admin City, France',
                'email' => 'admin@hse-saas.com',
                'phone' => '+33 1 23 45 67 89',
                'is_active' => true,
            ]
        );

        // Create Super Admin role if not exists
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super-admin'],
            [
                'display_name' => 'Super Administrator',
                'description' => 'Full system access with all permissions',
                                'permissions' => json_encode([
                    'users' => ['create', 'read', 'update', 'delete', 'manage'],
                    'companies' => ['create', 'read', 'update', 'delete', 'manage'],
                    'projects' => ['create', 'read', 'update', 'delete', 'manage'],
                    'workers' => ['create', 'read', 'update', 'delete', 'manage'],
                    'kpi' => ['create', 'read', 'update', 'delete', 'export'],
                    'sor' => ['create', 'read', 'update', 'delete', 'export'],
                    'permits' => ['create', 'read', 'update', 'delete', 'approve'],
                    'inspections' => ['create', 'read', 'update', 'delete', 'manage'],
                    'training' => ['create', 'read', 'update', 'delete', 'manage'],
                    'ppe' => ['create', 'read', 'update', 'delete', 'manage'],
                    'library' => ['create', 'read', 'update', 'delete', 'manage'],
                    'community' => ['create', 'read', 'update', 'delete', 'moderate'],
                    'settings' => ['read', 'update', 'manage'],
                    'audit' => ['read', 'export'],
                    'reports' => ['create', 'read', 'export', 'delete'],
                    'notifications' => ['create', 'read', 'update', 'delete', 'send'],
                    'roles' => ['create', 'read', 'update', 'delete', 'manage'],
                    'system' => ['access', 'configure', 'maintain'],
                ]),
            ]
        );

        // Create Admin role
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'Company administrator with management permissions',
                                'permissions' => json_encode([
                    'users' => ['create', 'read', 'update', 'delete'],
                    'projects' => ['create', 'read', 'update', 'delete'],
                    'workers' => ['create', 'read', 'update', 'delete'],
                    'kpi' => ['create', 'read', 'update', 'export'],
                    'sor' => ['create', 'read', 'update', 'export'],
                    'permits' => ['create', 'read', 'update', 'approve'],
                    'inspections' => ['create', 'read', 'update', 'delete'],
                    'training' => ['create', 'read', 'update', 'delete'],
                    'ppe' => ['create', 'read', 'update', 'delete'],
                    'library' => ['create', 'read', 'update', 'delete'],
                    'community' => ['create', 'read', 'update', 'delete'],
                    'settings' => ['read', 'update'],
                    'reports' => ['create', 'read', 'export'],
                ]),
            ]
        );

        // Create Manager role
        Role::firstOrCreate(
            ['name' => 'manager'],
            [
                'display_name' => 'Manager',
                'description' => 'Project manager with operational permissions',
                                'permissions' => json_encode([
                    'workers' => ['create', 'read', 'update'],
                    'kpi' => ['create', 'read', 'update'],
                    'sor' => ['create', 'read', 'update'],
                    'permits' => ['create', 'read', 'update'],
                    'inspections' => ['create', 'read', 'update'],
                    'training' => ['create', 'read', 'update'],
                    'ppe' => ['create', 'read', 'update'],
                    'library' => ['read'],
                    'community' => ['create', 'read', 'update'],
                    'reports' => ['create', 'read'],
                ]),
            ]
        );

        // Create Engineer role
        Role::firstOrCreate(
            ['name' => 'engineer'],
            [
                'display_name' => 'Engineer',
                'description' => 'HSE Engineer with technical permissions',
                                'permissions' => json_encode([
                    'workers' => ['create', 'read', 'update'],
                    'kpi' => ['create', 'read', 'update'],
                    'sor' => ['create', 'read', 'update'],
                    'permits' => ['create', 'read'],
                    'inspections' => ['create', 'read', 'update'],
                    'training' => ['create', 'read', 'update'],
                    'ppe' => ['read', 'update'],
                    'library' => ['read'],
                    'reports' => ['create', 'read'],
                ]),
            ]
        );

        // Create Supervisor role
        Role::firstOrCreate(
            ['name' => 'supervisor'],
            [
                'display_name' => 'Supervisor',
                'description' => 'Site supervisor with limited management permissions',
                                'permissions' => json_encode([
                    'workers' => ['read', 'update'],
                    'kpi' => ['read', 'update'],
                    'sor' => ['create', 'read'],
                    'permits' => ['create', 'read'],
                    'inspections' => ['create', 'read'],
                    'training' => ['read'],
                    'ppe' => ['read', 'update'],
                    'library' => ['read'],
                ]),
            ]
        );

        // Create Worker role
        Role::firstOrCreate(
            ['name' => 'worker'],
            [
                'display_name' => 'Worker',
                'description' => 'Basic worker with viewing permissions',
                                'permissions' => json_encode([
                    'workers' => ['read'],
                    'kpi' => ['read'],
                    'sor' => ['create'],
                    'permits' => ['read'],
                    'ppe' => ['read'],
                    'library' => ['read'],
                ]),
            ]
        );

        // Create Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@hse-saas.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Administrator',
                'password' => Hash::make('SuperAdmin123!'),
                'role_id' => $superAdminRole->id,
                'company_id' => $company->id,
                'phone' => '+33 1 23 45 67 89',
                'email_verified_at' => now(),
                'project_access_type' => 'all',
                'is_active' => true,
                'remember_token' => Str::random(10),
            ]
        );

        // Create regular admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@hse-saas.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make('Admin123!'),
                'role_id' => $adminRole->id,
                'company_id' => $company->id,
                'phone' => '+33 1 23 45 67 90',
                'email_verified_at' => now(),
                'project_access_type' => 'all',
                'is_active' => true,
                'remember_token' => Str::random(10),
            ]
        );

        $this->command->info('========================================');
        $this->command->info('Super Admin Account Created!');
        $this->command->info('========================================');
        $this->command->info('Email: superadmin@hse-saas.com');
        $this->command->info('Password: SuperAdmin123!');
        $this->command->info('');
        $this->command->info('Admin Account Created!');
        $this->command->info('Email: admin@hse-saas.com');
        $this->command->info('Password: Admin123!');
        $this->command->info('========================================');
    }
}
