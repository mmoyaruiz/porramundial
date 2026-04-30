<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class PronosticosCampeonesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pronosticos_campeones')->insert([
            // Porra 1 (Mundial - Amigos)
            [
                'id_pronostico_campeon' => 1,
                'id_usuario' => 1,
                'id_porra' => 1,
                'tipo_pronostico' => 'competicion',
                'grupo' => null,
                'equipo_pronosticado' => 'Brasil',
                'puntos_obtenidos' => 0,
                'fecha_creacion' => '2026-05-20 12:00:00',
            ],
            [
                'id_pronostico_campeon' => 2,
                'id_usuario' => 2,
                'id_porra' => 1,
                'tipo_pronostico' => 'competicion',
                'grupo' => null,
                'equipo_pronosticado' => 'España',
                'puntos_obtenidos' => 0,
                'fecha_creacion' => '2026-05-20 12:05:00',
            ],
            [
                'id_pronostico_campeon' => 3,
                'id_usuario' => 3,
                'id_porra' => 1,
                'tipo_pronostico' => 'grupo',
                'grupo' => 'A',
                'equipo_pronosticado' => 'Francia',
                'puntos_obtenidos' => 0,
                'fecha_creacion' => '2026-05-20 12:10:00',
            ],

            // Porra 2 (Mundial - Oficina)
            [
                'id_pronostico_campeon' => 4,
                'id_usuario' => 2,
                'id_porra' => 2,
                'tipo_pronostico' => 'competicion',
                'grupo' => null,
                'equipo_pronosticado' => 'Argentina',
                'puntos_obtenidos' => 0,
                'fecha_creacion' => '2026-05-21 09:00:00',
            ],
            [
                'id_pronostico_campeon' => 5,
                'id_usuario' => 5,
                'id_porra' => 2,
                'tipo_pronostico' => 'grupo',
                'grupo' => 'B',
                'equipo_pronosticado' => 'Alemania',
                'puntos_obtenidos' => 0,
                'fecha_creacion' => '2026-05-21 09:10:00',
            ],

            // Porra 3 (LaLiga - Peña del bar)
            [
                'id_pronostico_campeon' => 6,
                'id_usuario' => 1,
                'id_porra' => 3,
                'tipo_pronostico' => 'competicion',
                'grupo' => null,
                'equipo_pronosticado' => 'Barcelona',
                'puntos_obtenidos' => 0,
                'fecha_creacion' => '2025-08-20 20:00:00',
            ],
            [
                'id_pronostico_campeon' => 7,
                'id_usuario' => 3,
                'id_porra' => 3,
                'tipo_pronostico' => 'competicion',
                'grupo' => null,
                'equipo_pronosticado' => 'Real Madrid',
                'puntos_obtenidos' => 0,
                'fecha_creacion' => '2025-08-20 20:05:00',
            ],
        ]);
    }
}

