<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id_usuario');
            $table->string('nombre_usuario', 50)->unique();
            $table->string('correo_electronico', 100)->unique();
            $table->string('password_hash', 255);
            $table->dateTime('fecha_registro')->useCurrent();
            $table->boolean('es_activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};

