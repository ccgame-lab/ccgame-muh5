<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('s1_shop_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('item_slug');
            $table->unsignedInteger('server_id');
            $table->char('reference', 36)->unique();
            $table->enum('currency', ['kc', 'wpoint']);
            $table->unsignedBigInteger('amount_spent');
            $table->string('period_key', 20); // e.g. "2026-W14" or "2026-04-02"
            $table->foreignId('gm_action_id')->nullable()->constrained('gm_actions')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'item_slug', 'period_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('s1_shop_purchases');
    }
};
