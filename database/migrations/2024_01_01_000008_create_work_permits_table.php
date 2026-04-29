<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('permit_number')->unique();
            $table->enum('type', ['hot_work', 'working_at_height', 'confined_space', 'electrical', 'excavation', 'demolition', 'other']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            
            $table->dateTime('issued_date');
            $table->dateTime('expiry_date');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'suspended', 'expired'])->default('draft');
            
            $table->text('hazards_identified')->nullable();
            $table->text('precautions_taken')->nullable();
            
            $table->boolean('fire_watch_required')->default(false);
            $table->foreignId('fire_watch_assigned_to')->nullable()->constrained('users')->onDelete('set null');
            
            $table->foreignId('issuing_authority_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            
            $table->foreignId('renewal_of')->nullable()->constrained('work_permits')->onDelete('set null');
            $table->json('attachments')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'project_id', 'status']);
            $table->index(['company_id', 'type']);
            $table->index('expiry_date');
            $table->index(['status', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_permits');
    }
};
