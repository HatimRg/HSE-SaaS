<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('reference')->unique();
            $table->date('date');
            $table->enum('type', ['safety', 'environmental', 'equipment', 'housekeeping', 'ppe', 'fire', 'electrical']);
            $table->string('location')->nullable();
            $table->string('inspector_name')->nullable();
            
            $table->enum('result', ['pass', 'fail', 'partial']);
            $table->decimal('score', 5, 2)->nullable();
            $table->json('checklist')->nullable();
            
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('corrective_actions')->nullable();
            
            $table->date('next_inspection_date')->nullable();
            $table->enum('status', ['completed', 'pending_actions', 'verified'])->default('completed');
            
            $table->string('report_path')->nullable();
            $table->json('photos')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'project_id', 'type']);
            $table->index(['company_id', 'result']);
            $table->index('next_inspection_date');
            $table->index('date');
            $table->index(['status', 'next_inspection_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
