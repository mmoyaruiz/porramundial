<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PartidosSeeder extends Seeder
{

    private array $tlaMap = [
        'España' => 'ESP',
        'Brasil' => 'BRA',
        'Alemania' => 'GER',
        'Japón' => 'JPN',
        'Francia' => 'FRA',
        'Australia' => 'AUS',
        'Portugal' => 'POR',
        'Inglaterra' => 'ENG',

        'Real Madrid' => 'RMA',
        'Barcelona' => 'FCB',
        'Sevilla' => 'SEV',
        'Betis' => 'BET',
        'Atlético' => 'ATM',
        'Valencia' => 'VAL',
        'Villarreal' => 'VIL',
        'Celta' => 'CEL',
    ];

    private function tlaOf(string $teamName): string
    {
        return $this->tlaMap[$teamName] ?? 'UNK';
    }

    public function run(): void
    {
        
        
        $rawRows = [

            [1, 2, '2025-09-10 21:00:00', 'Real Madrid', 'Barcelona',   null, null, 'programado'],
            [2, 2, '2025-09-20 18:30:00', 'Sevilla',     'Betis',       null, null, 'programado'],
            [3, 2, '2025-09-25 20:00:00', 'Atlético',    'Valencia',    null, null, 'programado'],
            [4, 2, '2025-10-01 19:00:00', 'Villarreal',  'Celta',       null, null, 'programado'],
        ];

        $rowsToInsert = [];

        foreach ($rawRows as [$idPartido, $idCompeticion, $fechaHora, $local, $visitante, $gLocal, $gVisit, $estado]) {

            $rowsToInsert[] = [
                'id_partido' => $idPartido,

                'api_match_id' => null,

                'id_competicion' => $idCompeticion,

                'fecha_hora' => $fechaHora,
                'estado' => $estado,

                'fase' => null,
                'grupo' => null,

                'equipo_local_nombre' => $local,
                'equipo_local_shortname' => $local,
                'equipo_local_crest_url' => null,

                'equipo_visitante_nombre' => $visitante,
                'equipo_visitante_shortname' => $visitante,
                'equipo_visitante_crest_url' => null,

                'goles_local' => $gLocal,
                'goles_visitante' => $gVisit,

                'equipo_local_tla' => $this->tlaOf($local),
                'equipo_visitante_tla' => $this->tlaOf($visitante),
            ];
        }

        DB::table('partidos')->insert($rowsToInsert);
    }
}

