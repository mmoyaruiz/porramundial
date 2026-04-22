<?php

namespace App\Http\Controllers;

use App\Http\Requests\PorraStoreRequest;
use App\Models\Competicion;
use App\Models\Participacion;
use App\Models\Porra;
use App\Models\Partido;
use App\Models\Pronostico;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Controlador PorraController
 *
 * Este controlador se encarga de la gestión principal de las porras:
 * - Listar las porras en las que participa un usuario (pantalla 8.6).
 * - Listar las porras que administra el usuario (pantalla 8.14).
 * - Crear nuevas porras (pantalla 8.13).
 * - Mostrar la página principal de una porra concreta (pantalla 8.7).
 */
class PorraController extends Controller
{
    /**
     * 8.6 – Mis porras
     *
     * Muestra todas las porras en las que participa el usuario autenticado.
     * Para cada porra se calculan datos adicionales que se muestran en la vista:
     * - Número de participantes.
     * - Posición del usuario en la clasificación.
     * - Número de pronósticos pendientes.
     * - Tiempo aproximado hasta el primer partido pendiente.
     */
    public function misPorras()
    {
        // Usuario autenticado por sesión
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        // 1) Obtener las porras en las que participa el usuario
        $porras = Porra::select('porras.*')
            ->join('participaciones', 'participaciones.id_porra', '=', 'porras.id_porra')
            ->where('participaciones.id_usuario', $usuario->id_usuario)
            ->get();

        // 2) Añadir información adicional a cada porra para la vista
        foreach ($porras as $porra) {

            // Número total de participantes
            $porra->num_participantes = Participacion::where('id_porra', $porra->id_porra)->count();

            // Posición del usuario en la clasificación de la porra
            $ranking = Participacion::where('id_porra', $porra->id_porra)
                ->orderByDesc('puntos')
                ->pluck('id_usuario');

            $posicion = $ranking->search($usuario->id_usuario);
            $porra->mi_posicion = ($posicion !== false) ? $posicion + 1 : '-';

            // Pronósticos pendientes del usuario en partidos futuros
            $partidosFuturos = Partido::where('id_competicion', $porra->id_competicion)
                ->where('fecha_hora', '>', now())
                ->pluck('id_partido');

            $pronosticados = Pronostico::where('id_usuario', $usuario->id_usuario)
                ->where('id_porra', $porra->id_porra)
                ->pluck('id_partido');

            $porra->pronosticos_pendientes = $partidosFuturos->diff($pronosticados)->count();

            // Tiempo aproximado hasta el primer partido pendiente
            $primerPartido = Partido::where('id_competicion', $porra->id_competicion)
                ->where('fecha_hora', '>', now())
                ->orderBy('fecha_hora')
                ->first();

            if ($primerPartido) {
                $porra->horas_hasta_cierre = Carbon::now()->diffForHumans(
                    Carbon::parse($primerPartido->fecha_hora),
                    ['short' => true, 'parts' => 2]
                );
            } else {
                $porra->horas_hasta_cierre = '-';
            }
        }

        return view('porras.mis', compact('porras'));
    }

    /**
     * 8.14 – Porras que administro
     *
     * Muestra únicamente las porras en las que el usuario es administrador.
     * El rol de administrador se gestiona a nivel de participación en la porra.
     */
    public function administro()
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porras = Porra::select('porras.*')
            ->join('participaciones', 'participaciones.id_porra', '=', 'porras.id_porra')
            ->where('participaciones.id_usuario', $usuario->id_usuario)
            ->where('participaciones.es_admin', 1)
            ->with('competicion')
            ->orderByDesc('porras.fecha_creacion')
            ->get();

        return view('porras.admin', compact('porras'));
    }

    /**
     * 8.13 – Crear porra
     *
     * Muestra el formulario para crear una nueva porra.
     * Es necesario que existan competiciones registradas en el sistema.
     */
    public function create()
    {
        $competiciones = Competicion::orderBy('nombre')->get();
        return view('porras.create', compact('competiciones'));
    }

    /**
     * Guardar porra
     *
     * Crea una nueva porra y registra automáticamente al usuario creador
     * como participante administrador de la misma.
     *
     * Se utiliza una transacción para asegurar que ambas operaciones
     * se realizan de forma consistente.
     */
    public function store(PorraStoreRequest $request)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        DB::transaction(function () use ($request, $usuario) {

            // Crear la porra con su configuración
            $porra = Porra::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'id_competicion' => $request->id_competicion,
                'id_usuario_creador' => $usuario->id_usuario,
                'es_publica' => $request->boolean('es_publica'),
                'max_participantes' => $request->max_participantes,
                'puntos_ganador' => $request->puntos_ganador,
                'puntos_marcador' => $request->puntos_marcador,
                'puntos_campeon_grupo' => $request->puntos_campeon_grupo,
                'puntos_ganador_torneo' => $request->puntos_ganador_torneo,
                'estado' => 'activa',
            ]);

            // Registrar al creador como administrador de la porra
            Participacion::create([
                'id_usuario' => $usuario->id_usuario,
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
     * 8.7 – Pantalla principal de una porra
     *
     * Muestra la información general de la porra:
     * - Clasificación actual.
     * - Próximos partidos de la competición.
     * - Estado de los pronósticos del usuario.
     */
    public function show($id)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        // Cargar la porra y su competición
        $porra = Porra::with(['competicion', 'creador'])->findOrFail($id);

        // Clasificación de la porra ordenada por puntos
        $clasificacion = Participacion::where('participaciones.id_porra', $porra->id_porra)
            ->join('usuarios', 'usuarios.id_usuario', '=', 'participaciones.id_usuario')
            ->orderByDesc('participaciones.puntos')
            ->select(
                'usuarios.id_usuario',
                'usuarios.nombre_usuario',
                'participaciones.puntos',
                'participaciones.posicion'
            )
            ->get();

        // Próximos partidos de la competición
        $proximosPartidos = Partido::where('id_competicion', $porra->id_competicion)
            ->where('fecha_hora', '>', now())
            ->orderBy('fecha_hora')
            ->limit(20)
            ->get();

        // Pronósticos del usuario para esta porra
        $misPronosticos = Pronostico::where('id_usuario', $usuario->id_usuario)
            ->where('id_porra', $porra->id_porra)
            ->get()
            ->keyBy('id_partido');

        return view('porras.show', compact(
            'porra',
            'clasificacion',
            'proximosPartidos',
            'misPronosticos'
        ));
    }
}