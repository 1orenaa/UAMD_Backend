<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Shton FK-të rrethore pasi të dyja tabelat ekzistojnë.
    // Ne SQLite (qe perdoret ne teste) ALTER TABLE ADD FOREIGN KEY nuk
    // suportohet; e kalojme kete hap — integriteti ruhet ne prodhim (SQL Server).
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('DEPARTAMENT', function (Blueprint $table) {
            $table->foreign('PED_ID')
                  ->references('PED_ID')->on('PEDAGOG')
                  ->onDelete('set null');
        });

        Schema::table('FAKULTET', function (Blueprint $table) {
            $table->foreign('PED_ID')
                  ->references('PED_ID')->on('PEDAGOG')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('DEPARTAMENT', function (Blueprint $table) {
            $table->dropForeign(['PED_ID']);
        });

        Schema::table('FAKULTET', function (Blueprint $table) {
            $table->dropForeign(['PED_ID']);
        });
    }
};
