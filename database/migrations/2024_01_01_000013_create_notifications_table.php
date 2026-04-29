<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['system', 'kpi', 'sor', 'permit', 'inspection', 'training', 'worker', 'approval'])->default('system');
            $table->enum('urgency', ['info', 'warning', 'urgent', 'critical'])->default('info');
            
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            
            $table->timestamp('read_at')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->json('data')->nullable();
            $table->json('sent_via')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'user_id', 'read_at']);
            $table->index(['company_id', 'urgency', 'created_at']);
            $table->index('user_id');
            $table->index('read_at');
            $table->index('dedupe_key');
            $table->index(['created_at', 'urgency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
