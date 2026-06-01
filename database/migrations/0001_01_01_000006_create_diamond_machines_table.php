<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diamond_machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('machine_index');
            $table->integer('level')->default(1);
            $table->integer('speed_level')->default(1);
            $table->integer('storage_level')->default(1);
            $table->integer('efficiency_level')->default(1);
            $table->integer('base_rate')->default(50);
            $table->integer('capacity')->default(200);
            $table->decimal('speed_multiplier', 8, 4)->default(1.0000);
            $table->integer('storage_limit');
            $table->timestamp('last_claim_at');
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'machine_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diamond_machines');
    }
};
