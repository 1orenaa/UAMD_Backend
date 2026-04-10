<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('VIT_AKADEMIK', function (Blueprint $table) {
            $table->bigIncrements('VIT_ID');
            $table->string('VIT_EM', 20);        // p.sh. "2025/2026"
            $table->date('VIT_DT_FILL');         // Data e fillimit
            $table->date('VIT_DT_MBR');          // Data e mbarimit
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('VIT_AKADEMIK');
    }
};
