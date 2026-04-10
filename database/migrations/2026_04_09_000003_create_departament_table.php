<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PED_ID (kryetari i departamentit) shtohet me migration të veçantë
        // pasi PEDAGOG varet nga DEPARTAMENT (varësi rrethore)
        Schema::create('DEPARTAMENT', function (Blueprint $table) {
            $table->bigIncrements('DEP_ID');
            $table->string('DEP_EM', 200);                          // Emri i Departamentit
            $table->unsignedBigInteger('FAK_ID');                   // FK → FAKULTET
            $table->unsignedBigInteger('PED_ID')->nullable();       // FK → PEDAGOG (nullable deri sa krijohet tabela)
            $table->timestamps();

            $table->foreign('FAK_ID')->references('FAK_ID')->on('FAKULTET')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DEPARTAMENT');
    }
};
