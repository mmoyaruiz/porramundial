<?php

namespace App\Services;

use App\Models\Partido;
use App\Models\Porra;
use App\Models\Pronostico;
use App\Models\Participacion;
use Illuminate\Support\Facades\DB;

/**
 * Servicio CalculadorPuntosService
 *
 * Se encarga de calcular y aplicar los puntos de los pronósticos
 * cuando un partido pasa a estado "finalizado".
 *
 * Responsabilidades:
 * - Calcular los puntos obtenidos en cada pronóstico de un partido.
 * - Guardar los puntos en pronosticos.puntos_obtenidos.
 * - Sumar esos puntos a la participación del usuario en la porra.
 *
 * Este servicio no decide *cuándo* se ejecuta el cálculo, solo *cómo* se calcula.
 */
class CalculadorPuntosService
{
    /**
     * Calcula y aplica los puntos correspondientes a un partido finalizado.
     *
     * @param Partido $partido
     */
    public function calcularPorPartido(Partido $partido): void
    {
        // Seguridad: solo se calcula si el partido está finalizado
        if ($partido->estado !== 'finalizado') {
            return;
        }

        // Obtener todos los pronósticos realizados para este partido
        $pronosticos = Pronostico::where('id_partido', $partido->id_partido)->get();

        DB::transaction(function () use ($partido, $pronosticos) {

            foreach ($pronosticos as $pronostico) {

                // Obtener la porra a la que pertenece el pronóstico
                $porra = Porra::find($pronostico->id_porra);
                if (!$porra) {
                    continue;
                }

                $puntos = 0;

                // Resultado real del partido
                $realLocal      = $partido->goles_local;
                $realVisitante  = $partido->goles_visitante;

                // Pronóstico del usuario
                $pLocal     = $pronostico->goles_local_pronosticados;
                $pVisitante = $pronostico->goles_visitante_pronosticados;

                /*
                 * Reglas de puntuación:
                 * 1) Marcador exacto → puntos_marcador
                 * 2) Acierto del ganador (1X2) → puntos_ganador
                 */
                if ($realLocal === $pLocal && $realVisitante === $pVisitante) {
                    $puntos = $porra->puntos_marcador;
                } else {
                    $signoReal = $this->signo($realLocal, $realVisitante);
                    $signoPron = $this->signo($pLocal, $pVisitante);

                    if ($signoReal === $signoPron) {
                        $puntos = $porra->puntos_ganador;
                    }
                }

                // Guardar puntos obtenidos en el pronóstico
                $pronostico->puntos_obtenidos = $puntos;
                $pronostico->save();

                // Sumar puntos a la participación del usuario en la porra
                Participacion::where('id_usuario', $pronostico->id_usuario)
                    ->where('id_porra', $pronostico->id_porra)
                    ->increment('puntos', $puntos);
            }
        });
    }

    /**
     * Calcula el signo 1X2 a partir de un marcador.
     *
     * @param int $local
     * @param int $visitante
     * @return string
     */
    private function signo(int $local, int $visitante): string
    {
        if ($local > $visitante) {
            return '1';
        }

        if ($local < $visitante) {
            return '2';
        }

        return 'X';
    }
}
