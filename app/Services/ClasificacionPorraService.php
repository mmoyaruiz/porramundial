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
 * Servicio ClasificacionPorraService
 *
 * Recalcula desde cero la clasificación de una porra concreta.
 *
 * Qué hace:
 * 1) Resetea puntos/posiciones de la porra (para evitar dobles sumas).
 * 2) Recalcula puntos de pronósticos de partidos finalizados.
 * 3) Recalcula puntos de pronósticos de campeones (si hay campeones reales).
 * 4) Reasigna posiciones ordenando por puntos.
 *
 * Nota: se recalcula "desde cero" para que el resultado sea consistente aunque se ejecute varias veces.
 */
class ClasificacionPorraService
{
    /**
     * Recalcula toda la clasificación de una porra.
     */
    public function recalcular(int $idPorra): void
    {
        DB::transaction(function () use ($idPorra) {

            $porra = Porra::findOrFail($idPorra);

            // 1) Reset total (evita duplicar puntos al recalcular)
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

            // 2) Puntos por partidos finalizados (solo si hay marcador real)
            $partidosFinalizados = Partido::where('id_competicion', $porra->id_competicion)
                ->where('estado', ['finalizado', 'en_juego'])
                ->whereNotNull('goles_local')
                ->whereNotNull('goles_visitante')
                ->get();

            foreach ($partidosFinalizados as $partido) {

                $pronosticos = Pronostico::where('id_porra', $idPorra)
                    ->where('id_partido', $partido->id_partido)
                    ->get();

                foreach ($pronosticos as $pr) {
                    $puntos = $this->puntosPartido(
                        (int) $porra->puntos_ganador,
                        (int) $porra->puntos_marcador,
                        (int) $partido->goles_local,
                        (int) $partido->goles_visitante,
                        (int) $pr->goles_local_pronosticados,
                        (int) $pr->goles_visitante_pronosticados
                    );

                    // Guardar puntos del pronóstico
                    $pr->puntos_obtenidos = $puntos;
                    $pr->save();

                    // Sumar al total del participante en esa porra
                    Participacion::where('id_porra', $idPorra)
                        ->where('id_usuario', $pr->id_usuario)
                        ->increment('puntos', $puntos);
                }
            }

            // 3) Puntos por campeones (si existen datos reales)
            $this->aplicarCampeonTorneo($porra);
            $this->aplicarCampeonesGrupo($porra);

            // 4) Recalcular posiciones (ranking por puntos)
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

    /**
     * Calcula puntos de un pronóstico de partido:
     * - Exacto -> puntos_marcador
     * - Si no es exacto, pero acierta 1X2 -> puntos_ganador
     */
    private function puntosPartido(
        int $puntosGanador,
        int $puntosMarcador,
        int $realL,
        int $realV,
        int $prL,
        int $prV
    ): int {
        //Si los goles reales son 4 o más, se da por correcto si el pronótico de goles del participante es 4
        if ($realL >= 4 && $prL === 4) {
            $realL = 4;
        }
        if ($realV >= 4 && $prV === 4) {
            $realV = 4;
        }
        
        if ($realL === $prL && $realV === $prV) {
            return $puntosMarcador;
        }

        if ($this->signo($realL, $realV) === $this->signo($prL, $prV)) {
            return $puntosGanador;
        }

        return 0;
    }

    /**
     * Devuelve el signo 1X2 a partir del marcador.
     */
    private function signo(int $l, int $v): string
    {
        if ($l > $v) return '1';
        if ($l < $v) return '2';
        return 'X';
    }

    /**
     * Aplica puntos por campeón del torneo (si existe campeón real).
     * Se compara el pronóstico del usuario con el campeón real guardado.
     */
    private function aplicarCampeonTorneo(Porra $porra): void
    {
        $real = CampeonReal::where('id_competicion', $porra->id_competicion)
            ->where('tipo', 'competicion')
            ->where('grupo', '')
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
            $puntos  = $acierta ? (int) $porra->puntos_ganador_torneo : 0;

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
     * Aplica puntos por campeones de grupo.
     * Regla: solo puntúa un grupo cuando el grupo está "cerrado" (todos los partidos de ese grupo están finalizados).
     */
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
            $grupo = (string) $pc->grupo;

            // Solo puntúa cuando el grupo esté completamente finalizado
            if (!$this->grupoCerrado($porra->id_competicion, $grupo)) {
                continue;
            }

            $realTla = $reales[$grupo]->equipo_tla ?? null;
            if (!$realTla) {
                continue;
            }

            $pronTla = $this->normalizarEquipoPronosticadoATla($porra->id_competicion, $pc->equipo_pronosticado);

            $acierta = ($pronTla === $realTla);
            $puntos  = $acierta ? (int) $porra->puntos_campeon_grupo : 0;

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
     * Un grupo se considera "cerrado" cuando:
     * - Existe al menos un partido de ese grupo.
     * - No queda ningún partido del grupo sin finalizar.
     * - Todos los partidos finalizados tienen marcador (goles no nulos).
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
                $q->whereNull('goles_local')
                  ->orWhereNull('goles_visitante');
            })
            ->exists();

        return !$sinMarcador;
    }

    /**
     * Normaliza el equipo pronosticado a TLA.
     * - Si ya viene con 3 letras, se considera TLA.
     * - Si viene como nombre, se intenta mapear usando los datos guardados en partidos.
     */
    private function normalizarEquipoPronosticadoATla(int $idCompeticion, ?string $equipo): ?string
    {
        if (!$equipo) return null;

        $trim = strtoupper(trim($equipo));
        if (strlen($trim) === 3) {
            return $trim;
        }

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