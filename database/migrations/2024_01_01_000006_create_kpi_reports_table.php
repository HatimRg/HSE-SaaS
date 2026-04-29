<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            
            // Safety metrics
            $table->decimal('total_hours', 12, 2)->default(0);
            $table->integer('injuries')->default(0);
            $table->integer('first_aids')->default(0);
            $table->integer('near_misses')->default(0);
            $table->integer('observations')->default(0);
            $table->integer('lost_time_incidents')->default(0);
            
            // Environmental metrics
            $table->integer('environmental_incidents')->default(0);
            
            // Vehicle metrics
            $table->integer('vehicles_damaged')->default(0);
            $table->integer('vehicles_lost')->default(0);
            
            // Workforce metrics
            $table->integer('manpower_count')->default(0);
            
            $table->text('remarks')->nullable();
            
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'project_id', 'status']);
            $table->index(['company_id', 'period_start', 'period_end']);
            $table->index(['project_id', 'status']);
            $table->index('status');
            $table->index(['created_at', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_reports');
    }
};
