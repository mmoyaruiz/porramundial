<?php

namespace App\Http\Controllers;

use App\Http\Requests\PorraStoreRequest;
use App\Models\Competicion;
use App\Models\Participacion;
use App\Models\Porra;
//use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Partido;
use App\Models\Pronostico;
use Carbon\Carbon;

/**
 * Controlador de Porras.
 *
 * Pantallas punto 8 (ERS):
 * - 8.6 Mis porras (participaciones)
 * - 8.13 Crear porra
 * - 8.14 Porras que administro
 * - 8.7 Pantalla general de una porra
 * - 8.15 Invitar participantes (se cubre en controlador Invitacion, pero se enlaza desde aquí)
 * 
 */
class PorraController extends Controller
{
    /**
     * 8.6 - Listado de porras en las que participo (CU6).
     */

    public function misPorras()
    {
        // ✅ Usuario autenticado (autenticación manual)


        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }
        $idUsuario = $usuario->id_usuario;



        /**
         * 1️⃣ Obtener porras en las que participa el usuario
         */
        $porras = Porra::select('porras.*')
            ->join('participaciones', 'participaciones.id_porra', '=', 'porras.id_porra')
            ->where('participaciones.id_usuario', $usuario->id_usuario)
            ->get();

        /**
         * 2️⃣ Enriquecer cada porra con los datos necesarios para la vista 8.6
         */
        foreach ($porras as $porra) {

            // ─────────────────────────────────────────────
            // Nº PARTICIPANTES
            // ─────────────────────────────────────────────
            $porra->num_participantes = Participacion::where('id_porra', $porra->id_porra)
                ->count();

            // ─────────────────────────────────────────────
            // MI POSICIÓN EN LA PORRA
            // (ordenado por puntos descendente)
            // ─────────────────────────────────────────────
            $ranking = Participacion::where('id_porra', $porra->id_porra)
                ->orderByDesc('puntos')
                ->pluck('id_usuario');

            $posicion = $ranking->search($usuario->id_usuario);

            $porra->mi_posicion = $posicion !== false
                ? $posicion + 1
                : '-';

            // ─────────────────────────────────────────────
            // PRONÓSTICOS PENDIENTES
            // ─────────────────────────────────────────────

            // Partidos futuros de la competición de la porra
            $partidosFuturos = Partido::where('id_competicion', $porra->id_competicion)
                ->where('fecha_hora', '>', now())
                ->pluck('id_partido');

            // Partidos ya pronosticados por el usuario en esta porra
            $pronosticados = Pronostico::where('id_usuario', $usuario->id_usuario)
                ->where('id_porra', $porra->id_porra)
                ->pluck('id_partido');

            $porra->pronosticos_pendientes =
                $partidosFuturos->diff($pronosticados)->count();

            // ─────────────────────────────────────────────
            // HORAS HASTA CIERRE
            // (primer partido futuro de la competición)
            // ─────────────────────────────────────────────
            $primerPartido = Partido::where('id_competicion', $porra->id_competicion)
                ->where('fecha_hora', '>', now())
                ->orderBy('fecha_hora')
                ->first();

            if ($primerPartido) {
                $porra->horas_hasta_cierre = Carbon::now()
                    ->diffForHumans(
                        Carbon::parse($primerPartido->fecha_hora),
                        ['short' => true, 'parts' => 2]
                    );
            } else {
                $porra->horas_hasta_cierre = '-';
            }
        }

        /**
         * 3️⃣ Enviar a la vista
         */
        return view('porras.mis', compact('porras'));
    }


    /**
     * 8.14 - Listado de porras que administro (CU11).
     * En tu BD el rol admin se expresa por participaciones.es_admin=1. [1](https://moleaer-my.sharepoint.com/personal/miguel_moleaer_com/Documents/Microsoft%20Copilot%20Chat%20Files/CREACION%20BASE%20DE%20DATOS.txt)
     */
    public function administro()
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }
        $idUsuario = $usuario->id_usuario;

        $porras = Porra::query()
            ->select('porras.*')
            ->join('participaciones', 'participaciones.id_porra', '=', 'porras.id_porra')
            ->where('participaciones.id_usuario', $idUsuario)
            ->where('participaciones.es_admin', 1)
            ->with(['competicion'])
            ->orderByDesc('porras.fecha_creacion')
            ->get();

        return view('porras.admin', compact('porras'));
    }

    /**
     * 8.13 - Formulario de creación de nueva porra (CU9).
     * Debe existir al menos una competición. 
     */
    public function create()
    {
        $competiciones = Competicion::orderBy('nombre')->get();
        return view('porras.create', compact('competiciones'));
    }

    /**
     * Guardar porra (CU9) + crear participación admin del creador.
     */
    public function store(PorraStoreRequest $request)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }
        $idUsuario = $usuario->id_usuario;

        DB::transaction(function () use ($request, $idUsuario) {
            $porra = Porra::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'id_competicion' => $request->id_competicion,
                'id_usuario_creador' => $idUsuario,
                'es_publica' => $request->boolean('es_publica'),
                'max_participantes' => $request->max_participantes,

                'puntos_ganador' => $request->puntos_ganador,
                'puntos_marcador' => $request->puntos_marcador,
                'puntos_campeon_grupo' => $request->puntos_campeon_grupo,
                'puntos_ganador_torneo' => $request->puntos_ganador_torneo,

                'estado' => 'activa',
            ]);

            // El creador pasa a ser "admin" de la porra (modelo de tu BD).
            Participacion::create([
                'id_usuario' => $idUsuario,
                'id_porra' => $porra->id_porra,
                'es_admin' => 1,
                'puntos' => 0,
            ]);
        });

        return redirect()
            ->route('porras.admin')
            ->with('success', 'Porra creada correctamente.');
    }

    /**
     * 8.7 - Pantalla general de una porra determinada.
     * En tu diseño debe mostrar clasificación + próximos partidos + estado pronósticos (lo iremos completando). 
     */
    public function show($id)
    {



        // Usuario autenticado por sesión (tu sistema actual)
        $usuario = session('usuario');

        // Seguridad defensiva (por si alguna ruta entra sin middleware)
        if (!$usuario) {
            return redirect()->route('login');
        }


        $porra = Porra::with(['competicion', 'creador'])->findOrFail($id);

        // (Fase siguiente) aquí se cargarán:
        // - clasificación: participaciones ordenadas por puntos


        $clasificacion = Participacion::where('participaciones.id_porra', $porra->id_porra)
            ->join('usuarios', 'usuarios.id_usuario', '=', 'participaciones.id_usuario')
            ->orderByDesc('participaciones.puntos')
            ->select(
                'usuarios.id_usuario as id_usuario',
                'usuarios.nombre_usuario',
                'participaciones.puntos',
                'participaciones.posicion'
            )
            ->get();



        // - próximos partidos: partidos de la competición
        // - estado de pronósticos: para el usuario logueado, si ha pronosticado o no cada partido próximo
        $proximosPartidos = Partido::where('id_competicion', $porra->id_competicion)
            ->where('fecha_hora', '>', now())
            ->orderBy('fecha_hora')
            ->limit(10)
            ->get();

        // Pronósticos del usuario autenticado para esta porra
        $misPronosticos = Pronostico::where('id_usuario', $usuario->id_usuario)
            ->where('id_porra', $porra->id_porra)
            ->get()
            ->keyBy('id_partido');

        // - estado de pronósticos del usuario
        return view('porras.show', compact(
            'porra',
            'clasificacion',
            'proximosPartidos',
            'misPronosticos'
        ));
    }
}
