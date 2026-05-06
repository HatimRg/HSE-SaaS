<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Training participants — tracks who attended which session.
 * Certificates are stored in worker_documents (type = training_certificate).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('worker_id')->constrained()->onDelete('cascade');

            $table->enum('status', ['registered', 'attended', 'absent', 'excused'])->default('registered');
            $table->decimal('score', 5, 2)->nullable(); // test score if applicable
            $table->enum('result', ['pass', 'fail', 'incomplete'])->nullable();
            $table->text('feedback')->nullable();

            $table->timestamps();

            $table->unique(['training_session_id', 'worker_id']);
            $table->index(['company_id', 'worker_id']);
            $table->index(['training_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_participants');
    }
};
