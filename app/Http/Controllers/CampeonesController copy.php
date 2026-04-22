<?php

namespace App\Http\Controllers;

use App\Models\Porra;
use App\Models\Partido;
use App\Models\PronosticoCampeon;
use Illuminate\Http\Request;
use App\Models\Participacion;


class CampeonesController extends Controller
{
    /**
     * GET /porras/{id}/campeones
     * Pantalla: Enviar o modificar campeones (grupo + campeón competición).
     */
    public function edit($id)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($id);

        // 1) Opciones para campeón absoluto (todos los equipos en la competición)
        $equiposCompeticion = $this->equiposDeCompeticion($porra->id_competicion);

        // 2) Opciones por grupo (A, B, C... según datos importados)
        $equiposPorGrupo = $this->equiposPorGrupo($porra->id_competicion);

        // 3) Precargar pronósticos existentes del usuario en ESTA porra
        $existentes = PronosticoCampeon::where('id_porra', $porra->id_porra)
            ->where('id_usuario', $usuario->id_usuario)
            ->get();

        // campeón de competición
        $campeonCompeticion = $existentes->firstWhere('tipo_pronostico', 'competicion');

        // campeones de grupo indexados por grupo
        $campeonesGrupo = $existentes
            ->where('tipo_pronostico', 'grupo')
            ->keyBy('grupo');

        $competicionComenzada = $this->competicionHaComenzado($porra->id_competicion);

        return view('porras.campeones', compact(
            'porra',
            'equiposCompeticion',
            'equiposPorGrupo',
            'campeonCompeticion',
            'campeonesGrupo',
            'competicionComenzada'
        ));
    }

    /**
     * POST /porras/{id}/campeones
     * Guarda o actualiza campeón absoluto y campeones de grupo.
     */
    public function update(Request $request, $id)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($id);




        if ($this->competicionHaComenzado($porra->id_competicion)) {
            return redirect()
                ->route('porras.campeones', $porra->id_porra)
                ->with('warning', 'La competición ha comenzado, por lo que no es posible modificar los pronósticos de campeones.');
        }


        // Reconstruimos opciones permitidas (para validar bien)
        $equiposCompeticion = $this->equiposDeCompeticion($porra->id_competicion);
        $equiposPorGrupo = $this->equiposPorGrupo($porra->id_competicion);

        // Validación base (estructura)
        $request->validate([
            'campeon_competicion' => 'nullable|string|max:100',
            'campeones_grupo' => 'nullable|array',
            'campeones_grupo.*' => 'nullable|string|max:100',
        ]);

        // 1) Validar que el campeón de competición esté en las opciones
        $campeonCompeticion = $request->input('campeon_competicion');
        if ($campeonCompeticion && !in_array($campeonCompeticion, $equiposCompeticion, true)) {
            return back()->withErrors([
                'campeon_competicion' => 'El equipo seleccionado no es válido para campeón de competición.',
            ])->withInput();
        }

        // 2) Validar campeones de grupo
        $campeonesGrupo = $request->input('campeones_grupo', []);
        foreach ($campeonesGrupo as $grupo => $equipo) {
            if ($equipo === null || $equipo === '') {
                continue;
            }
            if (!isset($equiposPorGrupo[$grupo]) || !in_array($equipo, $equiposPorGrupo[$grupo], true)) {
                return back()->withErrors([
                    "campeones_grupo.$grupo" => "El equipo seleccionado no es válido para el grupo $grupo.",
                ])->withInput();
            }
        }

        // 3) Guardar / actualizar (sin duplicar)
        // Campeón competición (tipo_pronostico='competicion', grupo=NULL)
        if ($campeonCompeticion) {
            PronosticoCampeon::updateOrCreate(
                [
                    'id_usuario' => $usuario->id_usuario,
                    'id_porra' => $porra->id_porra,
                    'tipo_pronostico' => 'competicion',
                    'grupo' => null,
                ],
                [
                    'equipo_pronosticado' => $campeonCompeticion,
                    'puntos_obtenidos' => 0,
                ]
            );
        } else {
            // si se deja vacío, opcionalmente lo borramos
            PronosticoCampeon::where('id_usuario', $usuario->id_usuario)
                ->where('id_porra', $porra->id_porra)
                ->where('tipo_pronostico', 'competicion')
                ->delete();
        }

        // Campeones de grupo (tipo_pronostico='grupo', grupo='A'..)
        foreach ($equiposPorGrupo as $grupo => $_) {
            $equipo = $campeonesGrupo[$grupo] ?? null;

            if ($equipo) {
                PronosticoCampeon::updateOrCreate(
                    [
                        'id_usuario' => $usuario->id_usuario,
                        'id_porra' => $porra->id_porra,
                        'tipo_pronostico' => 'grupo',
                        'grupo' => $grupo,
                    ],
                    [
                        'equipo_pronosticado' => $equipo,
                        'puntos_obtenidos' => 0,
                    ]
                );
            } else {
                // si se deja vacío, lo borramos
                PronosticoCampeon::where('id_usuario', $usuario->id_usuario)
                    ->where('id_porra', $porra->id_porra)
                    ->where('tipo_pronostico', 'grupo')
                    ->where('grupo', $grupo)
                    ->delete();
            }
        }

        return redirect()
            ->route('porras.show', $porra->id_porra)
            ->with('success', 'Pronósticos de campeones guardados correctamente.');
    }

    /**
     * Devuelve lista única de equipos (orden alfabético) para una competición.
     * Usamos la tabla partidos importada desde la API (local + visitante).
     */
    private function equiposDeCompeticion(int $idCompeticion): array
    {
        $locals = Partido::where('id_competicion', $idCompeticion)
            ->whereNotNull('equipo_local_nombre')
            ->pluck('equipo_local_nombre')
            ->toArray();

        $aways = Partido::where('id_competicion', $idCompeticion)
            ->whereNotNull('equipo_visitante_nombre')
            ->pluck('equipo_visitante_nombre')
            ->toArray();

        $equipos = array_values(array_unique(array_merge($locals, $aways)));
        sort($equipos, SORT_LOCALE_STRING);

        return $equipos;
    }

    /**
     * Devuelve array: ['A' => [equipos...], 'B' => [equipos...], ...]
     * Para cada grupo, los equipos se deducen de los partidos de ese grupo.
     */
    private function equiposPorGrupo(int $idCompeticion): array
    {
        $grupos = Partido::where('id_competicion', $idCompeticion)
            ->whereNotNull('grupo')
            ->pluck('grupo')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $resultado = [];

        foreach ($grupos as $g) {
            $locals = Partido::where('id_competicion', $idCompeticion)
                ->where('grupo', $g)
                ->pluck('equipo_local_nombre')
                ->toArray();

            $aways = Partido::where('id_competicion', $idCompeticion)
                ->where('grupo', $g)
                ->pluck('equipo_visitante_nombre')
                ->toArray();

            $equipos = array_values(array_unique(array_merge($locals, $aways)));
            sort($equipos, SORT_LOCALE_STRING);

            $resultado[$g] = $equipos;
        }

        return $resultado;
    }




    public function verCampeonesParticipantes($id)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($id);

        // 1) Comprobar si la competición ha comenzado
        $competicionComenzada = $this->competicionHaComenzado($porra->id_competicion);



        $participantes = collect();
        $pronosticos = collect();
        $grupos = collect();
        $mapNombreATla = [];





        //if (!$competicionComenzada) {
        //    return redirect()
        //        ->route('porras.show', $porra->id_porra)
        //        ->with('warning', 'Los pronósticos de campeones solo se pueden consultar cuando la competición ha comenzado.');
        //}



        if ($competicionComenzada) {

            // 2) Participantes (orden clasificación)
            $participantes = Participacion::where('id_porra', $porra->id_porra)
                ->join('usuarios', 'usuarios.id_usuario', '=', 'participaciones.id_usuario')
                ->orderBy('participaciones.posicion')
                ->select(
                    'usuarios.id_usuario',
                    'usuarios.nombre_usuario'
                )
                ->get();

            // 3) Pronósticos de campeones (todos)
            $pronosticos = \App\Models\PronosticoCampeon::where('id_porra', $porra->id_porra)
                ->get()
                ->groupBy('id_usuario');

            // 4) Grupos existentes (A, B, C…)
            $grupos = \App\Models\Partido::where('id_competicion', $porra->id_competicion)
                ->whereNotNull('grupo')
                ->pluck('grupo')
                ->unique()
                ->sort()
                ->values();


            // Mapa nombre => TLA usando la tabla partidos (importada desde API)
            $mapNombreATla = [];

            // Local
            $locals = \App\Models\Partido::where('id_competicion', $porra->id_competicion)
                ->whereNotNull('equipo_local_nombre')
                ->whereNotNull('equipo_local_tla')
                ->get(['equipo_local_nombre', 'equipo_local_tla']);

            foreach ($locals as $row) {
                $mapNombreATla[$row->equipo_local_nombre] = $row->equipo_local_tla;
            }

            // Visitante
            $aways = \App\Models\Partido::where('id_competicion', $porra->id_competicion)
                ->whereNotNull('equipo_visitante_nombre')
                ->whereNotNull('equipo_visitante_tla')
                ->get(['equipo_visitante_nombre', 'equipo_visitante_tla']);

            foreach ($aways as $row) {
                $mapNombreATla[$row->equipo_visitante_nombre] = $row->equipo_visitante_tla;
            }
        }

        return view('porras.campeones_participantes', compact(
            'porra',
            'participantes',
            'pronosticos',
            'grupos',
            'mapNombreATla',
            'competicionComenzada'
        ));
    }



    /**
     * Determina si la competición ha comenzado.
     * Criterio: si existe algún partido cuyo estado != 'programado'
     */
    private function competicionHaComenzado(int $idCompeticion): bool
    {
        return \App\Models\Partido::where('id_competicion', $idCompeticion)
            ->where('estado', '!=', 'programado')
            ->exists();
    }
}
