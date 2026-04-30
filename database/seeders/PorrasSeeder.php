<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class PorrasSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('porras')->insert([
            [
                'id_porra' => 1,
                'nombre' => 'Mundial 2026 - Grupo de amigos',
                'descripcion' => 'Porrilla entre amigos para el Mundial 2026',
                'id_competicion' => 1,
                'id_usuario_creador' => 1,
                'es_publica' => 0,
                'max_participantes' => 20,
                'puntos_ganador' => 1,
                'puntos_marcador' => 3,
                'puntos_campeon_grupo' => 2,
                'puntos_ganador_torneo' => 5,
                'fecha_creacion' => '2026-02-01 12:00:00',
                'estado' => 'activa',
            ],
            [
                'id_porra' => 2,
                'nombre' => 'Mundial 2026 - Oficina',
                'descripcion' => 'Porra de la oficina para el Mundial 2026',
                'id_competicion' => 1,
                'id_usuario_creador' => 2,
                'es_publica' => 1,
                'max_participantes' => 50,
                'puntos_ganador' => 1,
                'puntos_marcador' => 3,
                'puntos_campeon_grupo' => 2,
                'puntos_ganador_torneo' => 5,
                'fecha_creacion' => '2026-02-02 09:30:00',
                'estado' => 'activa',
            ],
            [
                'id_porra' => 3,
                'nombre' => 'LaLiga 25/26 - Peña del bar',
                'descripcion' => 'Porra de la peña del bar para LaLiga 25/26',
                'id_competicion' => 2,
                'id_usuario_creador' => 3,
                'es_publica' => 0,
                'max_participantes' => 30,
                'puntos_ganador' => 1,
                'puntos_marcador' => 3,
                'puntos_campeon_grupo' => 0,
                'puntos_ganador_torneo' => 5,
                'fecha_creacion' => '2026-02-05 20:00:00',
                'estado' => 'activa',
            ],
            [
                'id_porra' => 4,
                'nombre' => 'Champions 25/26 - Grupo amigos',
                'descripcion' => 'Porra para la Champions League 2025/26',
                'id_competicion' => 3,
                'id_usuario_creador' => 4,
                'es_publica' => 0,
                'max_participantes' => 25,
                'puntos_ganador' => 1,
                'puntos_marcador' => 3,
                'puntos_campeon_grupo' => 0,
                'puntos_ganador_torneo' => 5,
                'fecha_creacion' => '2026-02-10 19:00:00',
                'estado' => 'activa',
            ],
            [
                'id_porra' => 5,
                'nombre' => 'Eurocopa 2028 - Familia',
                'descripcion' => 'Porra familiar para la Eurocopa 2028',
                'id_competicion' => 5,
                'id_usuario_creador' => 5,
                'es_publica' => 0,
                'max_participantes' => 15,
                'puntos_ganador' => 1,
                'puntos_marcador' => 3,
                'puntos_campeon_grupo' => 2,
                'puntos_ganador_torneo' => 6,
                'fecha_creacion' => '2028-01-01 12:00:00',
                'estado' => 'pendiente',
            ],
        ]);
    }
}


