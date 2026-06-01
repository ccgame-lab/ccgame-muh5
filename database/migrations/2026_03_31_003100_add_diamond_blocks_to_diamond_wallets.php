<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('diamond_wallets', function (Blueprint $table) {
            $table->unsignedBigInteger('diamond_blocks')->default(0)->after('balance')->comment('Lưu trữ Tinh Thạch KC nén từ Ingame');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diamond_wallets', function (Blueprint $table) {
            $table->dropColumn('diamond_blocks');
        });
    }
};
