<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified corrective / preventive actions.
 * 
 * Used by both hse_events and inspections — the `source_type` polymorphic
 * column allows a single actions table to serve all modules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Polymorphic: can belong to hse_events, inspections, or any future source
            $table->unsignedBigInteger('source_id');
            $table->string('source_type'); // 'hse_event', 'inspection', etc.

            $table->text('description');
            $table->enum('type', ['corrective', 'preventive'])->default('corrective');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'completed', 'verified', 'overdue'])->default('open');

            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->date('due_date');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');

            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['source_type', 'source_id']);
            $table->index(['assigned_to', 'status']);
            $table->index('due_date');
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_actions');
    }
};
