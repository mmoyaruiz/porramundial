<?php

namespace App\Services;

use App\Models\Partido;
use App\Models\Porra;
use App\Models\Pronostico;
use App\Models\Participacion;
use Illuminate\Support\Facades\DB;

/**
 * CalculadorPuntosService
 *
 * Calcula y aplica puntos cuando un partido se da por finalizado.
 * Actualiza:
 * - pronosticos.puntos_obtenidos
 * - participaciones.puntos (clasificación)
 */
class CalculadorPuntosService
{
    public function calcularPorPartido(Partido $partido): void
    {
        // Solo calcular si el partido está finalizado
        if ($partido->estado !== 'finalizado') {
            return;
        }

        // Pronósticos de ese partido
        $pronosticos = Pronostico::where('id_partido', $partido->id_partido)->get();

        DB::transaction(function () use ($partido, $pronosticos) {

            foreach ($pronosticos as $pronostico) {

                $porra = Porra::find($pronostico->id_porra);
                if (!$porra) {
                    continue;
                }

                $puntos = 0;

                // Resultado real
                $realLocal = $partido->goles_local;
                $realVisitante = $partido->goles_visitante;

                // Pronóstico
                $pLocal = $pronostico->goles_local_pronosticados;
                $pVisitante = $pronostico->goles_visitante_pronosticados;

                // 1️⃣ Marcador exacto
                if ($realLocal === $pLocal && $realVisitante === $pVisitante) {
                    $puntos = $porra->puntos_marcador;
                } else {
                    // 2️⃣ Acierta ganador (1X2)
                    $realSigno = $this->signo($realLocal, $realVisitante);
                    $pronSigno = $this->signo($pLocal, $pVisitante);

                    if ($realSigno === $pronSigno) {
                        $puntos = $porra->puntos_ganador;
                    }
                }

                // Guardar puntos del pronóstico
                $pronostico->puntos_obtenidos = $puntos;
                $pronostico->save();

                // Sumar puntos a la participación
                Participacion::where('id_usuario', $pronostico->id_usuario)
                    ->where('id_porra', $pronostico->id_porra)
                    ->increment('puntos', $puntos);
            }
        });
    }

    /**
     * Devuelve signo 1X2:
     *  1  -> local
     *  X  -> empate
     *  2  -> visitante
     */
    private function signo(int $local, int $visitante): string
    {
        if ($local > $visitante) return '1';
        if ($local < $visitante) return '2';
        return 'X';
    }
}
