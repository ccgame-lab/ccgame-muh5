<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diamond_claim_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('machine_index');
            $table->unsignedBigInteger('amount_claimed');
            $table->unsignedInteger('production_seconds');
            $table->unsignedSmallInteger('machine_level')->default(1);
            $table->unsignedSmallInteger('speed_level')->default(1);
            $table->unsignedSmallInteger('storage_level')->default(1);
            $table->unsignedSmallInteger('efficiency_level')->default(1);
            $table->json('machine_snapshot')->nullable()->comment('Full machine state at claim time for audit');
            $table->boolean('is_lucky_drop')->default(false);
            $table->string('drop_item_id')->nullable();
            $table->string('drop_seed')->nullable();
            $table->string('drop_table_version')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->unsignedInteger('server_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diamond_claim_logs');
    }
};
