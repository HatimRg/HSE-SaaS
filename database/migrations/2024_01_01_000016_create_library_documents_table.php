<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('folder_id')->nullable()->constrained('library_folders')->onDelete('set null');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->json('keywords')->nullable();
            
            $table->string('public_token', 32)->nullable()->unique();
            $table->boolean('is_encrypted')->default(false);
            
            $table->integer('version')->default(1);
            $table->foreignId('previous_version_id')->nullable()->constrained('library_documents')->onDelete('set null');
            
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'folder_id']);
            $table->index(['company_id', 'created_at']);
            $table->index('folder_id');
            $table->index('public_token');
            $table->fullText('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_documents');
    }
};
