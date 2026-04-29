<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            $table->string('cin')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->virtualAs("CONCAT(first_name, ' ', last_name)");
            
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('nationality')->nullable();
            
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            
            $table->string('function')->nullable();
            $table->string('department')->nullable();
            $table->enum('contract_type', ['cdi', 'cdd', 'intern', 'temporary', 'subcontractor'])->nullable();
            $table->date('hire_date')->nullable();
            
            // Medical info
            $table->date('medical_fitness_date')->nullable();
            $table->enum('medical_fitness_status', ['fit', 'unfit', 'restricted', 'pending'])->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('blood_type', 5)->nullable();
            
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            
            $table->enum('status', ['active', 'inactive', 'suspended', 'terminated'])->default('active');
            $table->string('photo')->nullable();
            $table->json('badges')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['company_id', 'cin']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'function']);
            $table->index('medical_fitness_date');
            $table->index(['last_name', 'first_name']);
            $table->fullText(['first_name', 'last_name', 'cin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
