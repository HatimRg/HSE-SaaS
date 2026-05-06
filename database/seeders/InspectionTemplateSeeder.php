<?php

namespace Database\Seeders;

use App\Models\InspectionTemplate;
use App\Models\TemplateItem;
use Illuminate\Database\Seeder;

class InspectionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;

        $templates = [
            [
                'name' => 'General Safety Inspection',
                'category' => 'area',
                'description' => 'Comprehensive site safety walkthrough checklist',
                'is_active' => true,
                'items' => [
                    ['question' => 'Emergency exits clearly marked and unobstructed', 'required' => true, 'weight' => 10, 'category' => 'emergency'],
                    ['question' => 'Fire extinguishers inspected and accessible', 'required' => true, 'weight' => 10, 'category' => 'fire_safety'],
                    ['question' => 'First aid kits stocked and accessible', 'required' => true, 'weight' => 8, 'category' => 'first_aid'],
                    ['question' => 'Walkways and corridors free of tripping hazards', 'required' => true, 'weight' => 8, 'category' => 'housekeeping'],
                    ['question' => 'Safety signage visible and current', 'required' => true, 'weight' => 6, 'category' => 'signage'],
                    ['question' => 'Lighting adequate in all work areas', 'required' => false, 'weight' => 5, 'category' => 'environment'],
                    ['question' => 'Housekeeping standards maintained', 'required' => false, 'weight' => 5, 'category' => 'housekeeping'],
                    ['question' => 'Waste disposal containers available and labeled', 'required' => true, 'weight' => 6, 'category' => 'environment'],
                    ['question' => 'Workers wearing required PPE', 'required' => true, 'weight' => 10, 'category' => 'ppe'],
                    ['question' => 'Safety data sheets available for chemicals on site', 'required' => true, 'weight' => 8, 'category' => 'chemical'],
                ],
            ],
            [
                'name' => 'Equipment Inspection',
                'category' => 'equipment',
                'description' => 'Pre-use equipment safety and condition check',
                'is_active' => true,
                'items' => [
                    ['question' => 'Equipment identification and serial number visible', 'required' => true, 'weight' => 5, 'category' => 'identification'],
                    ['question' => 'Guards and safety devices in place', 'required' => true, 'weight' => 10, 'category' => 'safety_devices'],
                    ['question' => 'Emergency stop button accessible and functional', 'required' => true, 'weight' => 10, 'category' => 'safety_devices'],
                    ['question' => 'Fluid levels adequate (oil, hydraulic, coolant)', 'required' => true, 'weight' => 8, 'category' => 'maintenance'],
                    ['question' => 'No visible leaks or damage', 'required' => true, 'weight' => 8, 'category' => 'condition'],
                    ['question' => 'Warning labels and operating instructions visible', 'required' => true, 'weight' => 6, 'category' => 'signage'],
                    ['question' => 'Last inspection date within valid period', 'required' => true, 'weight' => 8, 'category' => 'compliance'],
                    ['question' => 'Operator trained and authorized', 'required' => true, 'weight' => 10, 'category' => 'competency'],
                ],
            ],
            [
                'name' => 'Vehicle Safety Inspection',
                'category' => 'vehicle',
                'description' => 'Daily vehicle pre-use safety checklist',
                'is_active' => true,
                'items' => [
                    ['question' => 'Tire condition and pressure adequate', 'required' => true, 'weight' => 10, 'category' => 'condition'],
                    ['question' => 'Brakes functional (service and parking)', 'required' => true, 'weight' => 10, 'category' => 'safety_devices'],
                    ['question' => 'Lights and signals operational', 'required' => true, 'weight' => 8, 'category' => 'safety_devices'],
                    ['question' => 'Horn and reverse alarm functional', 'required' => true, 'weight' => 8, 'category' => 'safety_devices'],
                    ['question' => 'Mirrors clean and properly adjusted', 'required' => true, 'weight' => 6, 'category' => 'visibility'],
                    ['question' => 'Windshield and windows clean and undamaged', 'required' => false, 'weight' => 5, 'category' => 'visibility'],
                    ['question' => 'Fire extinguisher present and inspected', 'required' => true, 'weight' => 8, 'category' => 'fire_safety'],
                    ['question' => 'First aid kit present and stocked', 'required' => true, 'weight' => 6, 'category' => 'first_aid'],
                    ['question' => 'Seat belts functional', 'required' => true, 'weight' => 8, 'category' => 'safety_devices'],
                    ['question' => 'Fluid levels adequate (oil, coolant, fuel)', 'required' => true, 'weight' => 6, 'category' => 'maintenance'],
                ],
            ],
            [
                'name' => 'Scaffolding Inspection',
                'category' => 'equipment',
                'description' => 'Post-erection and daily scaffolding safety inspection',
                'is_active' => true,
                'items' => [
                    ['question' => 'Scafftag displayed and current', 'required' => true, 'weight' => 10, 'category' => 'compliance'],
                    ['question' => 'Base plates and sole boards properly installed', 'required' => true, 'weight' => 10, 'category' => 'structural'],
                    ['question' => 'Standards, ledgers, and braces secure', 'required' => true, 'weight' => 10, 'category' => 'structural'],
                    ['question' => 'Working platforms fully boarded', 'required' => true, 'weight' => 8, 'category' => 'platform'],
                    ['question' => 'Toe boards and guardrails in place', 'required' => true, 'weight' => 10, 'category' => 'fall_protection'],
                    ['question' => 'Access ladder or staircase properly installed', 'required' => true, 'weight' => 8, 'category' => 'access'],
                    ['question' => 'No overloading or unauthorized modifications', 'required' => true, 'weight' => 8, 'category' => 'compliance'],
                    ['question' => 'Headroom adequate at all levels', 'required' => false, 'weight' => 4, 'category' => 'platform'],
                ],
            ],
        ];

        foreach ($templates as $tplData) {
            $items = $tplData['items'];
            unset($tplData['items']);

            $template = InspectionTemplate::firstOrCreate(
                ['company_id' => $companyId, 'name' => $tplData['name']],
                array_merge($tplData, ['company_id' => $companyId, 'created_by' => 1])
            );

            if ($template->wasRecentlyCreated) {
                foreach ($items as $i => $item) {
                    TemplateItem::create([
                        'inspection_template_id' => $template->id,
                        'question' => $item['question'],
                        'required' => $item['required'],
                        'weight' => $item['weight'],
                        'category' => $item['category'],
                        'sort_order' => $i + 1,
                    ]);
                }
            }
        }

        $this->command->info('Seeded ' . count($templates) . ' inspection templates with items.');
    }
}
