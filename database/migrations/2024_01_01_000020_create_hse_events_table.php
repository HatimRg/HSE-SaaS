<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified HSE Events table — replaces sor_reports.
 * 
 * All safety observations, incidents, near-misses, audits, and training events
 * flow through this single table. Type discrimination via the `type` enum.
 * This eliminates the need for separate SOR/incident/near-miss tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hse_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade');

            $table->string('reference')->unique();
            $table->enum('type', [
                'observation', 'near_miss', 'incident', 'hazard',
                'violation', 'improvement', 'audit', 'training',
            ])->default('observation');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('status', ['open', 'in_progress', 'closed', 'verified', 'cancelled'])->default('open');

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();

            // Risk linkage — added via separate migration after risk_items table exists
            $table->unsignedBigInteger('risk_item_id')->nullable();

            // Assignment & escalation
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->date('due_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedTinyInteger('escalation_level')->default(0);

            // Timestamp of the actual event
            $table->datetime('occurred_at');

            // Media
            $table->json('photos')->nullable();
            $table->json('attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'project_id', 'type']);
            $table->index(['company_id', 'project_id', 'status']);
            $table->index(['company_id', 'severity']);
            $table->index(['project_id', 'occurred_at']);
            $table->index('due_date');
            $table->index(['status', 'due_date']);
            $table->index('escalation_level');
            $table->index('risk_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hse_events');
    }
};
