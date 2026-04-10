<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PROGRAM_STUDIMI', function (Blueprint $table) {
            $table->bigIncrements('PROG_ID');
            $table->string('PROG_EM', 200);         // Emri i Programit
            $table->string('PROG_NIV', 50);         // Niveli: Bachelor, Master, PhD
            $table->integer('PROG_KRD');            // Kreditë totale
            $table->unsignedBigInteger('DEP_ID');   // FK → DEPARTAMENT
            $table->timestamps();

            $table->foreign('DEP_ID')->references('DEP_ID')->on('DEPARTAMENT')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PROGRAM_STUDIMI');
    }
};
