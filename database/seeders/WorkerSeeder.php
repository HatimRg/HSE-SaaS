<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Worker;
use Illuminate\Database\Seeder;

class WorkerSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        $workers = [
            [
                'cin' => 'AB123456',
                'first_name' => 'Ahmed',
                'last_name' => 'Benali',
                'gender' => 'male',
                'nationality' => 'Moroccan',
                'phone' => '+212 600 111 001',
                'function' => 'Foreman',
                'contract_type' => 'cdi',
                'hire_date' => now()->subYears(2),
                'medical_fitness_status' => 'fit',
                'medical_fitness_date' => now()->subMonths(3),
                'status' => 'active',
            ],
            [
                'cin' => 'CD789012',
                'first_name' => 'Fatima',
                'last_name' => 'Zahra',
                'gender' => 'female',
                'nationality' => 'Moroccan',
                'phone' => '+212 600 111 002',
                'function' => 'Safety Officer',
                'contract_type' => 'cdi',
                'hire_date' => now()->subYear(),
                'medical_fitness_status' => 'fit',
                'medical_fitness_date' => now()->subMonths(6),
                'status' => 'active',
            ],
            [
                'cin' => 'EF345678',
                'first_name' => 'Omar',
                'last_name' => 'El Amrani',
                'gender' => 'male',
                'nationality' => 'Moroccan',
                'phone' => '+212 600 111 003',
                'function' => 'Heavy Equipment Operator',
                'contract_type' => 'cdi',
                'hire_date' => now()->subMonths(8),
                'medical_fitness_status' => 'fit',
                'medical_fitness_date' => now()->subMonths(2),
                'status' => 'active',
            ],
            [
                'cin' => 'GH901234',
                'first_name' => 'Youssef',
                'last_name' => 'Bennani',
                'gender' => 'male',
                'nationality' => 'Moroccan',
                'phone' => '+212 600 111 004',
                'function' => 'Welder',
                'contract_type' => 'cdd',
                'hire_date' => now()->subMonths(4),
                'medical_fitness_status' => 'fit',
                'medical_fitness_date' => now()->subMonth(),
                'status' => 'active',
            ],
            [
                'cin' => 'IJ567890',
                'first_name' => 'Karim',
                'last_name' => 'Idrissi',
                'gender' => 'male',
                'nationality' => 'Moroccan',
                'phone' => '+212 600 111 005',
                'function' => 'Crane Operator',
                'contract_type' => 'cdi',
                'hire_date' => now()->subYears(3),
                'medical_fitness_status' => 'fit',
                'medical_fitness_date' => now()->subMonths(5),
                'status' => 'active',
            ],
            [
                'cin' => 'KL123789',
                'first_name' => 'Said',
                'last_name' => 'El Fassi',
                'gender' => 'male',
                'nationality' => 'Moroccan',
                'phone' => '+212 600 111 006',
                'function' => 'Scaffolder',
                'contract_type' => 'temporary',
                'hire_date' => now()->subMonths(2),
                'medical_fitness_status' => 'fit',
                'medical_fitness_date' => now()->subWeeks(2),
                'status' => 'active',
            ],
        ];

        foreach ($workers as $workerData) {
            Worker::create([
                ...$workerData,
                'company_id' => $company->id,
            ]);
        }
    }
}
