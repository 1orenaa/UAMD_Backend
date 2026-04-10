<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SEKSION', function (Blueprint $table) {
            $table->bigIncrements('SEK_ID');
            $table->date('SEK_DATA');                        // Data e seksionit
            $table->dateTime('SEK_DRAFILL');                 // Ora e fillimit
            $table->dateTime('SEK_DRAMBIA');                 // Ora e mbarimit
            $table->unsignedBigInteger('PED_ID');            // FK → PEDAGOG
            $table->unsignedBigInteger('SALLE_ID');          // FK → SALLE
            $table->unsignedBigInteger('LEN_ID');            // FK → LENDE
            $table->unsignedBigInteger('SEM_ID');            // FK → SEMESTER
            $table->timestamps();

            $table->foreign('PED_ID')->references('PED_ID')->on('PEDAGOG')->onDelete('no action');
            $table->foreign('SALLE_ID')->references('SALLE_ID')->on('SALLE')->onDelete('no action');
            $table->foreign('LEN_ID')->references('LEN_ID')->on('LENDE')->onDelete('no action');
            $table->foreign('SEM_ID')->references('SEM_ID')->on('SEMESTER')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SEKSION');
    }
};
