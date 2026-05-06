<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PPE issuance tracking — who received what, when, and how many.
 * Also adds PPE stock tracking per project.
 */
return new class extends Migration
{
    public function up(): void
    {
        // PPE stock per project (inventory)
        Schema::create('ppe_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('ppe_item_id')->constrained()->onDelete('cascade');

            $table->integer('quantity')->default(0);
            $table->integer('min_stock_level')->default(5);
            $table->string('storage_location')->nullable();

            $table->timestamps();

            $table->unique(['project_id', 'ppe_item_id']);
            $table->index(['company_id', 'project_id']);
        });

        // Worker PPE issue records
        Schema::create('worker_ppe_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('worker_id')->constrained()->onDelete('cascade');
            $table->foreignId('ppe_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->integer('quantity')->default(1);
            $table->enum('size', ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'])->nullable();
            $table->date('issued_at');
            $table->date('expected_return_date')->nullable();
            $table->date('returned_at')->nullable();
            $table->enum('condition_on_return', ['good', 'worn', 'damaged'])->nullable();

            $table->foreignId('issued_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['worker_id', 'ppe_item_id']);
            $table->index(['company_id', 'project_id']);
            $table->index('issued_at');
            $table->index('expected_return_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_ppe_issues');
        Schema::dropIfExists('ppe_stocks');
    }
};
