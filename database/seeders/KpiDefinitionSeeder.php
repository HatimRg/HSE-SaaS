<?php

namespace Database\Seeders;

use App\Models\KpiDefinition;
use Illuminate\Database\Seeder;

class KpiDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1; // Default company

        $definitions = [
            [
                'company_id' => $companyId,
                'code' => 'trir',
                'name' => 'Total Recordable Incident Rate',
                'description' => 'Number of recordable incidents per 200,000 hours worked',
                'formula' => '(recordable_incidents * 200000) / total_hours',
                'input_mapping' => [
                    'recordable_incidents' => 'hse_events:type=incident,severity=high|critical',
                    'total_hours' => 'daily_headcounts:sum(total_count)*8',
                ],
                'target_value' => 0.5,
                'direction' => 'lower_is_better',
                'unit' => 'rate',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'code' => 'ltifr',
                'name' => 'Lost Time Injury Frequency Rate',
                'description' => 'Number of lost time injuries per 1,000,000 hours worked',
                'formula' => '(lost_time_incidents * 1000000) / total_hours',
                'input_mapping' => [
                    'lost_time_incidents' => 'hse_events:type=incident,closed_at!=null',
                    'total_hours' => 'daily_headcounts:sum(total_count)*8',
                ],
                'target_value' => 1.0,
                'direction' => 'lower_is_better',
                'unit' => 'rate',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'code' => 'near_miss_rate',
                'name' => 'Near Miss Reporting Rate',
                'description' => 'Percentage of near misses relative to total events',
                'formula' => '(near_misses / total_events) * 100',
                'input_mapping' => [
                    'near_misses' => 'hse_events:type=near_miss',
                    'total_events' => 'hse_events:count(*)',
                ],
                'target_value' => 60,
                'direction' => 'higher_is_better',
                'unit' => '%',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'code' => 'action_closure_rate',
                'name' => 'Action Closure Rate',
                'description' => 'Percentage of corrective/preventive actions completed on time',
                'formula' => '(completed_actions / total_actions) * 100',
                'input_mapping' => [
                    'completed_actions' => 'event_actions:status=completed|verified',
                    'total_actions' => 'event_actions:count(*)',
                ],
                'target_value' => 90,
                'direction' => 'higher_is_better',
                'unit' => '%',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'code' => 'inspection_compliance',
                'name' => 'Inspection Compliance Rate',
                'description' => 'Percentage of inspections that pass',
                'formula' => '(passed_inspections / total_inspections) * 100',
                'input_mapping' => [
                    'passed_inspections' => 'inspections:result=pass',
                    'total_inspections' => 'inspections:count(*)',
                ],
                'target_value' => 95,
                'direction' => 'higher_is_better',
                'unit' => '%',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'code' => 'permit_compliance',
                'name' => 'Permit Compliance Rate',
                'description' => 'Percentage of active permits that are not expired',
                'formula' => '(active_permits / total_approved_permits) * 100',
                'input_mapping' => [
                    'active_permits' => 'work_permits:status=approved,expiry_date>=now',
                    'total_approved_permits' => 'work_permits:status=approved',
                ],
                'target_value' => 100,
                'direction' => 'higher_is_better',
                'unit' => '%',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'code' => 'training_completion',
                'name' => 'Training Completion Rate',
                'description' => 'Percentage of workers who have completed required training',
                'formula' => '(trained_workers / total_workers) * 100',
                'input_mapping' => [
                    'trained_workers' => 'training_participants:status=attended,distinct(worker_id)',
                    'total_workers' => 'workers:status=active',
                ],
                'target_value' => 100,
                'direction' => 'higher_is_better',
                'unit' => '%',
                'sort_order' => 7,
                'is_active' => true,
            ],
        ];

        foreach ($definitions as $def) {
            KpiDefinition::firstOrCreate(
                ['company_id' => $def['company_id'], 'code' => $def['code']],
                $def
            );
        }

        $this->command->info('Seeded ' . count($definitions) . ' KPI definitions.');
    }
}
