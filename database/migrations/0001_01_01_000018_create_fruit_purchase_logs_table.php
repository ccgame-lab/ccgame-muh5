<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fruit_purchase_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('server_id');
            $table->unsignedInteger('item_id');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('wcoin_spent');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fruit_purchase_logs');
    }
};
