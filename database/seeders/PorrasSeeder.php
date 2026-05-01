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
                'descripcion' => 'Porran entre amigos para el Mundial 2026',
                'id_competicion' => 1,
                'id_usuario_creador' => 2,
                'es_publica' => 0,
                'max_participantes' => 20,
                'puntos_ganador' => 1,
                'puntos_marcador' => 3,
                'puntos_campeon_grupo' => 3,
                'puntos_ganador_torneo' => 6,
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
                'nombre' => 'Mundial 2026 - Bar',
                'descripcion' => 'Porra del bar para el Mundial 2026',
                'id_competicion' => 1,
                'id_usuario_creador' => 3,
                'es_publica' => 1,
                'max_participantes' => 50,
                'puntos_ganador' => 1,
                'puntos_marcador' => 3,
                'puntos_campeon_grupo' => 5,
                'puntos_ganador_torneo' => 10,
                'fecha_creacion' => '2026-02-03 09:30:00',
                'estado' => 'activa',
            ],
             [
                'id_porra' => 4,
                'nombre' => 'Mundial 2026 - Familia',
                'descripcion' => 'Porra familiar para el Mundial 2026',
                'id_competicion' => 1,
                'id_usuario_creador' => 5,
                'es_publica' => 0,
                'max_participantes' => 10,
                'puntos_ganador' => 1,
                'puntos_marcador' => 3,
                'puntos_campeon_grupo' => 2,
                'puntos_ganador_torneo' => 5,
                'fecha_creacion' => '2026-02-04 10:00:00',
                'estado' => 'activa',
             ],
        ]);
    }
}


