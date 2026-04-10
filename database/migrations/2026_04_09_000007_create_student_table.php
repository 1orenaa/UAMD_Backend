<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('STUDENT', function (Blueprint $table) {
            $table->bigIncrements('STD_ID');
            $table->string('STD_EM', 100);              // Emri
            $table->string('STD_MB', 100);              // Mbiemri
            $table->string('STD_EMAIL', 150)->unique();
            $table->date('STD_DTL')->nullable();        // Data e lindjes
            $table->string('STD_GJINI', 1)->nullable(); // 'M' / 'F'
            $table->unsignedBigInteger('DEP_ID');       // FK → DEPARTAMENT
            $table->timestamps();

            $table->foreign('DEP_ID')->references('DEP_ID')->on('DEPARTAMENT')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('STUDENT');
    }
};
