<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['induction', 'toolbox_talk', 'hse_awareness', 'skill', 'certification', 'emergency', 'first_aid', 'fire_safety']);
            $table->enum('category', ['mandatory', 'recommended', 'optional'])->default('recommended');
            
            $table->string('trainer_name')->nullable();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->decimal('duration_hours', 5, 2)->nullable();
            $table->string('location')->nullable();
            
            $table->integer('max_participants')->default(20);
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned');
            
            $table->string('materials_path')->nullable();
            $table->string('certificate_template')->nullable();
            $table->json('prerequisites')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'type']);
            $table->index('start_date');
            $table->index(['start_date', 'end_date']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
