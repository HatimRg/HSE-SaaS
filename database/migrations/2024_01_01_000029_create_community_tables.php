<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Community module — external stakeholder engagement.
 * 
 * community_reports: formal complaints, meetings, external incidents
 * community_post_comments: comments on internal community posts
 * community_post_reactions: likes/reactions on posts
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->enum('type', ['complaint', 'meeting', 'incident', 'suggestion', 'inquiry']);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');

            $table->string('reporter_name')->nullable();
            $table->string('reporter_contact')->nullable();
            $table->string('reporter_organization')->nullable();

            $table->text('description');
            $table->string('location')->nullable();
            $table->datetime('reported_at');

            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->json('attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'project_id', 'type']);
            $table->index(['company_id', 'status']);
            $table->index('reported_at');
        });

        // Comments on community posts
        Schema::create('community_post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->constrained('community_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('community_post_comments')->onDelete('cascade'); // threaded

            $table->text('content');
            $table->timestamp('edited_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_id', 'created_at']);
            $table->index('parent_id');
        });

        // Reactions (likes, etc.) on community posts
        Schema::create('community_post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('community_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['like', 'celebrate', 'support', 'insightful', 'heart'])->default('like');

            $table->timestamps();

            $table->unique(['post_id', 'user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_post_reactions');
        Schema::dropIfExists('community_post_comments');
        Schema::dropIfExists('community_reports');
    }
};
