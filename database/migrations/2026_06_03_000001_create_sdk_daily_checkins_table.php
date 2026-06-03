<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdk_daily_checkins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('checked_at');
            $table->integer('streak')->default(0);
            $table->boolean('reward_given')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdk_daily_checkins');
    }
};
