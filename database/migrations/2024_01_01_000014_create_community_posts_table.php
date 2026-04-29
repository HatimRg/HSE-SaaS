<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->text('content');
            $table->json('image_paths')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('mentions')->nullable();
            
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_announcement')->default(false);
            
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            
            $table->timestamp('edited_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'is_pinned', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index('user_id');
            $table->index('is_announcement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_posts');
    }
};
