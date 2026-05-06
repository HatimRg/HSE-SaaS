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
            KpiDefinitionSeeder::class,
            KpiReportSeeder::class,
            HseEventSeeder::class,
            SorReportSeeder::class,
            WorkPermitSeeder::class,
            InspectionSeeder::class,
            TrainingSessionSeeder::class,
            PpeItemSeeder::class,
            LibrarySeeder::class,
            EnvironmentSeeder::class,
            PermitTypeSeeder::class,
            InspectionTemplateSeeder::class,
            HazardSeeder::class,
            WorkerDataSeeder::class,
        ]);
    }
}
