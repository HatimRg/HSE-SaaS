<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI Engine — NO manual entry.
 * 
 * kpi_definitions: formula-based KPI specs (TRIR, LTIFR, etc.)
 * kpi_values: cached computed values (refreshed by scheduled jobs)
 * 
 * All values are DERIVED from operational data:
 *   hse_events, event_actions, work_permits, inspections, workers, daily_headcounts
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            $table->string('name'); // e.g. "TRIR", "LTIFR", "Near Miss Rate"
            $table->string('code')->unique(); // e.g. "trir", "ltifr"
            $table->text('description')->nullable();
            $table->text('formula'); // e.g. "(injuries * 200000) / total_hours"
            $table->json('input_mapping')->nullable(); // maps formula vars to DB sources
            // e.g. {"injuries": "hse_events.where(type=incident,severity>=high).count()", "total_hours": "daily_headcounts.sum(total_count)*8"}

            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->enum('unit', ['ratio', 'percentage', 'count', 'rate', 'score'])->default('ratio');

            $table->decimal('target_value', 10, 4)->nullable();
            $table->decimal('alert_threshold', 10, 4)->nullable();
            $table->enum('direction', ['lower_is_better', 'higher_is_better'])->default('lower_is_better');

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'frequency']);
        });

        Schema::create('kpi_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('kpi_definition_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('value', 12, 4);
            $table->decimal('target_value', 12, 4)->nullable();
            $table->enum('status', ['on_target', 'warning', 'critical'])->default('on_target');

            // Raw inputs snapshot for auditability
            $table->json('input_snapshot')->nullable();
            // e.g. {"injuries": 3, "total_hours": 240000, "near_misses": 12}

            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['kpi_definition_id', 'project_id', 'period_start', 'period_end'], 'kpi_vals_def_proj_period_unique');
            $table->index(['company_id', 'project_id', 'period_start']);
            $table->index(['kpi_definition_id', 'period_start']);
            $table->index('computed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_values');
        Schema::dropIfExists('kpi_definitions');
    }
};
