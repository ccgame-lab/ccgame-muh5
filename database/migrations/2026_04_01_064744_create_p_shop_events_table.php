<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('p_shop_events', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index(); // 'boost', 'race', 'milestone'
            $table->string('name');
            $table->string('status')->default('draft')->index(); // 'draft', 'active', 'finished'
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('target')->nullable(); // e.g. 'zen'
            $table->decimal('multiplier', 8, 2)->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p_shop_events');
    }
};
