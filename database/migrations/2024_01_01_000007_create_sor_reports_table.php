<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sor_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('reference')->unique();
            $table->date('date');
            $table->string('title');
            $table->text('description');
            
            $table->enum('type', ['observation', 'near_miss', 'incident', 'hazard', 'violation', 'improvement'])->default('observation');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('status', ['open', 'in-progress', 'closed', 'cancelled'])->default('open');
            
            $table->string('location')->nullable();
            $table->foreignId('responsible_person_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('corrective_action')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->json('photos')->nullable();
            $table->json('attachments')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'project_id', 'status']);
            $table->index(['company_id', 'date']);
            $table->index('status');
            $table->index('severity');
            $table->index('due_date');
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sor_reports');
    }
};
