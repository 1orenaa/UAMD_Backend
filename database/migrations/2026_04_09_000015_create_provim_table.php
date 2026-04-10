<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PROVIM', function (Blueprint $table) {
            $table->bigIncrements('PRV_ID');
            $table->date('PRV_DBA');                    // Data e provimit
            $table->string('PRV_TIP', 30);              // Lloji: "Final", "Kolokium", "Rikompletim"
            $table->dateTime('PRV_DTFILL');             // Ora e fillimit
            $table->dateTime('PRV_DTMBA');              // Ora e mbarimit
            $table->unsignedBigInteger('SEK_ID');       // FK → SEKSION
            $table->unsignedBigInteger('STD_ID')->nullable(); // FK → STUDENT (kush e mbikëqyr)
            $table->timestamps();

            $table->foreign('SEK_ID')->references('SEK_ID')->on('SEKSION')->onDelete('cascade');
            $table->foreign('STD_ID')->references('STD_ID')->on('STUDENT')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PROVIM');
    }
};
