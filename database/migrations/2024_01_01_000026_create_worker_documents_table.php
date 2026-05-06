<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Worker documents — unified storage for training certs, medical records,
 * certifications, and competency records. No separate tables needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('worker_id')->constrained()->onDelete('cascade');

            $table->enum('type', [
                'training_certificate', 'medical_fitness', 'certification',
                'competency_assessment', 'induction_record', 'license',
                'vaccination_record', 'other',
            ]);
            $table->string('name');
            $table->text('description')->nullable();

            $table->string('issuer')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->enum('status', ['valid', 'expired', 'pending_renewal', 'revoked'])->default('valid');

            $table->string('file_path')->nullable();
            $table->json('metadata')->nullable(); // extra type-specific fields

            // Link to training session if applicable
            $table->foreignId('training_session_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['worker_id', 'type']);
            $table->index(['company_id', 'type', 'status']);
            $table->index('expiry_date');
            $table->index(['type', 'expiry_date']); // for renewal alerts
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_documents');
    }
};
