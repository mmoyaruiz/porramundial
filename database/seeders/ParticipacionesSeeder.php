<?php



namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class ParticipacionesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('participaciones')->insert([
            // Porra 1 (Mundial - Amigos)
            [
                'id_participacion' => 1,
                'id_usuario' => 2,
                'id_porra' => 1,
                'fecha_union' => '2026-02-01 12:05:00',
                'es_admin' => 1,
                'puntos' => 10,
                'posicion' => 1,
            ],
            [
                'id_participacion' => 2,
                'id_usuario' => 3,
                'id_porra' => 1,
                'fecha_union' => '2026-02-01 12:10:00',
                'es_admin' => 0,
                'puntos' => 8,
                'posicion' => 2,
            ],
            [
                'id_participacion' => 3,
                'id_usuario' => 4,
                'id_porra' => 1,
                'fecha_union' => '2026-02-01 12:20:00',
                'es_admin' => 0,
                'puntos' => 5,
                'posicion' => 3,
            ],
            [
                'id_participacion' => 4,
                'id_usuario' => 5,
                'id_porra' => 1,
                'fecha_union' => '2026-02-01 12:25:00',
                'es_admin' => 0,
                'puntos' => 2,
                'posicion' => 4,
            ],

            // Porra 2 (Mundial - Oficina)
            [
                'id_participacion' => 5,
                'id_usuario' => 2,
                'id_porra' => 2,
                'fecha_union' => '2026-02-02 09:35:00',
                'es_admin' => 1,
                'puntos' => 12,
                'posicion' => 1,
            ],
            [
                'id_participacion' => 6,
                'id_usuario' => 3,
                'id_porra' => 2,
                'fecha_union' => '2026-02-02 09:40:00',
                'es_admin' => 0,
                'puntos' => 9,
                'posicion' => 2,
            ],
            [
                'id_participacion' => 7,
                'id_usuario' => 5,
                'id_porra' => 2,
                'fecha_union' => '2026-02-02 09:50:00',
                'es_admin' => 0,
                'puntos' => 4,
                'posicion' => 3,
            ],

            //Porra 3 
            [
                'id_participacion' => 8,
                'id_usuario' => 2,
                'id_porra' => 3,
                'fecha_union' => '2026-02-05 20:05:00',
                'es_admin' => 0,
                'puntos' => 3,
                'posicion' => 2,
            ],
            [
                'id_participacion' => 9,
                'id_usuario' => 3,
                'id_porra' => 3,
                'fecha_union' => '2026-02-05 20:05:00',
                'es_admin' => 1,
                'puntos' => 7,
                'posicion' => 1,
            ],
            [
                'id_participacion' => 10,
                'id_usuario' => 6,
                'id_porra' => 3,
                'fecha_union' => '2026-02-05 20:10:00',
                'es_admin' => 0,
                'puntos' => 1,
                'posicion' => 3,
            ],
            //Porra 4    
            [
                'id_participacion' => 11,
                'id_usuario' => 5,
                'id_porra' => 4,
                'fecha_union' => '2026-02-04 10:05:00',
                'es_admin' => 1,
                'puntos' => 15,
                'posicion' => 1,
            ],
            [
                'id_participacion' => 12,
                'id_usuario' => 6,
                'id_porra' => 4,
                'fecha_union' => '2026-02-04 10:10:00',
                'es_admin' => 0,
                'puntos' => 10,
                'posicion' => 2,
            ],
        ]);
    }
}
