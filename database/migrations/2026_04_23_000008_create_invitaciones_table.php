<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invitaciones', function (Blueprint $table) {
            $table->increments('id_invitacion');

            $table->unsignedInteger('id_porra');
            $table->unsignedInteger('id_usuario_invitador');

            $table->string('usuario_destino', 50)->nullable();
            $table->string('email_destino', 100)->nullable();
            $table->string('estado', 20)->default('pendiente');

            $table->dateTime('fecha_envio')->useCurrent();
            $table->dateTime('fecha_respuesta')->nullable();

            $table->foreign('id_porra')->references('id_porra')->on('porras');
            $table->foreign('id_usuario_invitador')->references('id_usuario')->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitaciones');
    }
};