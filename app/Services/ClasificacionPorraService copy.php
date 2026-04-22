<?php

namespace App\Services;

use App\Models\CampeonReal;
use App\Models\Partido;
use App\Models\Participacion;
use App\Models\Porra;
use App\Models\Pronostico;
use App\Models\PronosticoCampeon;
use Illuminate\Support\Facades\DB;

/**
 * ClasificacionPorraService
 *
 * Recalcula desde cero la clasificación de una porra basándose en:
 * - Pronósticos de partidos vs resultados reales (partidos finalizados)
 * - Pronósticos de campeones vs campeones reales (si existen)
 *
 * ERS:
 * - Puntos por 1X2, marcador exacto, campeón de grupo, ganador del torneo. [1](https://www.postman.com/api-noob/football-data-org-apis/documentation/yjgfm4j/football-data-org-v4)
 */
class ClasificacionPorraService
{
    /**
     * Recalcula TODA la porra desde cero (robusto, evita duplicar puntos).
     */
    public function recalcular(int $idPorra): void
    {
        DB::transaction(function () use ($idPorra) {

            $porra = Porra::findOrFail($idPorra);

            // ---------------------------------------------------------
            // 1) RESET TOTAL (para evitar dobles sumas)
            // ---------------------------------------------------------
            Participacion::where('id_porra', $idPorra)->update([
                'puntos' => 0,
                'posicion' => null,
            ]);

            Pronostico::where('id_porra', $idPorra)->update([
                'puntos_obtenidos' => 0,
            ]);

            PronosticoCampeon::where('id_porra', $idPorra)->update([
                'puntos_obtenidos' => 0,
            ]);

            // ---------------------------------------------------------
            // 2) PUNTOS POR PARTIDOS FINALIZADOS
            // ---------------------------------------------------------
            $partidosFinalizados = Partido::where('id_competicion', $porra->id_competicion)
                ->where('estado', 'finalizado')
                ->whereNotNull('goles_local')
                ->whereNotNull('goles_visitante')
                ->get();

            foreach ($partidosFinalizados as $partido) {
                $pronosticos = Pronostico::where('id_porra', $idPorra)
                    ->where('id_partido', $partido->id_partido)
                    ->get();

                foreach ($pronosticos as $pr) {
                    $puntos = $this->puntosPartido(
                        (int)$porra->puntos_ganador,
                        (int)$porra->puntos_marcador,
                        (int)$partido->goles_local,
                        (int)$partido->goles_visitante,
                        (int)$pr->goles_local_pronosticados,
                        (int)$pr->goles_visitante_pronosticados
                    );

                    $pr->puntos_obtenidos = $puntos;
                    $pr->save();

                    Participacion::where('id_porra', $idPorra)
                        ->where('id_usuario', $pr->id_usuario)
                        ->increment('puntos', $puntos);
                }
            }

            // ---------------------------------------------------------
            // 3) PUNTOS POR CAMPEONES (si existen datos reales)
            // ---------------------------------------------------------
            $this->aplicarCampeonTorneo($porra);
            $this->aplicarCampeonesGrupo($porra);

            // ---------------------------------------------------------
            // 4) POSICIONES (ranking por puntos)
            // ---------------------------------------------------------
            $ranking = Participacion::where('id_porra', $idPorra)
                ->orderByDesc('puntos')
                ->orderBy('fecha_union')
                ->get();

            $pos = 1;
            foreach ($ranking as $p) {
                $p->posicion = $pos++;
                $p->save();
            }
        });
    }

    // =============================================================
    // PUNTOS PARTIDO (1X2 / exacto)
    // =============================================================
    private function puntosPartido(
        int $puntosGanador,
        int $puntosMarcador,
        int $realL,
        int $realV,
        int $prL,
        int $prV
    ): int {
        // Exacto
        if ($realL === $prL && $realV === $prV) {
            return $puntosMarcador;
        }

        // 1X2
        if ($this->signo($realL, $realV) === $this->signo($prL, $prV)) {
            return $puntosGanador;
        }

        return 0;
    }

    private function signo(int $l, int $v): string
    {
        if ($l > $v) return '1';
        if ($l < $v) return '2';
        return 'X';
    }

    // =============================================================
    // CAMPEÓN TORNEO (tipo='competicion', grupo='')
    // =============================================================
    private function aplicarCampeonTorneo(Porra $porra): void
    {
        $real = CampeonReal::where('id_competicion', $porra->id_competicion)
            ->where('tipo', 'competicion')
            ->where('grupo', '') // diseño recomendado
            ->value('equipo_tla');

        if (!$real) {
            return; // aún no hay campeón real del torneo
        }

        $pron = PronosticoCampeon::where('id_porra', $porra->id_porra)
            ->where('tipo_pronostico', 'competicion')
            ->get();

        foreach ($pron as $pc) {
            $pronTla = $this->normalizarEquipoPronosticadoATla($porra->id_competicion, $pc->equipo_pronosticado);

            $acierta = ($pronTla === $real);
            $puntos = $acierta ? (int)$porra->puntos_ganador_torneo : 0;

            $pc->puntos_obtenidos = $puntos;
            $pc->save();

            if ($puntos > 0) {
                Participacion::where('id_porra', $porra->id_porra)
                    ->where('id_usuario', $pc->id_usuario)
                    ->increment('puntos', $puntos);
            }
        }
    }

    // =============================================================
    // CAMPEONES GRUPO (tipo='grupo', grupo='A'..)
    // Regla: solo puntúa si TODOS los partidos del grupo están finalizados
    // =============================================================
    private function aplicarCampeonesGrupo(Porra $porra): void
    {
        $reales = CampeonReal::where('id_competicion', $porra->id_competicion)
            ->where('tipo', 'grupo')
            ->get()
            ->keyBy('grupo');

        if ($reales->isEmpty()) {
            return;
        }

        $pron = PronosticoCampeon::where('id_porra', $porra->id_porra)
            ->where('tipo_pronostico', 'grupo')
            ->get();

        foreach ($pron as $pc) {
            $grupo = (string)$pc->grupo;

            // ✅ Regla que has definido:
            // solo sumar puntos si el grupo está "cerrado" (todos finalizados con marcador)
            if (!$this->grupoCerrado($porra->id_competicion, $grupo)) {
                continue;
            }

            $realTla = $reales[$grupo]->equipo_tla ?? null;
            if (!$realTla) {
                continue;
            }

            $pronTla = $this->normalizarEquipoPronosticadoATla($porra->id_competicion, $pc->equipo_pronosticado);

            $acierta = ($pronTla === $realTla);
            $puntos = $acierta ? (int)$porra->puntos_campeon_grupo : 0;

            $pc->puntos_obtenidos = $puntos;
            $pc->save();

            if ($puntos > 0) {
                Participacion::where('id_porra', $porra->id_porra)
                    ->where('id_usuario', $pc->id_usuario)
                    ->increment('puntos', $puntos);
            }
        }
    }

    /**
     * Grupo cerrado = existe al menos un partido de ese grupo y
     * NO existe ninguno NO finalizado y todos tienen marcador.
     */
    private function grupoCerrado(int $idCompeticion, string $grupo): bool
    {
        $hayPartidos = Partido::where('id_competicion', $idCompeticion)
            ->where('fase', 'GROUP_STAGE')
            ->where('grupo', $grupo)
            ->exists();

        if (!$hayPartidos) return false;

        $pendientes = Partido::where('id_competicion', $idCompeticion)
            ->where('fase', 'GROUP_STAGE')
            ->where('grupo', $grupo)
            ->where('estado', '!=', 'finalizado')
            ->exists();

        if ($pendientes) return false;

        $sinMarcador = Partido::where('id_competicion', $idCompeticion)
            ->where('fase', 'GROUP_STAGE')
            ->where('grupo', $grupo)
            ->where(function ($q) {
                $q->whereNull('goles_local')->orWhereNull('goles_visitante');
            })
            ->exists();

        return !$sinMarcador;
    }

    /**
     * Normaliza el equipo pronosticado a TLA.
     * - Si ya viene en TLA (3 letras), lo devuelve.
     * - Si viene como nombre completo, intenta mapearlo a TLA usando tu tabla partidos.
     */
    private function normalizarEquipoPronosticadoATla(int $idCompeticion, ?string $equipo): ?string
    {
        if (!$equipo) return null;

        $trim = strtoupper(trim($equipo));
        if (strlen($trim) === 3) {
            return $trim; // ya es TLA
        }

        // Mapear nombre => TLA usando partidos importados (local/visitante)
        $tlaLocal = Partido::where('id_competicion', $idCompeticion)
            ->where('equipo_local_nombre', $equipo)
            ->value('equipo_local_tla');

        if ($tlaLocal) return strtoupper($tlaLocal);

        $tlaAway = Partido::where('id_competicion', $idCompeticion)
            ->where('equipo_visitante_nombre', $equipo)
            ->value('equipo_visitante_tla');

        if ($tlaAway) return strtoupper($tlaAway);

        return null;
    }
}
