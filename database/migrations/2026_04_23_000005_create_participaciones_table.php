<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('participaciones', function (Blueprint $table) {
            $table->increments('id_participacion');

            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_porra');

            $table->dateTime('fecha_union')->useCurrent();
            $table->boolean('es_admin')->default(false);
            $table->integer('puntos')->default(0);
            $table->integer('posicion')->nullable();

            $table->unique(['id_usuario', 'id_porra']);

            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            $table->foreign('id_porra')->references('id_porra')->on('porras');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participaciones');
    }
};