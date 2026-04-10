<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('FAKULTET', function (Blueprint $table) {
            $table->bigIncrements('FAK_ID');
            $table->string('FAK_EM', 200);           // Emri i Fakultetit
            $table->unsignedBigInteger('PED_ID')->nullable(); // Dekani (shtohet pas PEDAGOG)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('FAKULTET');
    }
};
