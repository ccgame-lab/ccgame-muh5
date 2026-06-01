<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hall_of_fame_legends', function (Blueprint $table) {
            $table->id();

            // Server info
            $table->string('server_name');              // e.g. "S1 - XỨ MỆNH KHỞI NGUYÊN"
            $table->string('server_key');               // e.g. "s1" — used for grouping
            $table->enum('server_status', ['completed', 'ongoing'])->default('completed');

            // King info
            $table->enum('category', ['combat', 'donate']);
            $table->string('category_label');           // e.g. "VUA LỰC CHIẾN"
            $table->string('player_name')->nullable();  // null = mystery (ongoing)
            $table->bigInteger('score_value')->nullable();
            $table->string('score_label');              // e.g. "Chiến Lực", "WPoint"

            // Rewards displayed in the card
            $table->json('rewards')->nullable();        // ["💎 300K KC", "🪙 300 WCoin", ...]

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hall_of_fame_legends');
    }
};
