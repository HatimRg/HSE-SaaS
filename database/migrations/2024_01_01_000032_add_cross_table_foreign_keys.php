<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add foreign key constraints that cross migration boundaries.
 * These must run AFTER all referenced tables exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hse_events', function (Blueprint $table) {
            $table->foreign('risk_item_id')->references('id')->on('risk_items')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('hse_events', function (Blueprint $table) {
            $table->dropForeign(['risk_item_id']);
        });
    }
};
