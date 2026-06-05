<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('diamond_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module_type', 32); // speed_core | durability_plate | overflow_tank | lucky_crystal
            $table->tinyInteger('slot_index')->nullable(); // null=inventory, 0/1/2=equipped
            $table->timestamp('acquired_at')->useCurrent();
            $table->timestamps();
            $table->index(['user_id', 'slot_index']);
        });

        // Seeder for legacy data
        DB::table('diamond_wallets')
            ->join('diamond_machines', 'diamond_machines.user_id', '=', 'diamond_wallets.user_id')
            ->select(
                'diamond_wallets.user_id',
                DB::raw('FLOOR(MAX(diamond_machines.speed_level) / 2) as ml'),
                DB::raw('diamond_wallets.ascension_level * 5 as lb')
            )
            ->groupBy('diamond_wallets.user_id', 'diamond_wallets.ascension_level')
            ->orderBy('diamond_wallets.user_id')
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('diamond_wallets')->where('user_id', $row->user_id)->update([
                        'machine_level' => max(1, min(3, (int) $row->ml)),
                        'legacy_bonus' => $row->lb,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diamond_modules');
    }
};
