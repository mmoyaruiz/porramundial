<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: partidos
 * Relación:
 * - Un partido pertenece a una única competición (1:N).
 */
return new class extends Migration {

    public function up(): void
    {
        Schema::create('partidos', function (Blueprint $table) {

            // Clave primaria autoincremental
            $table->increments('id_partido');

            // Identificador externo del partido en la API (football-data.org)
            // Puede ser NULL, pero si existe debe ser único
            $table->unsignedInteger('api_match_id')
                  ->nullable()
                  ->unique();

            // Clave foránea hacia competiciones
            $table->unsignedInteger('id_competicion');

            // Fecha y hora del partido (hora local)
            $table->dateTime('fecha_hora');

            // Estado del partido
            $table->enum('estado', [
                'programado',
                'en_juego',
                'finalizado'
            ])->default('programado');

            // Información de fase y grupo del torneo
            $table->string('fase', 30)->nullable();   // GROUP_STAGE, QUARTER_FINALS, etc.
            $table->char('grupo', 1)->nullable();     // A, B, C... (NULL en eliminatorias)

            // Equipo local
            $table->string('equipo_local_nombre', 100);
            $table->string('equipo_local_shortname', 50);
            $table->string('equipo_local_crest_url', 255)->nullable();

            // Equipo visitante
            $table->string('equipo_visitante_nombre', 100);
            $table->string('equipo_visitante_shortname', 50);
            $table->string('equipo_visitante_crest_url', 255)->nullable();

            // Resultado final del partido (NULL si no ha terminado)
            $table->unsignedTinyInteger('goles_local')->nullable();
            $table->unsignedTinyInteger('goles_visitante')->nullable();

            // Identificadores TLA de los equipos (ESP, BRA, ARG...)
            $table->char('equipo_local_tla', 3);
            $table->char('equipo_visitante_tla', 3);

            // Definición de la clave foránea
            $table->foreign('id_competicion')
                  ->references('id_competicion')
                  ->on('competiciones')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidos');
    }
};

