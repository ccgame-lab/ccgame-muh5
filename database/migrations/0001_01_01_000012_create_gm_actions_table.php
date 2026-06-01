<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gm_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('action_uuid')->unique();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->cascadeOnDelete();
            $table->unsignedInteger('server_id')->nullable();
            $table->string('action_type');
            $table->string('target_user');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->string('ip_address', 45)->nullable();
            $table->json('response')->nullable();
            $table->float('duration_ms')->nullable();
            $table->timestamp('executing_started_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gm_actions');
    }
};
