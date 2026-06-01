<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('reference');
            $table->unsignedInteger('amount');
            $table->text('error_message');
            $table->text('refund_error_message');
            $table->json('meta')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('reference');
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_transactions');
    }
};
