<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('STUDENT_PROGRAM', function (Blueprint $table) {
            $table->bigIncrements('STD_PROG_ID');
            $table->date('STD_PRG_DTF');                    // Data e fillimit
            $table->date('STD_PRG_DTM')->nullable();        // Data e mbarimit
            $table->string('STD_PROG_STATUS', 20);          // Aktiv, Larguar, Diplomuar
            $table->unsignedBigInteger('STD_ID');           // FK → STUDENT
            $table->unsignedBigInteger('PROG_ID');          // FK → PROGRAM_STUDIMI
            $table->timestamps();

            $table->foreign('STD_ID')->references('STD_ID')->on('STUDENT')->onDelete('cascade');
            $table->foreign('PROG_ID')->references('PROG_ID')->on('PROGRAM_STUDIMI')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('STUDENT_PROGRAM');
    }
};
