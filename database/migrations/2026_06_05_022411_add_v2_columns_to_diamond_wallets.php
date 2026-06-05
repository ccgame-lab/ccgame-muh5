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
            $table->tinyInteger('machine_level')->default(1)->after('ascension_level');
            $table->decimal('legacy_bonus', 5, 2)->default(0.00)->after('machine_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diamond_wallets', function (Blueprint $table) {
            $table->dropColumn(['machine_level', 'legacy_bonus']);
        });
    }
};
