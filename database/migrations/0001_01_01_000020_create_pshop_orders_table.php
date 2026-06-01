<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pshop_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gifted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('item_key');
            $table->string('currency');
            $table->integer('amount_spent');
            $table->integer('quantity')->default(1);
            $table->integer('server_id');
            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->foreignId('gm_action_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pshop_orders');
    }
};
