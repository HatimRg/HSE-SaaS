<?php

namespace Database\Seeders;

use App\Models\Hazard;
use Illuminate\Database\Seeder;

class HazardSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;

        $hazards = [
            // Physical hazards
            ['name' => 'Working at Height', 'category' => 'physical', 'description' => 'Risk of falls from elevated work areas, scaffolding, roofs, or ladders', 'default_control_measures' => ['Guardrails and toe-boards', 'Safety harness and lanyard', 'Safety nets below work area', 'Ladder safety training']],
            ['name' => 'Slips, Trips and Falls', 'category' => 'physical', 'description' => 'Risk of injury from wet surfaces, uneven flooring, loose cables, or debris', 'default_control_measures' => ['Housekeeping program', 'Anti-slip flooring', 'Cable management systems', 'Wet floor signage']],
            ['name' => 'Falling Objects', 'category' => 'physical', 'description' => 'Risk of being struck by tools, materials, or debris falling from above', 'default_control_measures' => ['Toe-boards on scaffolding', 'Tool lanyards', 'Exclusion zones below overhead work', 'Hard hat requirement']],
            ['name' => 'Moving Machinery', 'category' => 'physical', 'description' => 'Risk of entanglement, crushing, or amputation from rotating or moving equipment', 'default_control_measures' => ['Machine guarding', 'LOTO procedures', 'Emergency stop devices', 'Training and authorization']],
            ['name' => 'Vehicle/Pedestrian Collision', 'category' => 'physical', 'description' => 'Risk of collision between site vehicles and workers on foot', 'default_control_measures' => ['Separate vehicle/pedestrian routes', 'Speed limits', 'Banksman/spotter for reversing', 'High-visibility vests']],

            // Chemical hazards
            ['name' => 'Hazardous Substances', 'category' => 'chemical', 'description' => 'Exposure to harmful chemicals, solvents, adhesives, or cleaning agents', 'default_control_measures' => ['Substitution with safer alternatives', 'Ventilation and extraction', 'PPE (gloves, goggles, respirator)', 'SDS availability and training']],
            ['name' => 'Dust and Fibers', 'category' => 'chemical', 'description' => 'Inhalation of silica dust, asbestos fibers, or other airborne particulates', 'default_control_measures' => ['Wet cutting methods', 'Dust extraction systems', 'RPE (FFP3 masks)', 'Exposure monitoring']],
            ['name' => 'Welding Fumes', 'category' => 'chemical', 'description' => 'Inhalation of metal fumes and gases from welding, cutting, or brazing', 'default_control_measures' => ['Local exhaust ventilation', 'RPE for welders', 'Enclosure of welding areas', 'Air quality monitoring']],

            // Biological hazards
            ['name' => 'Biological Agents', 'category' => 'biological', 'description' => 'Exposure to bacteria, viruses, or fungi from sewage, contaminated water, or waste', 'default_control_measures' => ['Vaccination program', 'PPE (gloves, coveralls)', 'Hygiene facilities', 'Sharps disposal']],

            // Ergonomic hazards
            ['name' => 'Manual Handling', 'category' => 'ergonomic', 'description' => 'Risk of musculoskeletal injury from lifting, carrying, pushing, or pulling loads', 'default_control_measures' => ['Mechanical lifting aids', 'Team lifting for heavy items', 'Manual handling training', 'Weight labeling on loads']],
            ['name' => 'Repetitive Strain', 'category' => 'ergonomic', 'description' => 'Risk of injury from repetitive motions, awkward postures, or sustained effort', 'default_control_measures' => ['Job rotation', 'Ergonomic tool design', 'Rest breaks', 'Stretching programs']],

            // Environmental hazards
            ['name' => 'Extreme Heat', 'category' => 'environmental', 'description' => 'Risk of heat exhaustion, heat stroke, or dehydration in hot working conditions', 'default_control_measures' => ['Shaded rest areas', 'Hydration stations', 'Work-rest cycles', 'Buddy system']],
            ['name' => 'Extreme Cold', 'category' => 'environmental', 'description' => 'Risk of hypothermia, frostbite, or reduced dexterity in cold conditions', 'default_control_measures' => ['Thermal PPE', 'Heated rest areas', 'Work duration limits', 'Cold stress training']],
            ['name' => 'Noise Exposure', 'category' => 'environmental', 'description' => 'Risk of hearing damage from prolonged exposure to high noise levels', 'default_control_measures' => ['Engineering controls (silencers, barriers)', 'Hearing protection zones', 'Audiometric testing', 'Noise monitoring']],
            ['name' => 'Poor Lighting', 'category' => 'environmental', 'description' => 'Risk of accidents due to inadequate or excessive lighting', 'default_control_measures' => ['Task lighting', 'Emergency lighting', 'Glare control', 'Regular lighting maintenance']],

            // Electrical hazards
            ['name' => 'Electrical Contact', 'category' => 'electrical', 'description' => 'Risk of electrocution or electric shock from contact with live conductors', 'default_control_measures' => ['LOTO procedures', 'Insulated tools', 'RCD protection', 'Competent persons only']],
            ['name' => 'Arc Flash', 'category' => 'electrical', 'description' => 'Risk of burn injury from arc flash during electrical switching or fault conditions', 'default_control_measures' => ['Arc flash study', 'Arc-rated PPE', 'Remote racking', 'Labeling of equipment']],
        ];

        foreach ($hazards as $hazard) {
            Hazard::firstOrCreate(
                ['company_id' => $companyId, 'name' => $hazard['name']],
                array_merge($hazard, ['company_id' => $companyId])
            );
        }

        $this->command->info('Seeded ' . count($hazards) . ' hazards.');
    }
}
