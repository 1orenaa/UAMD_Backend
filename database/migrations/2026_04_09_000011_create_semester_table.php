<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SEMESTER', function (Blueprint $table) {
            $table->bigIncrements('SEM_ID');
            $table->integer('SEM_NR');              // Numri i semestrit (1-8)
            $table->date('SEM_DT_FILL');            // Data e fillimit
            $table->date('SEM_DT_MBR');             // Data e mbarimit
            $table->unsignedBigInteger('VIT_ID');   // FK → VIT_AKADEMIK
            $table->timestamps();

            $table->foreign('VIT_ID')->references('VIT_ID')->on('VIT_AKADEMIK')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SEMESTER');
    }
};
