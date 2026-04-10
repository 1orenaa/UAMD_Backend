<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KURRIKULE', function (Blueprint $table) {
            $table->bigIncrements('KURR_ID');
            $table->integer('KURR_KRED');                   // Kreditë e lëndës në kurrikul
            $table->integer('KURR_PARASHIK')->nullable();   // Orë të parashikuara
            $table->unsignedBigInteger('LEN_ID');           // FK → LENDE
            $table->unsignedBigInteger('PROG_ID');          // FK → PROGRAM_STUDIMI
            $table->timestamps();

            $table->foreign('LEN_ID')->references('LEN_ID')->on('LENDE')->onDelete('cascade');
            $table->foreign('PROG_ID')->references('PROG_ID')->on('PROGRAM_STUDIMI')->onDelete('cascade');

            $table->unique(['LEN_ID', 'PROG_ID']); // Lënda mund të jetë vetëm 1 herë në program
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KURRIKULE');
    }
};
