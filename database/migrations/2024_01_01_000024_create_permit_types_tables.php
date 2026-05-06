<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permit type relational tables — replaces boolean flags on work_permits.
 * 
 * A permit can have MULTIPLE types (hot_work + confined_space) without
 * adding boolean columns for each type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permit_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            $table->string('name'); // "Hot Work", "Confined Space", etc.
            $table->string('code')->unique(); // "hot_work", "confined_space"
            $table->text('description')->nullable();
            $table->json('required_safety_measures')->nullable();
            $table->json('required_ppe')->nullable();
            $table->boolean('requires_fire_watch')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        Schema::create('permit_type_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_id')->constrained('work_permits')->onDelete('cascade');
            $table->foreignId('permit_type_id')->constrained()->onDelete('cascade');

            $table->timestamps();

            $table->unique(['permit_id', 'permit_type_id']);
            $table->index('permit_type_id');
        });

        // Link permits to risk assessments
        // Add risk_assessment_id column to existing work_permits table
        Schema::table('work_permits', function (Blueprint $table) {
            $table->foreignId('risk_assessment_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('work_permits', function (Blueprint $table) {
            $table->dropForeign(['risk_assessment_id']);
            $table->dropColumn('risk_assessment_id');
        });
        Schema::dropIfExists('permit_type_assignments');
        Schema::dropIfExists('permit_types');
    }
};
