<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy mining state — one row per user on diamond_wallets
        Schema::table('diamond_wallets', function (Blueprint $table) {
            $table->timestamp('last_maintained_at')->nullable()->after('max_active_boosts');
            $table->decimal('boost_multiplier', 4, 2)->default(1.0)->after('last_maintained_at');
            $table->timestamp('boost_until')->nullable()->after('boost_multiplier');
            $table->decimal('cap_multiplier', 4, 2)->default(1.0)->after('boost_until');
            $table->timestamp('cap_until')->nullable()->after('cap_multiplier');
        });

        // Economy audit snapshots on claim logs
        Schema::table('diamond_claim_logs', function (Blueprint $table) {
            $table->integer('rate_snapshot')->nullable()->after('machine_snapshot');
            $table->integer('cap_snapshot')->nullable()->after('rate_snapshot');
            $table->decimal('efficiency_snapshot', 4, 3)->nullable()->after('cap_snapshot');
            $table->decimal('boost_snapshot', 4, 2)->nullable()->after('efficiency_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('diamond_wallets', function (Blueprint $table) {
            $table->dropColumn([
                'last_maintained_at',
                'boost_multiplier',
                'boost_until',
                'cap_multiplier',
                'cap_until',
            ]);
        });

        Schema::table('diamond_claim_logs', function (Blueprint $table) {
            $table->dropColumn([
                'rate_snapshot',
                'cap_snapshot',
                'efficiency_snapshot',
                'boost_snapshot',
            ]);
        });
    }
};
