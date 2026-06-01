<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 60);
            $table->string('host', 120);
            $table->unsignedSmallInteger('port');
            $table->string('db_name', 60);
            $table->string('db_connection_name', 60)->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=Bình thường, 1=Hot, 2=Mới, 3=Đề xuất, 4=Bảo trì, 5=Sắp mở');
            $table->integer('priority')->default(0);
            $table->unsignedInteger('max_players')->default(0)->comment('0 = unlimited');
            $table->string('region', 30)->default('vn')->comment('Server region for scaling');
            $table->boolean('visible')->default(true)->comment('Hiển thị trên trang chọn server');
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
