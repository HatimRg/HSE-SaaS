<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
            ProjectSeeder::class,
            WorkerSeeder::class,
            KpiReportSeeder::class,
            SorReportSeeder::class,
            WorkPermitSeeder::class,
            InspectionSeeder::class,
            TrainingSessionSeeder::class,
            PpeItemSeeder::class,
            LibrarySeeder::class,
        ]);
    }
}
