<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('LENDE', function (Blueprint $table) {
            $table->bigIncrements('LEN_ID');
            $table->string('LEN_EM', 200);          // Emri i Lëndës
            $table->string('LEN_KOD', 20)->unique();// Kodi unik, p.sh. "PRG202"
            $table->integer('LEN_KRED');            // Kredite ECTS
            $table->unsignedBigInteger('DEP_ID');   // FK → DEPARTAMENT
            $table->timestamps();

            $table->foreign('DEP_ID')->references('DEP_ID')->on('DEPARTAMENT')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LENDE');
    }
};
