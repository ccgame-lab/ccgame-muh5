<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users CHANGE COLUMN wpoint points INT NOT NULL DEFAULT 0');
        } else {
            // sqlite (tests): no raw ENUM/CHANGE support — rename natively.
            Schema::table('users', fn (Blueprint $table) => $table->renameColumn('wpoint', 'points'));
        }

        Schema::rename('wpoint_transactions', 'point_transactions');

        // The s1_shop_* enum migration is MySQL-only; sqlite tests don't touch
        // those tables, so their currency column stays as originally created.
        if (DB::getDriverName() === 'mysql') {
            // s1_shop_items: expand enum → update data → contract enum
            DB::statement("ALTER TABLE s1_shop_items MODIFY COLUMN currency ENUM('kc','wpoint','points') NOT NULL");
            DB::statement("UPDATE s1_shop_items SET currency='points' WHERE currency='wpoint'");
            DB::statement("ALTER TABLE s1_shop_items MODIFY COLUMN currency ENUM('kc','points') NOT NULL");

            // s1_shop_purchases: expand enum → update data → contract enum
            DB::statement("ALTER TABLE s1_shop_purchases MODIFY COLUMN currency ENUM('kc','wpoint','points') NOT NULL");
            DB::statement("UPDATE s1_shop_purchases SET currency='points' WHERE currency='wpoint'");
            DB::statement("ALTER TABLE s1_shop_purchases MODIFY COLUMN currency ENUM('kc','points') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // s1_shop_purchases: expand enum → update data → contract enum
            DB::statement("ALTER TABLE s1_shop_purchases MODIFY COLUMN currency ENUM('kc','wpoint','points') NOT NULL");
            DB::statement("UPDATE s1_shop_purchases SET currency='wpoint' WHERE currency='points'");
            DB::statement("ALTER TABLE s1_shop_purchases MODIFY COLUMN currency ENUM('kc','wpoint') NOT NULL");

            // s1_shop_items: expand enum → update data → contract enum
            DB::statement("ALTER TABLE s1_shop_items MODIFY COLUMN currency ENUM('kc','wpoint','points') NOT NULL");
            DB::statement("UPDATE s1_shop_items SET currency='wpoint' WHERE currency='points'");
            DB::statement("ALTER TABLE s1_shop_items MODIFY COLUMN currency ENUM('kc','wpoint') NOT NULL");
        }

        Schema::rename('point_transactions', 'wpoint_transactions');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users CHANGE COLUMN points wpoint INT NOT NULL DEFAULT 0');
        } else {
            Schema::table('users', fn (Blueprint $table) => $table->renameColumn('points', 'wpoint'));
        }
    }
};
