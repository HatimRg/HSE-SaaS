<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Risk Management Engine — the central nervous system of HSE.
 * 
 * hazards: master list of hazard types (shared across company)
 * risk_assessments: assessment sessions per project
 * risk_items: individual risk entries within an assessment
 */
return new class extends Migration
{
    public function up(): void
    {
        // Master hazard catalog — company-wide, reusable
        Schema::create('hazards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->enum('category', [
                'physical', 'chemical', 'biological', 'ergonomic',
                'psychosocial', 'environmental', 'mechanical', 'electrical',
            ])->default('physical');
            $table->text('description')->nullable();
            $table->json('default_control_measures')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'category']);
            $table->fullText('name');
        });

        // Risk assessment sessions
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->string('title');
            $table->text('task_description')->nullable();
            $table->text('scope')->nullable();
            $table->enum('methodology', ['risk_matrix', 'hazop', 'what_if', 'checklist', 'jha'])->default('risk_matrix');
            $table->enum('status', ['draft', 'under_review', 'approved', 'archived'])->default('draft');

            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            $table->date('assessment_date');
            $table->date('review_date')->nullable();

            $table->json('attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'project_id', 'status']);
            $table->index('assessment_date');
            $table->index('review_date');
        });

        // Individual risk items within an assessment
        Schema::create('risk_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('risk_assessment_id')->constrained()->onDelete('cascade');
            $table->foreignId('hazard_id')->nullable()->constrained()->onDelete('set null');

            $table->string('hazard_description');
            $table->text('potential_consequence')->nullable();

            // Risk scoring (1-5 scale)
            $table->unsignedTinyInteger('likelihood_before')->default(1);
            $table->unsignedTinyInteger('severity_before')->default(1);
            $table->unsignedTinyInteger('risk_score_before')->default(1); // likelihood × severity
            $table->enum('risk_level_before', ['low', 'medium', 'high', 'critical'])->default('low');

            // Control measures
            $table->text('control_measures')->nullable();
            $table->enum('control_type', ['elimination', 'substitution', 'engineering', 'administrative', 'ppe'])->nullable();

            // Residual risk (after controls)
            $table->unsignedTinyInteger('likelihood_after')->default(1);
            $table->unsignedTinyInteger('severity_after')->default(1);
            $table->unsignedTinyInteger('risk_score_after')->default(1);
            $table->enum('risk_level_after', ['low', 'medium', 'high', 'critical'])->default('low');

            $table->foreignId('responsible_person_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('target_date')->nullable();

            $table->timestamps();

            $table->index(['risk_assessment_id', 'risk_level_before']);
            $table->index(['risk_assessment_id', 'risk_level_after']);
            $table->index('hazard_id');
            $table->index('responsible_person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_items');
        Schema::dropIfExists('risk_assessments');
        Schema::dropIfExists('hazards');
    }
};
