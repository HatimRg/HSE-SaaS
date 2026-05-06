<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inspection checklist items + inspection templates.
 * 
 * Replaces the JSON `checklist` column on inspections with proper
 * relational structure for traceability and querying.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Reusable inspection templates
        Schema::create('inspection_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->enum('category', ['equipment', 'area', 'vehicle', 'safety', 'environmental'])->default('safety');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'is_active']);
        });

        // Individual checklist items within a template
        Schema::create('template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_template_id')->constrained()->onDelete('cascade');

            $table->string('description');
            $table->enum('category', ['safety', 'environmental', 'equipment', 'housekeeping', 'ppe', 'fire', 'electrical'])->default('safety');
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('weight')->default(1); // scoring weight
            $table->text('guidance_notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('inspection_template_id');
        });

        // Actual inspection items (filled checklist)
        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_item_id')->nullable()->constrained()->onDelete('set null');

            $table->string('checklist_item');
            $table->enum('status', ['ok', 'non_conform', 'not_applicable', 'not_checked'])->default('not_checked');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->text('note')->nullable();
            $table->json('photos')->nullable();

            $table->timestamps();

            $table->index(['inspection_id', 'status']);
            $table->index('template_item_id');
        });

        // Add template_id to inspections table
        Schema::table('inspections', function (Blueprint $table) {
            $table->foreignId('inspection_template_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropForeign(['inspection_template_id']);
            $table->dropColumn('inspection_template_id');
        });
        Schema::dropIfExists('inspection_items');
        Schema::dropIfExists('template_items');
        Schema::dropIfExists('inspection_templates');
    }
};
