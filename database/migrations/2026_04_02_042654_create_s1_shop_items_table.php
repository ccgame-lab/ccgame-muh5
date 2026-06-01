<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('s1_shop_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->enum('track', ['whale', 'casual']);
            $table->enum('currency', ['kc', 'wpoint']);
            $table->unsignedBigInteger('price');
            $table->tinyInteger('unlock_week')->default(1);
            $table->enum('limit_type', ['weekly', 'daily']);
            $table->tinyInteger('limit_count');
            $table->enum('delivery_type', ['boost', 'mail', 'claim_reset', 'boost_slot']);
            $table->json('delivery_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('s1_shop_items');
    }
};
