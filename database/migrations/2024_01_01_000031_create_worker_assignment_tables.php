<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Worker-project assignments + worker sanctions.
 * 
 * Workers can be assigned to multiple projects. This replaces the
 * single project_id approach with a proper many-to-many relationship.
 * Also adds sanctions tracking (no separate table needed).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Worker → Project many-to-many
        Schema::create('worker_project_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('worker_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->date('assigned_from')->useCurrent();
            $table->date('assigned_until')->nullable();
            $table->enum('status', ['active', 'completed', 'transferred'])->default('active');

            $table->timestamps();

            $table->unique(['worker_id', 'project_id']);
            $table->index(['company_id', 'project_id']);
            $table->index(['company_id', 'worker_id']);
            $table->index('assigned_from');
        });

        // Worker sanctions / disciplinary records
        Schema::create('worker_sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('worker_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');

            $table->enum('type', ['warning', 'suspension', 'dismissal', 're_training', 'other']);
            $table->enum('severity', ['minor', 'major', 'critical'])->default('minor');
            $table->text('reason');
            $table->foreignId('issued_by')->constrained('users')->onDelete('cascade');
            $table->date('issued_at');

            // For suspensions
            $table->date('suspension_from')->nullable();
            $table->date('suspension_until')->nullable();

            $table->text('corrective_action')->nullable();
            $table->enum('status', ['active', 'resolved', 'appealed'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['worker_id', 'status']);
            $table->index(['company_id', 'project_id']);
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_sanctions');
        Schema::dropIfExists('worker_project_assignments');
    }
};
