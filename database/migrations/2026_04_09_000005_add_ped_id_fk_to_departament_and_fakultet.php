<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Shton FK-të rrethore pasi të dyja tabelat ekzistojnë
    public function up(): void
    {
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
        Schema::table('DEPARTAMENT', function (Blueprint $table) {
            $table->dropForeign(['PED_ID']);
        });

        Schema::table('FAKULTET', function (Blueprint $table) {
            $table->dropForeign(['PED_ID']);
        });
    }
};
