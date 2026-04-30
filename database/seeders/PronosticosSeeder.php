<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;




class PronosticosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pronosticos')->insert([
            

            // Porra 3, Partidos de LaLiga
            [
                'id_pronostico' => 8,
                'id_usuario' => 1,
                'id_porra' => 3,
                'id_partido' => 1,
                'goles_local_pronosticados' => 2,
                'goles_visitante_pronosticados' => 2,
                'puntos_obtenidos' => 0,
                'fecha_creacion' => '2025-09-01 09:00:00',
            ],
            [
                'id_pronostico' => 9,
                'id_usuario' => 3,
                'id_porra' => 3,
                'id_partido' => 1,
                'goles_local_pronosticados' => 1,
                'goles_visitante_pronosticados' => 2,
                'puntos_obtenidos' => 0,
                'fecha_creacion' => '2025-09-01 09:05:00',
            ],
            [
                'id_pronostico' => 10,
                'id_usuario' => 6,
                'id_porra' => 3,
                'id_partido' => 2,
                'goles_local_pronosticados' => 1,
                'goles_visitante_pronosticados' => 1,
                'puntos_obtenidos' => 0,
                'fecha_creacion' => '2025-09-01 09:10:00',
            ],
        ]);
    }
}

