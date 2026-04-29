<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_headcounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->date('date');
            $table->integer('men_count')->default(0);
            $table->integer('women_count')->default(0);
            $table->integer('total_count')->default(0);
            $table->integer('contractor_count')->default(0);
            $table->integer('visitor_count')->default(0);
            
            $table->enum('shift', ['day', 'night', 'evening'])->default('day');
            $table->text('notes')->nullable();
            
            $table->enum('weather_conditions', ['sunny', 'cloudy', 'rainy', 'windy', 'stormy', 'foggy', 'snowy'])->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['project_id', 'date', 'shift']);
            $table->index(['company_id', 'project_id', 'date']);
            $table->index(['company_id', 'date']);
            $table->index('shift');
            $table->index(['date', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_headcounts');
    }
};
