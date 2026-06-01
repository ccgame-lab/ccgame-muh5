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
        Schema::table('pshop_orders', function (Blueprint $table) {
            $table->string('status', 20)->default('completed')->after('server_id')->comment('Order Status');
            $table->tinyInteger('is_test')->default(0)->after('status')->comment('Test Flag for Admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pshop_orders', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_test']);
        });
    }
};
