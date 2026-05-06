<?php

namespace Database\Seeders;

use App\Models\WorkerDocument;
use App\Models\TrainingParticipant;
use Illuminate\Database\Seeder;

class WorkerDataSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;

        // Worker documents (certifications, medical, training certs)
        $documents = [
            ['worker_id' => 1, 'type' => 'medical', 'name' => 'Medical Fitness Certificate', 'issuer' => 'Occupational Health Center', 'status' => 'valid', 'days_offset' => -180, 'expiry_days' => 185],
            ['worker_id' => 1, 'type' => 'certification', 'name' => 'Working at Height Certificate', 'issuer' => 'Safety Training Institute', 'status' => 'valid', 'days_offset' => -90, 'expiry_days' => 275],
            ['worker_id' => 1, 'type' => 'training', 'name' => 'Fire Safety Training', 'issuer' => 'Fire Academy', 'status' => 'valid', 'days_offset' => -60, 'expiry_days' => 305],
            ['worker_id' => 2, 'type' => 'medical', 'name' => 'Medical Fitness Certificate', 'issuer' => 'Occupational Health Center', 'status' => 'valid', 'days_offset' => -200, 'expiry_days' => 165],
            ['worker_id' => 2, 'type' => 'certification', 'name' => 'Confined Space Entry Certificate', 'issuer' => 'Safety Training Institute', 'status' => 'valid', 'days_offset' => -120, 'expiry_days' => 245],
            ['worker_id' => 2, 'type' => 'competency', 'name' => 'Scaffolding Competency Card', 'issuer' => 'CISRS', 'status' => 'expiring', 'days_offset' => -340, 'expiry_days' => 30],
            ['worker_id' => 3, 'type' => 'medical', 'name' => 'Medical Fitness Certificate', 'issuer' => 'Occupational Health Center', 'status' => 'expired', 'days_offset' => -400, 'expiry_days' => -35],
            ['worker_id' => 3, 'type' => 'certification', 'name' => 'Electrical Safety Certificate', 'issuer' => 'Electrical Safety Authority', 'status' => 'valid', 'days_offset' => -30, 'expiry_days' => 335],
            ['worker_id' => 3, 'type' => 'training', 'name' => 'First Aid at Work', 'issuer' => 'Red Cross', 'status' => 'valid', 'days_offset' => -150, 'expiry_days' => 215],
            ['worker_id' => 4, 'type' => 'medical', 'name' => 'Medical Fitness Certificate', 'issuer' => 'Occupational Health Center', 'status' => 'valid', 'days_offset' => -100, 'expiry_days' => 265],
            ['worker_id' => 4, 'type' => 'certification', 'name' => 'Crane Operator License', 'issuer' => 'Lifting Authority', 'status' => 'valid', 'days_offset' => -45, 'expiry_days' => 320],
            ['worker_id' => 4, 'type' => 'training', 'name' => 'LOTO Training', 'issuer' => 'Safety Training Institute', 'status' => 'valid', 'days_offset' => -75, 'expiry_days' => 290],
        ];

        foreach ($documents as $doc) {
            $issueDate = now()->subDays(abs($doc['days_offset']));
            $expiryDate = now()->addDays($doc['expiry_days']);

            WorkerDocument::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'worker_id' => $doc['worker_id'],
                    'type' => $doc['type'],
                    'name' => $doc['name'],
                ],
                [
                    'issue_date' => $issueDate,
                    'expiry_date' => $expiryDate,
                    'issuer' => $doc['issuer'],
                    'status' => $doc['status'],
                    'description' => "Auto-seeded {$doc['type']} document",
                ]
            );
        }

        // Training participants
        $participants = [
            ['session_id' => 1, 'worker_id' => 1, 'status' => 'attended', 'score' => 85.00, 'result' => 'pass'],
            ['session_id' => 1, 'worker_id' => 2, 'status' => 'attended', 'score' => 92.50, 'result' => 'pass'],
            ['session_id' => 1, 'worker_id' => 3, 'status' => 'attended', 'score' => 70.00, 'result' => 'pass'],
            ['session_id' => 2, 'worker_id' => 1, 'status' => 'attended', 'score' => 88.00, 'result' => 'pass'],
            ['session_id' => 2, 'worker_id' => 4, 'status' => 'attended', 'score' => 65.00, 'result' => 'fail'],
            ['session_id' => 2, 'worker_id' => 3, 'status' => 'absent', 'score' => null, 'result' => null],
            ['session_id' => 3, 'worker_id' => 2, 'status' => 'attended', 'score' => 95.00, 'result' => 'pass'],
            ['session_id' => 3, 'worker_id' => 4, 'status' => 'attended', 'score' => 78.00, 'result' => 'pass'],
        ];

        foreach ($participants as $p) {
            TrainingParticipant::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'training_session_id' => $p['session_id'],
                    'worker_id' => $p['worker_id'],
                ],
                [
                    'status' => $p['status'],
                    'score' => $p['score'],
                    'result' => $p['result'],
                ]
            );
        }

        $this->command->info('Seeded ' . count($documents) . ' worker documents and ' . count($participants) . ' training participants.');
    }
}
