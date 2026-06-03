<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users CHANGE COLUMN wpoint points INT NOT NULL DEFAULT 0");
        Schema::rename('wpoint_transactions', 'point_transactions');

        // s1_shop_items: expand enum → update data → contract enum
        DB::statement("ALTER TABLE s1_shop_items MODIFY COLUMN currency ENUM('kc','wpoint','points') NOT NULL");
        DB::statement("UPDATE s1_shop_items SET currency='points' WHERE currency='wpoint'");
        DB::statement("ALTER TABLE s1_shop_items MODIFY COLUMN currency ENUM('kc','points') NOT NULL");

        // s1_shop_purchases: expand enum → update data → contract enum
        DB::statement("ALTER TABLE s1_shop_purchases MODIFY COLUMN currency ENUM('kc','wpoint','points') NOT NULL");
        DB::statement("UPDATE s1_shop_purchases SET currency='points' WHERE currency='wpoint'");
        DB::statement("ALTER TABLE s1_shop_purchases MODIFY COLUMN currency ENUM('kc','points') NOT NULL");
    }

    public function down(): void
    {
        // s1_shop_purchases: expand enum → update data → contract enum
        DB::statement("ALTER TABLE s1_shop_purchases MODIFY COLUMN currency ENUM('kc','wpoint','points') NOT NULL");
        DB::statement("UPDATE s1_shop_purchases SET currency='wpoint' WHERE currency='points'");
        DB::statement("ALTER TABLE s1_shop_purchases MODIFY COLUMN currency ENUM('kc','wpoint') NOT NULL");

        // s1_shop_items: expand enum → update data → contract enum
        DB::statement("ALTER TABLE s1_shop_items MODIFY COLUMN currency ENUM('kc','wpoint','points') NOT NULL");
        DB::statement("UPDATE s1_shop_items SET currency='wpoint' WHERE currency='points'");
        DB::statement("ALTER TABLE s1_shop_items MODIFY COLUMN currency ENUM('kc','wpoint') NOT NULL");

        Schema::rename('point_transactions', 'wpoint_transactions');
        DB::statement("ALTER TABLE users CHANGE COLUMN points wpoint INT NOT NULL DEFAULT 0");
    }
};
