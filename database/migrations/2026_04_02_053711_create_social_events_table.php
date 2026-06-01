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
        Schema::create('social_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('username')->nullable();
            $table->unsignedInteger('server_id')->nullable()->index();
            $table->string('event_type')->index(); // e.g., recharge, purchase_item, milestone
            $table->string('template')->nullable(); // e.g., user_recharge
            $table->json('metadata')->nullable();
            $table->integer('priority')->default(0); // For sorting or deciding what to show
            $table->timestamps();

            // Helpful composite index for fetching the feed quickly
            $table->index(['server_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_events');
    }
};
