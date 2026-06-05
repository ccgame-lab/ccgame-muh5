<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tom_purchase_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('item_id', 64);
            $table->unsignedInteger('server_id')->nullable()->index();
            $table->unsignedSmallInteger('tom_spent');
            $table->string('idempotency_key', 128)->unique();
            $table->string('greenjade_exchange_id', 36)->nullable();
            $table->unsignedInteger('remaining_tom')->nullable();
            // pending → spent (GJ deducted) → delivered (GM command dispatched)
            // pending → failed (GJ rejected)
            // spent → delivery_failed (GM retries exhausted)
            $table->string('status', 20)->default('pending')->index();
            $table->string('failure_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tom_purchase_logs');
    }
};
