<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diamond_wallets', function (Blueprint $table) {
            $table->timestamp('last_claimed_at')->nullable()->after('cap_until');
        });
    }

    public function down(): void
    {
        Schema::table('diamond_wallets', function (Blueprint $table) {
            $table->dropColumn('last_claimed_at');
        });
    }
};
