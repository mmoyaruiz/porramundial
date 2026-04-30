<?php

namespace App\Http\Controllers;

use App\Models\Porra;
use App\Models\Partido;
use App\Models\PronosticoCampeon;
use App\Models\Participacion;
use Illuminate\Http\Request;

/**
 * Controlador CampeonesController
 *
 * Gestiona los pronósticos especiales de campeones:
 * - Campeón del torneo.
 * - Campeón de cada grupo.
 *
 * Funcionalidades asociadas 
 * - Pantalla para enviar/modificar campeones (similar a pronósticos de partidos).
 * - Pantalla para consultar pronósticos de campeones del resto de participantes.
 *
 * Nota: la autenticación del proyecto es manual por sesión.
 */
class CampeonesController extends Controller
{
    /**
     * Muestra la pantalla para enviar o modificar campeones.
     *
     * - Carga listas de equipos disponibles (competición completa y por grupos).
     * - Precarga los pronósticos ya guardados por el usuario.
     * - Calcula si la competición ha comenzado para bloquear edición cuando corresponda.
     */
    public function edit($id)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($id);

        // Opciones para desplegables
        $equiposCompeticion = $this->equiposDeCompeticion($porra->id_competicion);
        $equiposPorGrupo    = $this->equiposPorGrupo($porra->id_competicion);

        // Pronósticos existentes del usuario en esta porra (para precargar)
        $existentes = PronosticoCampeon::where('id_porra', $porra->id_porra)
            ->where('id_usuario', $usuario->id_usuario)
            ->get();

        $campeonCompeticion = $existentes->firstWhere('tipo_pronostico', 'competicion');

        $campeonesGrupo = $existentes
            ->where('tipo_pronostico', 'grupo')
            ->keyBy('grupo');

        // Se usa para bloquear edición si ya ha empezado la competición
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
     * Guarda los pronósticos de campeones (campeón del torneo y campeones de grupo).
     *
     * - Valida que las selecciones pertenecen al listado permitido.
     * - Evita modificaciones si la competición ya ha comenzado.
     * - Usa updateOrCreate para no duplicar registros.
     */
    public function update(Request $request, $id)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($id);

        // Si la competición ha comenzado, se impide modificar (solo consulta)
        if ($this->competicionHaComenzado($porra->id_competicion)) {
            return redirect()
                ->route('porras.campeones', $porra->id_porra)
                ->with('warning', 'La competición ha comenzado, por lo que no es posible modificar los pronósticos de campeones.');
        }

        // Listas permitidas para validación
        $equiposCompeticion = $this->equiposDeCompeticion($porra->id_competicion);
        $equiposPorGrupo    = $this->equiposPorGrupo($porra->id_competicion);

        // Validación base de estructura
        $request->validate([
            'campeon_competicion' => 'nullable|string|max:100',
            'campeones_grupo'     => 'nullable|array',
            'campeones_grupo.*'   => 'nullable|string|max:100',
        ]);

        // 1) Validar campeón del torneo
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

        // 3) Guardar campeón del torneo (tipo=competicion)
        if ($campeonCompeticion) {
            PronosticoCampeon::updateOrCreate(
                [
                    'id_usuario'      => $usuario->id_usuario,
                    'id_porra'        => $porra->id_porra,
                    'tipo_pronostico' => 'competicion',
                    'grupo'           => null,
                ],
                [
                    'equipo_pronosticado' => $campeonCompeticion,
                    'puntos_obtenidos'    => 0,
                ]
            );
        } else {
            // Si se deja vacío, se elimina el registro (opcional)
            PronosticoCampeon::where('id_usuario', $usuario->id_usuario)
                ->where('id_porra', $porra->id_porra)
                ->where('tipo_pronostico', 'competicion')
                ->delete();
        }

        // 4) Guardar campeones de grupo (tipo=grupo)
        foreach ($equiposPorGrupo as $grupo => $_) {
            $equipo = $campeonesGrupo[$grupo] ?? null;

            if ($equipo) {
                PronosticoCampeon::updateOrCreate(
                    [
                        'id_usuario'      => $usuario->id_usuario,
                        'id_porra'        => $porra->id_porra,
                        'tipo_pronostico' => 'grupo',
                        'grupo'           => $grupo,
                    ],
                    [
                        'equipo_pronosticado' => $equipo,
                        'puntos_obtenidos'    => 0,
                    ]
                );
            } else {
                // Si se deja vacío, se elimina el registro (opcional)
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
     * Muestra la pantalla de consulta de campeones de otros participantes.
     *
     * Comportamiento:
     * - Si la competición no ha comenzado, la vista se carga igualmente,
     *   pero sin datos (y se muestra un aviso en la propia vista).
     * - Si la competición ha comenzado, se cargan participantes, pronósticos y grupos.
     */
    public function verCampeonesParticipantes($id)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($id);

        $competicionComenzada = $this->competicionHaComenzado($porra->id_competicion);

        // Por defecto: colecciones vacías (para permitir cargar la vista siempre)
        $participantes   = collect();
        $pronosticos     = collect();
        $grupos          = collect();
        $mapNombreATla   = [];

        if ($competicionComenzada) {

            // Participantes ordenados por su posición en la porra
            $participantes = Participacion::where('id_porra', $porra->id_porra)
                ->join('usuarios', 'usuarios.id_usuario', '=', 'participaciones.id_usuario')
                ->orderBy('participaciones.posicion')
                ->select('usuarios.id_usuario', 'usuarios.nombre_usuario')
                ->get();

            // Pronósticos de campeones agrupados por usuario
            $pronosticos = PronosticoCampeon::where('id_porra', $porra->id_porra)
                ->get()
                ->groupBy('id_usuario');

            // Lista de grupos existentes en la competición (según partidos importados)
            $grupos = Partido::where('id_competicion', $porra->id_competicion)
                ->whereNotNull('grupo')
                ->pluck('grupo')
                ->unique()
                ->sort()
                ->values();

            // Mapa nombre -> TLA (para mostrar TLA aunque en BD se guarde nombre)
            $locals = Partido::where('id_competicion', $porra->id_competicion)
                ->whereNotNull('equipo_local_nombre')
                ->whereNotNull('equipo_local_tla')
                ->get(['equipo_local_nombre', 'equipo_local_tla']);

            foreach ($locals as $row) {
                $mapNombreATla[$row->equipo_local_nombre] = $row->equipo_local_tla;
            }

            $aways = Partido::where('id_competicion', $porra->id_competicion)
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
     * Devuelve una lista única de equipos de la competición (orden alfabético).
     * Se obtiene de la tabla partidos (local + visitante) para no depender de llamadas adicionales.
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
     * Devuelve los equipos de cada grupo en formato:
     * ['A' => [...], 'B' => [...], ...]
     *
     * Los equipos se deducen a partir de los partidos del grupo.
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

    /**
     * Determina si la competición ha comenzado.
     * Se considera comenzada si existe al menos un partido que ya no esté "programado".
     */
    private function competicionHaComenzado(int $idCompeticion): bool
    {
        return Partido::where('id_competicion', $idCompeticion)
            ->where('estado', '!=', 'programado')
            ->exists();
    }
}