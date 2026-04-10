<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SALLE', function (Blueprint $table) {
            $table->bigIncrements('SALLE_ID');
            $table->string('SALLE_EM', 50);             // p.sh. "A-201", "Lab-3"
            $table->integer('SALLE_KAP');               // Kapaciteti
            $table->unsignedBigInteger('FAK_ID');       // FK → FAKULTET
            $table->timestamps();

            $table->foreign('FAK_ID')->references('FAK_ID')->on('FAKULTET')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SALLE');
    }
};
