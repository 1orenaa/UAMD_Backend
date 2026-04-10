<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PEDAGOG', function (Blueprint $table) {
            $table->bigIncrements('PED_ID');
            $table->string('PED_EM', 100);          // Emri
            $table->string('PED_MR', 100);          // Mbiemri
            $table->string('PED_EMAIL', 150)->unique();
            $table->string('PED_TIT', 50)->nullable(); // Titulli (Prof., Dr., ...)
            $table->date('PED_DTL')->nullable();    // Data e lindjes
            $table->unsignedBigInteger('DEP_ID');   // FK → DEPARTAMENT

            $table->timestamps();

            $table->foreign('DEP_ID')->references('DEP_ID')->on('DEPARTAMENT')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PEDAGOG');
    }
};
