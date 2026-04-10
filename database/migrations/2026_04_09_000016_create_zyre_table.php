<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ZYRE është specializim i SALLE — çdo zyrë është edhe një sallë
        Schema::create('ZYRE', function (Blueprint $table) {
            $table->unsignedBigInteger('SALLE_ID')->primary(); // PK + FK → SALLE
            $table->unsignedBigInteger('PED_ID');              // FK → PEDAGOG
            $table->timestamps();

            $table->foreign('SALLE_ID')->references('SALLE_ID')->on('SALLE')->onDelete('cascade');
            $table->foreign('PED_ID')->references('PED_ID')->on('PEDAGOG')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ZYRE');
    }
};
