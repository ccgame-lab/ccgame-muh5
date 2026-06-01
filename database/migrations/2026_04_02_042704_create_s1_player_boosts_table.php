<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('s1_player_boosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('boost_category', ['regen', 'daily_cap', 'offline']);
            $table->decimal('value', 10, 2); // e.g. 0.20 for regen, 200000 for daily_cap, 1 for offline
            $table->string('source_slug'); // item slug that granted this boost
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'boost_category', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('s1_player_boosts');
    }
};
