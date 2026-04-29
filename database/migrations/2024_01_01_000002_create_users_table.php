<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->nullable()->constrained()->onDelete('set null');
            
            // Name fields
            $table->string('first_name');
            $table->string('last_name');
            
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            
            // Project access control
            $table->enum('project_access_type', ['all', 'pole', 'projects'])->default('all');
            $table->unsignedBigInteger('pole_id')->nullable();
            // Foreign key will be added after projects table is created
            
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            
            $table->string('language')->default('fr');
            $table->string('timezone')->default('Africa/Casablanca');
            
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super_admin')->default(false);
            
            // Two factor
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->unique(['company_id', 'email']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'role_id']);
            $table->index(['company_id', 'project_access_type']);
            $table->index('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
