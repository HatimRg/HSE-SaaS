<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\LibraryFolder;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        // Create root folders
        $folders = [
            [
                'name' => 'HSE Procedures',
                'description' => 'Health, Safety, and Environment procedures and guidelines',
                'sort_order' => 1,
            ],
            [
                'name' => 'Safety Data Sheets',
                'description' => 'SDS for chemicals and hazardous materials',
                'sort_order' => 2,
            ],
            [
                'name' => 'Training Materials',
                'description' => 'Training presentations and documentation',
                'sort_order' => 3,
            ],
            [
                'name' => 'Forms & Templates',
                'description' => 'Standard forms and document templates',
                'sort_order' => 4,
            ],
            [
                'name' => 'Legal & Regulatory',
                'description' => 'Legal documents and regulatory requirements',
                'sort_order' => 5,
            ],
            [
                'name' => 'Project Documents',
                'description' => 'Project specific documentation',
                'sort_order' => 6,
            ],
        ];

        foreach ($folders as $folder) {
            LibraryFolder::create([
                ...$folder,
                'company_id' => $company->id,
                'parent_id' => null,
            ]);
        }

        // Create subfolders in HSE Procedures
        $hseFolder = LibraryFolder::where('name', 'HSE Procedures')->first();
        
        $subFolders = [
            ['name' => 'Emergency Response', 'sort_order' => 1],
            ['name' => 'Incident Reporting', 'sort_order' => 2],
            ['name' => 'PPE Requirements', 'sort_order' => 3],
            ['name' => 'Permit to Work', 'sort_order' => 4],
        ];

        foreach ($subFolders as $subFolder) {
            LibraryFolder::create([
                ...$subFolder,
                'company_id' => $company->id,
                'parent_id' => $hseFolder->id,
            ]);
        }
    }
}
