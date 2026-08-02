<?php

namespace App\Console\Commands;

use App\Models\CampeonReal;
use App\Models\Competicion;
use App\Models\Partido;
use App\Services\FootballDataClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando pm:importar-campeones
 *
 * Objetivo:
 * - Guardar/actualizar en la tabla campeones_reales los campeones actuales de cada grupo
 *   (primer clasificado de cada grupo según la clasificación).
 * - Guardar/actualizar el campeón del torneo cuando exista una FINAL finalizada.
 *
 * Este comando existe para poder comparar “campeones reales” contra los pronósticos
 * de campeones de los usuarios y, más adelante, sumar puntos en la clasificación. 
 */
class ImportarCampeones extends Command
{
    protected $signature = 'pm:importar-campeones
        {--code=WC : Código de competición (ej: WC)}
        {--competicion-id= : id_competicion interno}
        {--nombre-competicion= : Buscar id_competicion por nombre (LIKE)}
        {--dry-run : No escribe en BD, solo muestra lo que detecta}';

    protected $description = 'Importa/actualiza campeones reales (grupos + campeón torneo) en campeones_reales';

    public function handle(FootballDataClient $api): int
    {
        $idCompeticion = $this->resolveIdCompeticion();
        if (!$idCompeticion) {
            $this->error('No se pudo resolver id_competicion interno. Usa --competicion-id o --nombre-competicion.');
            return Command::FAILURE;
        }

        $code   = (string) $this->option('code');
        $dryRun = (bool) $this->option('dry-run');

        $creados      = 0;
        $actualizados = 0;

        /*
         * ==========================================================
         * 1) CAMPEONES DE GRUPO (standings)
         * ==========================================================
         * Se recorre el array "standings" y se extrae el equipo en posición 1
         * para cada grupo (A, B, C...).
         *
         * Nota: la API puede devolver "Group A" o "GROUP_A", por eso aceptamos ambos formatos.
         */
        try {
            $payloadStandings = $api->competitionStandings($code);
        } catch (\Throwable $e) {
            $this->error('Error consultando standings en la API: ' . $e->getMessage());
            Log::error('API standings error', ['exception' => $e]);
            return Command::FAILURE;
        }

        $standings = $payloadStandings['standings'] ?? [];

        // Guardamos por grupo tanto el TLA como el shortName, para no perderlos en el upsert.
        $detectedGroupChampions = []; // ['A' => ['tla'=>'ESP','short'=>'Spain'], ...]
        $detectedGroupPriority  = []; // preferencia: TOTAL = 2, otro = 1

        foreach ($standings as $s) {
            $group = $s['group'] ?? null;   // ej: "Group A"
            $type  = $s['type'] ?? null;    // ej: "TOTAL"
            $table = $s['table'] ?? [];

            // Detectar letra de grupo (aceptando formatos "Group A" y "GROUP_A")
            $grupoLetra = null;

            if ($group && preg_match('/\bGroup\s*([A-L])\b/i', $group, $m)) {
                $grupoLetra = strtoupper($m[1]);
            } elseif ($group && preg_match('/\bGROUP[_\s-]*([A-L])\b/i', $group, $m)) {
                $grupoLetra = strtoupper($m[1]);
            }

            if (!$grupoLetra || empty($table)) {
                continue;
            }

            // Preferimos standings de tipo TOTAL si existieran duplicados del mismo grupo
            $priority = ($type === 'TOTAL') ? 2 : 1;

            if (isset($detectedGroupChampions[$grupoLetra]) && ($detectedGroupPriority[$grupoLetra] ?? 0) > $priority) {
                continue;
            }

            // Buscar el líder del grupo (position=1). Si no existe, tomar el primero.
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

            $teamTla   = $leader['team']['tla'] ?? null;
            $teamShort = $leader['team']['shortName'] ?? null;

            // Si no viene shortName en standings, lo intentamos resolver desde la tabla partidos por TLA
            if (!$teamShort && $teamTla) {
                $tla = strtoupper($teamTla);

                $teamShort = Partido::where('id_competicion', $idCompeticion)
                    ->where('equipo_local_tla', $tla)
                    ->value('equipo_local_shortname');

                if (!$teamShort) {
                    $teamShort = Partido::where('id_competicion', $idCompeticion)
                        ->where('equipo_visitante_tla', $tla)
                        ->value('equipo_visitante_shortname');
                }
            }

            $teamShort = $teamShort ? trim($teamShort) : '';

            // Si no hay TLA no podemos guardar (campeones_reales trabaja con TLA)
            if (!$teamTla) {
                continue;
            }

            $detectedGroupChampions[$grupoLetra] = [
                'tla'   => strtoupper($teamTla),
                'short' => $teamShort,
            ];
            $detectedGroupPriority[$grupoLetra] = $priority;
        }

        // Salida informativa 
        $this->line('Grupos detectados: ' . count($detectedGroupChampions));
        $this->line(json_encode($detectedGroupChampions));

        // Upsert en campeones_reales para campeones de grupo
        foreach ($detectedGroupChampions as $grupo => $info) {
            $equipoTla   = $info['tla'];
            $equipoShort = $info['short'];

            $this->line("DEBUG UPSERT Grupo {$grupo}: {$equipoTla} / {$equipoShort}");

            if ($dryRun) {
                continue;
            }

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

        /*
         * ==========================================================
         * 2) CAMPEÓN DEL TORNEO (FINAL finalizada en matches)
         * ==========================================================
         * Solo se puede determinar cuando exista una FINAL con status FINISHED.
         */
        try {
            $payloadMatches = $api->competitionMatches($code);
        } catch (\Throwable $e) {
            $this->error('Error consultando matches en la API: ' . $e->getMessage());
            Log::error('API matches error', ['exception' => $e]);
            $payloadMatches = [];
        }

        $matches = $payloadMatches['matches'] ?? [];

        $finalMatch = null;
        foreach ($matches as $m) {
            if (($m['stage'] ?? null) === 'FINAL' && ($m['status'] ?? null) === 'FINISHED') {
                $finalMatch = $m;
                break;
            }
        }

        if ($finalMatch) {
            dd($finalMatch);

            $winner  = $finalMatch['score']['winner'] ?? null;
            $homeTla = $finalMatch['homeTeam']['tla'] ?? null;
            $awayTla = $finalMatch['awayTeam']['tla'] ?? null;

            $campeonTla = null;

            if ($winner === 'HOME_TEAM') {
                $campeonTla = $homeTla;
            } elseif ($winner === 'AWAY_TEAM') {
                $campeonTla = $awayTla;
            } else {
                // Fallback si no viene winner: deducimos por marcador fullTime
                $hl = $finalMatch['score']['fullTime']['home'] ?? null;
                $av = $finalMatch['score']['fullTime']['away'] ?? null;

                if ($hl !== null && $av !== null) {
                    if ($hl > $av) $campeonTla = $homeTla;
                    if ($hl < $av) $campeonTla = $awayTla;
                }
            }

            dd($campeonTla);

            if ($campeonTla) {
                $campeonTla = strtoupper($campeonTla);

                dd("FINAL detectada. Campeón torneo: {$campeonTla}");

                dd($dryRun);

                if (!$dryRun) {
                    $row = CampeonReal::where('id_competicion', $idCompeticion)
                        ->where('tipo', 'competicion')
                        ->where('grupo', '')
                        ->first();
                    
                        dd($row);

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
                } else {
                    $this->line("[DRY-RUN] Campeón torneo -> {$campeonTla}");
                }
            } else {
                $this->warn('FINAL encontrada pero no se pudo determinar el campeón.');
            }
        } else {
            $this->line('No hay FINAL finalizada todavía. Campeón del torneo no actualizado.');
        }

        $this->info("Importación de campeones completada. Creados: {$creados} | Actualizados: {$actualizados}");
        return Command::SUCCESS;
    }

    /**
     * Resuelve el id_competicion interno a partir de:
     * - --competicion-id
     * - --nombre-competicion
     * - o una heurística simple si no se pasa nada.
     */
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

        $c = Competicion::where('nombre', 'like', '%Mundial%')
            ->orWhere('nombre', 'like', '%World Cup%')
            ->first();

        return $c?->id_competicion;
    }
}