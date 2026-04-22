<?php

namespace App\Console\Commands;

use App\Models\CampeonReal;
use App\Models\Competicion;
use App\Services\FootballDataClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando: pm:importar-campeones
 *
 * Importa/actualiza en la tabla campeones_reales:
 *  - Campeones reales de cada grupo (GROUP_A, GROUP_B, ...) desde el endpoint de standings.
 *  - Campeón real del torneo desde el partido FINAL (stage=FINAL, status=FINISHED) en matches.
 *
 * Fuentes API football-data.org v4:
 *  - Competition / Standings: /v4/competitions/{CODE}/standings  [1](https://www.postman.com/api-noob/football-data-org-apis/documentation/yjgfm4j/football-data-org-v4)
 *  - Competition / Matches:   /v4/competitions/{CODE}/matches     
 *  - Enums del recurso Match: status, stage, group (FINAL, GROUP_A...) [2](https://www.postman.com/api-noob/football-data-org-apis/request/f6t6urb/competition-standings)
 *
 * Relación con ERS:
 *  - La porra puntúa campeón de grupo y ganador del torneo, y existe PronosticosCampeones para ello. [1](https://www.postman.com/api-noob/football-data-org-apis/documentation/yjgfm4j/football-data-org-v4)
 */
class ImportarCampeones extends Command
{
    protected $signature = 'pm:importar-campeones
        {--code=WC : Código football-data de la competición (ej: WC)}
        {--competicion-id= : id_competicion interno (FK)}
        {--nombre-competicion= : Buscar id_competicion por nombre (LIKE)}
        {--dry-run : No escribe en BD, solo muestra lo que detecta}';

    protected $description = 'Importa/actualiza campeones reales (grupos + campeón torneo) desde football-data.org hacia campeones_reales';

    public function handle(FootballDataClient $api): int
    {
        $idCompeticion = $this->resolveIdCompeticion();
        if (!$idCompeticion) {
            $this->error('No se pudo resolver id_competicion interno. Usa --competicion-id o --nombre-competicion.');
            return Command::FAILURE;
        }

        $code = (string) $this->option('code');
        $dryRun = (bool) $this->option('dry-run');

        $creados = 0;
        $actualizados = 0;

        // ------------------------------------------------------------
        // 1) CAMPEONES DE GRUPO (desde standings)
        // ------------------------------------------------------------
        try {
            // Debes implementar este método en FootballDataClient:
            // GET /v4/competitions/{CODE}/standings  [1](https://www.postman.com/api-noob/football-data-org-apis/documentation/yjgfm4j/football-data-org-v4)
            $payloadStandings = $api->competitionStandings($code);
        } catch (\Throwable $e) {
            $this->error('Error consultando standings en la API: ' . $e->getMessage());
            Log::error('API football-data standings error', ['exception' => $e]);
            return Command::FAILURE;
        }

        $standings = $payloadStandings['standings'] ?? [];








        // Estrategia robusta:
        // - No dependemos de stage (puede venir ALL).
        // - Nos guiamos por: group = GROUP_X y table con position 1.
        // - Priorizamos type=TOTAL si existe, pero aceptamos otros si fuera necesario.
        $detectedGroupChampions = [];        // ['A' => 'ESP', ...]
        $detectedGroupPriority  = [];        // ['A' => 2] para preferir TOTAL (2) sobre otros (1)

        foreach ($standings as $s) {
            $group = $s['group'] ?? null;          // ej: GROUP_A
            $type  = $s['type'] ?? null;           // ej: TOTAL
            $table = $s['table'] ?? [];







            /**
             * La API te está devolviendo:
             *   "Group A", "Group B", ...
             * pero en otros casos puede devolver:
             *   "GROUP_A"
             *
             * Por eso aceptamos AMBOS:
             *  - Group A / Group-A / Group_A
             *  - GROUP_A / GROUP-A / GROUP A
             */
            $grupoLetra = null;

            // Caso 1: "Group A"
            if (preg_match('/\bGroup\s*([A-L])\b/i', $group, $m)) {
                $grupoLetra = strtoupper($m[1]);
            }
            // Caso 2: "GROUP_A" o "GROUP A" o "GROUP-A"
            elseif (preg_match('/\bGROUP[_\s-]*([A-L])\b/i', $group, $m)) {
                $grupoLetra = strtoupper($m[1]);
            }

            if (!$grupoLetra) {
                continue;
            }



            if (empty($table)) {
                continue;
            }

            // 2) Preferencia de tipo: TOTAL > cualquier otro
            $priority = ($type === 'TOTAL') ? 2 : 1;

            // Si ya tenemos campeón para ese grupo con mayor prioridad, saltamos
            if (isset($detectedGroupChampions[$grupoLetra]) && ($detectedGroupPriority[$grupoLetra] ?? 0) > $priority) {
                continue;
            }

            // 3) Buscamos la fila con position=1; si no existe, usamos la primera
            $leader = null;
            foreach ($table as $row) {
                if (($row['position'] ?? null) === 1) {
                    $leader = $row;
                    break;
                }
            }
            if (!$leader) {
                $leader = $table[0];
            }

            $teamTla  = $leader['team']['tla'] ?? null;
            $teamName = $leader['team']['name'] ?? null;
            $teamShort = $leader['team']['shortName'] ?? null;


            // Fallback por TLA (mucho más fiable que por name)
            if (!$teamShort && $teamTla) {
                $tla = strtoupper($teamTla);

                $teamShort = \App\Models\Partido::where('id_competicion', $idCompeticion)
                    ->where('equipo_local_tla', $tla)
                    ->value('equipo_local_shortname');

                if (!$teamShort) {
                    $teamShort = \App\Models\Partido::where('id_competicion', $idCompeticion)
                        ->where('equipo_visitante_tla', $tla)
                        ->value('equipo_visitante_shortname');
                }
            }

            $teamShort = $teamShort ? trim($teamShort) : '';


            $teamShort = $teamShort ? trim($teamShort) : '';


            if (!$teamTla) {
                // Si sigue sin TLA, no podemos guardar en campeones_reales (porque tu tabla espera TLA)
                continue;
            }


            $detectedGroupChampions[$grupoLetra] = [
                'tla'   => strtoupper($teamTla),
                'short' => $teamShort, // lo calculamos justo antes (ver siguiente bloque)
            ];
            $detectedGroupPriority[$grupoLetra]  = $priority;
        }




        $this->line('Grupos detectados: ' . count($detectedGroupChampions));
        $this->line(json_encode($detectedGroupChampions));


        // Upsert campeones de grupo

        foreach ($detectedGroupChampions as $grupo => $info) {
            $equipoTla   = $info['tla'];
            $equipoShort = $info['short'];

            // DEBUG útil (puedes dejarlo temporalmente)
            $this->line("DEBUG UPSERT Grupo {$grupo}: {$equipoTla} / {$equipoShort}");

            $row = CampeonReal::where('id_competicion', $idCompeticion)
                ->where('tipo', 'grupo')
                ->where('grupo', $grupo)
                ->first();

            if ($row) {
                if ($row->equipo_tla !== $equipoTla || $row->equipo_shortname !== $equipoShort) {
                    $row->equipo_tla = $equipoTla;
                    $row->equipo_shortname = $equipoShort;
                    $row->save();
                    $actualizados++;
                }
            } else {
                CampeonReal::create([
                    'id_competicion' => $idCompeticion,
                    'tipo' => 'grupo',
                    'grupo' => $grupo,
                    'equipo_tla' => $equipoTla,
                    'equipo_shortname' => $equipoShort,
                    'fuente' => 'football-data.org',
                ]);
                $creados++;
            }
        }


        // ------------------------------------------------------------
        // 2) CAMPEÓN DEL TORNEO (desde FINAL en matches)
        // ------------------------------------------------------------
        try {
            // GET /v4/competitions/{CODE}/matches  
            $payloadMatches = $api->competitionMatches($code);
        } catch (\Throwable $e) {
            $this->error('Error consultando matches en la API: ' . $e->getMessage());
            Log::error('API football-data matches error', ['exception' => $e]);
            // No fallamos todo el comando: campeones de grupo pueden estar OK
            $payloadMatches = [];
        }

        $matches = $payloadMatches['matches'] ?? [];

        $finalMatch = null;
        foreach ($matches as $m) {
            // stage/status enum del Match [2](https://www.postman.com/api-noob/football-data-org-apis/request/f6t6urb/competition-standings)
            if (($m['stage'] ?? null) === 'FINAL' && ($m['status'] ?? null) === 'FINISHED') {
                $finalMatch = $m;
                break;
            }
        }

        if ($finalMatch) {
            $winner = $finalMatch['score']['winner'] ?? null; // suele ser HOME_TEAM / AWAY_TEAM / DRAW
            $homeTla = $finalMatch['homeTeam']['tla'] ?? null;
            $awayTla = $finalMatch['awayTeam']['tla'] ?? null;

            $campeonTla = null;
            if ($winner === 'HOME_TEAM') {
                $campeonTla = $homeTla;
            } elseif ($winner === 'AWAY_TEAM') {
                $campeonTla = $awayTla;
            } else {
                // Fallback: si no viene winner, determinamos por marcador fullTime
                $hl = $finalMatch['score']['fullTime']['home'] ?? null;
                $av = $finalMatch['score']['fullTime']['away'] ?? null;
                if ($hl !== null && $av !== null) {
                    if ($hl > $av) $campeonTla = $homeTla;
                    if ($hl < $av) $campeonTla = $awayTla;
                }
            }

            if ($campeonTla) {
                $campeonTla = strtoupper($campeonTla);

                if ($dryRun) {
                    $this->line("[DRY-RUN] Campeón torneo -> {$campeonTla}");
                } else {
                    // Importante: para tipo='competicion' usamos grupo='' (cadena vacía)
                    $row = CampeonReal::where('id_competicion', $idCompeticion)
                        ->where('tipo', 'competicion')
                        ->where('grupo', '')
                        ->first();

                    if ($row) {
                        if ($row->equipo_tla !== $campeonTla) {
                            $row->equipo_tla = $campeonTla;
                            $row->save();
                            $actualizados++;
                        }
                    } else {
                        CampeonReal::create([
                            'id_competicion' => $idCompeticion,
                            'tipo' => 'competicion',
                            'grupo' => '',
                            'equipo_tla' => $campeonTla,
                            'fuente' => 'football-data.org',
                        ]);
                        $creados++;
                    }
                }
            } else {
                $this->warn('FINAL encontrada pero no se pudo determinar el campeón (winner/marcador no disponible).');
            }
        } else {
            $this->line('No hay FINAL finalizada todavía (stage=FINAL, status=FINISHED). Campeón del torneo no actualizado.');
        }

        $this->info("Importación de campeones completada. Creados: {$creados} | Actualizados: {$actualizados}");
        return Command::SUCCESS;
    }

    private function resolveIdCompeticion(): ?int
    {
        if ($this->option('competicion-id')) {
            return (int) $this->option('competicion-id');
        }

        if ($this->option('nombre-competicion')) {
            $needle = (string) $this->option('nombre-competicion');
            $c = Competicion::where('nombre', 'like', "%{$needle}%")->first();
            return $c?->id_competicion;
        }

        // Heurística por defecto (si no se pasa nada)
        $c = Competicion::where('nombre', 'like', '%Mundial%')
            ->orWhere('nombre', 'like', '%World Cup%')
            ->first();

        return $c?->id_competicion;
    }
}
