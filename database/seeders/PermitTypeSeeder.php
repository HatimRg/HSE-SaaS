<?php

namespace Database\Seeders;

use App\Models\PermitType;
use Illuminate\Database\Seeder;

class PermitTypeSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;

        $types = [
            [
                'company_id' => $companyId,
                'name' => 'Hot Work Permit',
                'code' => 'hot_work',
                'description' => 'Required for welding, cutting, brazing, or any work producing heat, sparks, or open flames',
                'required_safety_measures' => ['Fire extinguisher within 10m', 'Fire watch for 30 min after work', 'Remove combustibles within 11m radius', 'Smoke/heat detectors verified'],
                'required_ppe' => ['Welding helmet', 'Fire-resistant clothing', 'Leather gloves', 'Safety boots'],
                'requires_fire_watch' => true,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Confined Space Entry',
                'code' => 'confined_space',
                'description' => 'Required for entry into any confined space with limited access/egress and potential hazards',
                'required_safety_measures' => ['Atmospheric testing before entry', 'Continuous ventilation', 'Standby person at entry', 'Rescue plan in place', 'Communication system established'],
                'required_ppe' => ['Full-body harness', 'Gas detector', 'SCBA if required', 'Hard hat'],
                'requires_fire_watch' => false,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Electrical Work Permit',
                'code' => 'electrical',
                'description' => 'Required for any work on or near energized electrical systems above 50V',
                'required_safety_measures' => ['LOTO (Lock Out Tag Out) applied', 'Voltage tester verified', 'Qualified electrician present', 'Arc flash boundary established', 'Insulated tools only'],
                'required_ppe' => ['Arc-rated clothing', 'Insulated gloves', 'Face shield', 'Voltage-rated boots'],
                'requires_fire_watch' => false,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Excavation Permit',
                'code' => 'excavation',
                'description' => 'Required for any excavation, trenching, or earth-moving operations deeper than 30cm',
                'required_safety_measures' => ['Underground utility survey completed', 'Shoring/sloping plan in place', 'Spoil pile set back 1m from edge', 'Access/egress within 7.5m of travel', 'Daily inspection by competent person'],
                'required_ppe' => ['Hard hat', 'Safety vest', 'Steel-toe boots', 'Safety glasses'],
                'requires_fire_watch' => false,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Working at Height Permit',
                'code' => 'working_at_height',
                'description' => 'Required for any work performed at heights above 1.8m (6 feet) where fall protection is needed',
                'required_safety_measures' => ['Fall protection plan in place', 'Anchor points inspected', 'Tool lanyards used', 'Barricades below work area', 'Weather conditions assessed'],
                'required_ppe' => ['Full-body harness', 'Lanyard with shock absorber', 'Hard hat with chin strap', 'Non-slip footwear'],
                'requires_fire_watch' => false,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Lifting Operations Permit',
                'code' => 'lifting',
                'description' => 'Required for crane operations, heavy lifts, and rigging activities',
                'required_safety_measures' => ['Lift plan approved by engineer', 'Crane inspection current', 'Signal person designated', 'Exclusion zone established', 'Load chart verified'],
                'required_ppe' => ['Hard hat', 'Safety vest', 'Steel-toe boots', 'Leather gloves'],
                'requires_fire_watch' => false,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Chemical Work Permit',
                'code' => 'chemical',
                'description' => 'Required for handling, storage, or transfer of hazardous chemicals',
                'required_safety_measures' => ['SDS available for all chemicals', 'Spill containment in place', 'Proper ventilation confirmed', 'Incompatible materials separated', 'Emergency shower/eyewash accessible'],
                'required_ppe' => ['Chemical-resistant gloves', 'Safety goggles', 'Respirator if required', 'Chemical apron'],
                'requires_fire_watch' => false,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            PermitType::firstOrCreate(
                ['company_id' => $type['company_id'], 'code' => $type['code']],
                $type
            );
        }

        $this->command->info('Seeded ' . count($types) . ' permit types.');
    }
}
