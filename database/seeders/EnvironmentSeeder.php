<?php

namespace Database\Seeders;

use App\Models\EnvironmentalReading;
use App\Models\WasteExport;
use Illuminate\Database\Seeder;

class EnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;
        $projectId = 1;

        // Environmental readings
        $readingTypes = [
            ['type' => 'noise', 'unit' => 'dB', 'min' => 40, 'max' => 95, 'threshold_max' => 85],
            ['type' => 'dust_pm10', 'unit' => 'µg/m³', 'min' => 20, 'max' => 180, 'threshold_max' => 150],
            ['type' => 'dust_pm25', 'unit' => 'µg/m³', 'min' => 10, 'max' => 90, 'threshold_max' => 75],
            ['type' => 'air_quality_aqi', 'unit' => 'AQI', 'min' => 20, 'max' => 200, 'threshold_max' => 100],
            ['type' => 'temperature', 'unit' => '°C', 'min' => 15, 'max' => 45, 'threshold_max' => 40],
            ['type' => 'humidity', 'unit' => '%', 'min' => 30, 'max' => 90, 'threshold_max' => 80],
            ['type' => 'water_ph', 'unit' => 'pH', 'min' => 5, 'max' => 9, 'threshold_min' => 6, 'threshold_max' => 8.5],
            ['type' => 'water_consumption', 'unit' => 'm³', 'min' => 50, 'max' => 500, 'threshold_max' => 400],
            ['type' => 'electricity_kwh', 'unit' => 'kWh', 'min' => 1000, 'max' => 8000, 'threshold_max' => 6000],
        ];

        $locations = ['Gate A', 'Gate B', 'Workshop Zone', 'Office Building', 'Tower 1 Base', 'Chemical Storage'];

        foreach ($readingTypes as $rt) {
            for ($d = 1; $d <= 30; $d++) {
                $value = round($rt['min'] + mt_rand() / mt_getrandmax() * ($rt['max'] - $rt['min']), 1);
                $isExceedance = false;
                if (isset($rt['threshold_max']) && $value > $rt['threshold_max']) $isExceedance = true;
                if (isset($rt['threshold_min']) && $value < $rt['threshold_min']) $isExceedance = true;

                EnvironmentalReading::create([
                    'company_id' => $companyId,
                    'project_id' => $projectId,
                    'type' => $rt['type'],
                    'value' => $value,
                    'unit' => $rt['unit'],
                    'threshold_min' => $rt['threshold_min'] ?? null,
                    'threshold_max' => $rt['threshold_max'] ?? null,
                    'location' => $locations[array_rand($locations)],
                    'measured_at' => now()->subDays($d)->setHour(rand(8, 16))->setMinute(rand(0, 59)),
                    'measured_by' => 2,
                    'is_exceedance' => $isExceedance,
                ]);
            }
        }

        // Waste exports
        $wasteTypes = [
            ['type' => 'construction_debris', 'hazardous' => false],
            ['type' => 'metal', 'hazardous' => false],
            ['type' => 'concrete', 'hazardous' => false],
            ['type' => 'wood', 'hazardous' => false],
            ['type' => 'plastic', 'hazardous' => false],
            ['type' => 'chemical', 'hazardous' => true],
            ['type' => 'hazardous', 'hazardous' => true],
            ['type' => 'general', 'hazardous' => false],
        ];

        $treatments = ['recycling', 'landfill', 'incineration', 'reuse'];
        $carriers = ['EcoWaste Transport', 'GreenCycle Logistics', 'SafeHaul Ltd'];

        foreach ($wasteTypes as $wt) {
            for ($d = 1; $d <= 8; $d++) {
                WasteExport::create([
                    'company_id' => $companyId,
                    'project_id' => $projectId,
                    'date' => now()->subDays($d * 3)->toDateString(),
                    'waste_type' => $wt['type'],
                    'quantity' => round(1 + mt_rand() / mt_getrandmax() * 20, 1),
                    'unit' => 'tonnes',
                    'transport_method' => 'truck',
                    'treatment' => $wt['hazardous'] ? 'incineration' : $treatments[array_rand($treatments)],
                    'treatment_facility' => $wt['hazardous'] ? 'HazWaste Processing Center' : 'City Recycling Plant',
                    'is_hazardous' => $wt['hazardous'],
                    'carrier_name' => $carriers[array_rand($carriers)],
                    'manifest_number' => 'MNF-' . str_pad(rand(1000, 9999), 6, '0', STR_PAD_LEFT),
                    'recorded_by' => 2,
                ]);
            }
        }

        $this->command->info('Seeded environmental readings (30 days x 9 types) and waste exports.');
    }
}
