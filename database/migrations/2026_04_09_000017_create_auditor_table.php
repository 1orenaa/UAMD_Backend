<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AUDITOR — caktimi i sallave për mbajtjen e provimeve
        Schema::create('AUDITOR', function (Blueprint $table) {
            $table->unsignedBigInteger('SALLE_ID'); // PK,FK → SALLE
            $table->unsignedBigInteger('SEK_ID');   // PK,FK → SEKSION
            $table->timestamps();

            $table->primary(['SALLE_ID', 'SEK_ID']);

            $table->foreign('SALLE_ID')->references('SALLE_ID')->on('SALLE')->onDelete('cascade');
            $table->foreign('SEK_ID')->references('SEK_ID')->on('SEKSION')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AUDITOR');
    }
};
