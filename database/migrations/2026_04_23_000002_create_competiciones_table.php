<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('competiciones', function (Blueprint $table) {
            $table->increments('id_competicion');
            $table->string('nombre', 100);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('tipo_torneo', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competiciones');
    }
};