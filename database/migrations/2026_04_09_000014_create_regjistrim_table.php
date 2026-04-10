<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('REGJISTRIM', function (Blueprint $table) {
            $table->bigIncrements('REGJL_ID');
            $table->date('REGJL_DT');                           // Data e regjistrimit
            $table->string('REGJL_STATUS', 20);                 // Aktiv, Larguar, etj.
            $table->decimal('REGJL_NOTA', 4, 2)->nullable();    // Nota përfundimtare
            $table->decimal('REGJL_PRV_NOR', 4, 2)->nullable(); // Nota normale e provimit
            $table->decimal('REGJL_PRV_FIN', 4, 2)->nullable(); // Nota finale e provimit
            $table->boolean('REGJL_PRU')->default(false);       // A e ka kryer provimin?
            $table->unsignedBigInteger('STD_ID');               // FK → STUDENT
            $table->unsignedBigInteger('SEK_ID');               // FK → SEKSION
            $table->timestamps();

            $table->foreign('STD_ID')->references('STD_ID')->on('STUDENT')->onDelete('cascade');
            $table->foreign('SEK_ID')->references('SEK_ID')->on('SEKSION')->onDelete('no action');

            $table->unique(['STD_ID', 'SEK_ID']); // Studenti regjistrohet vetëm 1 herë në seksion
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('REGJISTRIM');
    }
};
