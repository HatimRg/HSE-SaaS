<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Environment module — readings and waste tracking.
 * Feeds into KPI calculations (environmental incidents, waste diversion).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environmental_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->enum('type', [
                'noise', 'dust_pm10', 'dust_pm25', 'water_ph', 'water_turbidity',
                'air_quality_aqi', 'vibration', 'temperature', 'humidity',
                'electricity_kwh', 'water_consumption',
            ]);
            $table->decimal('value', 12, 4);
            $table->string('unit', 20); // 'dB', 'µg/m³', 'pH', 'mg/L', etc.
            $table->decimal('threshold_min', 12, 4)->nullable();
            $table->decimal('threshold_max', 12, 4)->nullable();
            $table->boolean('is_exceedance')->default(false);

            $table->string('location')->nullable();
            $table->datetime('measured_at');
            $table->foreignId('measured_by')->nullable()->constrained('users')->onDelete('set null');

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'project_id', 'type']);
            $table->index(['project_id', 'measured_at']);
            $table->index('is_exceedance');
            $table->index(['type', 'measured_at']);
        });

        Schema::create('waste_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->date('date');
            $table->enum('waste_type', [
                'construction_debris', 'hazardous', 'metal', 'concrete',
                'wood', 'plastic', 'chemical', 'asbestos', 'general', 'other',
            ]);
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20)->default('tonnes');
            $table->enum('transport_method', ['truck', 'skip', 'pipeline', 'other'])->default('truck');
            $table->string('treatment_facility')->nullable();
            $table->enum('treatment', ['recycling', 'landfill', 'incineration', 'reuse', 'other'])->nullable();
            $table->boolean('is_hazardous')->default(false);

            $table->string('carrier_name')->nullable();
            $table->string('manifest_number')->nullable();

            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'project_id', 'date']);
            $table->index(['waste_type', 'date']);
            $table->index('is_hazardous');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_exports');
        Schema::dropIfExists('environmental_readings');
    }
};
