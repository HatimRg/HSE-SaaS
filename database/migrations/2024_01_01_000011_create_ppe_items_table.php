<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['head', 'eye_face', 'hearing', 'respiratory', 'hand', 'foot', 'body', 'fall_protection', 'high_visibility']);
            
            $table->json('size_options')->nullable();
            $table->json('color_options')->nullable();
            
            $table->string('supplier')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->integer('reorder_level')->default(10);
            
            $table->json('specifications')->nullable();
            $table->json('certifications')->nullable();
            $table->integer('shelf_life_months')->nullable();
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'is_active']);
            $table->index('category');
            $table->fullText('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppe_items');
    }
};
