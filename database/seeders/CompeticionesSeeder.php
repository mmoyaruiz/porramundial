<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompeticionesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('competiciones')->insert([
            [
                'id_competicion' => 1,
                'nombre' => 'Mundial de Selecciones 2026',
                'fecha_inicio' => '2026-06-01',
                'fecha_fin' => '2026-07-15',
                'tipo_torneo' => 'Mundial',
            ],
            [
                'id_competicion' => 2,
                'nombre' => 'LaLiga 1ª División 2025/26 (NO FUNCIONA)',
                'fecha_inicio' => '2025-08-15',
                'fecha_fin' => '2026-05-30',
                'tipo_torneo' => 'Liga',
            ],
            [
                'id_competicion' => 3,
                'nombre' => 'Champions League 2025/26 (NO FUNCIONA)',
                'fecha_inicio' => '2025-09-10',
                'fecha_fin' => '2026-05-29',
                'tipo_torneo' => 'Copa',
            ],
            [
                'id_competicion' => 4,
                'nombre' => 'Copa del Rey 2026 (NO FUNCIONA)',
                'fecha_inicio' => '2026-01-10',
                'fecha_fin' => '2026-04-30',
                'tipo_torneo' => 'Copa',
            ],
            [
                'id_competicion' => 5,
                'nombre' => 'Eurocopa 2028 (NO FUNCIONA)',
                'fecha_inicio' => '2028-06-10',
                'fecha_fin' => '2028-07-15',
                'tipo_torneo' => 'Mundial',
            ],
        ]);
    }
}
