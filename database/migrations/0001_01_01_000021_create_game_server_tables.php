<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Game server tables used by GmApiService.
 *
 * In production, these tables live on separate game-server DB connections
 * and are managed by the game engine, not by Laravel migrations.
 *
 * This migration only runs in the testing environment so that
 * RefreshDatabase can properly create and roll back these tables
 * without leaving orphaned DDL artifacts in SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            Schema::create('feecallback', function ($table) {
                $table->id();
                $table->integer('serverid');
                $table->string('openid', 50);
                $table->integer('itemid');
                $table->string('actor_id', 50);
            });

            Schema::create('actors', function ($table) {
                $table->string('actorid', 50)->primary();
                $table->string('actorname', 50);
                $table->string('accountname', 50);
                $table->integer('serverindex');
                $table->integer('level')->default(1);
                $table->integer('job')->default(1);
                $table->integer('sex')->default(0);
                $table->bigInteger('gold')->default(0);
                $table->bigInteger('yuanbao')->default(0);
                $table->integer('vip_level')->default(0);
                $table->bigInteger('totalpower')->default(0);
            });

            Schema::create('gmcmd', function ($table) {
                $table->id();
                $table->integer('serverid');
                $table->integer('cmdid');
                $table->string('cmd', 256);
                $table->string('param1', 256)->default('');
                $table->string('param2', 256)->default('');
                $table->string('param3', 256)->default('');
                $table->string('param4', 256)->default('');
                $table->string('param5', 256)->default('');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gmcmd');
        Schema::dropIfExists('actors');
        Schema::dropIfExists('feecallback');
    }
};
