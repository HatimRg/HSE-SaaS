<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('registration_number')->nullable();
            $table->json('settings')->nullable();
            
            // Color palette - customizable by company
            $table->string('color_primary_light')->default('#3b82f6');
            $table->string('color_primary_dark')->default('#1d4ed8');
            $table->string('color_background_light')->default('#ffffff');
            $table->string('color_background_dark')->default('#0f172a');
            $table->string('color_accent')->default('#f59e0b');
            
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('subscription_plan')->default('basic');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('is_active');
            $table->index(['is_active', 'subscription_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
