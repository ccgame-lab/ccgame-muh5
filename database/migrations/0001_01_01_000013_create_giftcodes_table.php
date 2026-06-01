<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('giftcodes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->integer('server_id')->nullable();
            $table->integer('limit_usage')->default(1);
            $table->integer('used_count')->default(0);
            $table->string('reward_type')->default('portal_credit');
            $table->json('reward_data')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('giftcode_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('giftcode_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['giftcode_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giftcode_redemptions');
        Schema::dropIfExists('giftcodes');
    }
};
