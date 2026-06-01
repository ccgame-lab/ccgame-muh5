<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diamond_upgrades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('machine_index');
            $table->string('upgrade_type');
            $table->integer('from_level');
            $table->integer('to_level');
            $table->integer('cost');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diamond_upgrades');
    }
};
