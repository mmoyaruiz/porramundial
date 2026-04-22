<?php

namespace App\Console\Commands;

use App\Models\Competicion;
use App\Models\Partido;
use App\Services\FootballDataClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando pm:importar-mundial
 *
 * Objetivo:
 * - Importar y actualizar partidos de una competición desde la API externa.
 * - Guardar en BD: fecha/hora (convertida a España), estado del partido, fase/grupo,
 *   equipos (nombre/shortname/TLA/escudo) y marcador final (fullTime).
 *
 * Este comando es la base para que la aplicación pueda:
 * - Mostrar partidos y estados actualizados.
 * - Calcular puntos y clasificaciones usando resultados reales.
 *
 * - Se hace "upsert" por api_match_id (si existe se actualiza, si no se crea).
 */
class ImportarPartidosMundial extends Command
{
    /**
     * Parámetros:
     * --code: código de competición en la API (por ejemplo WC).
     * --competicion-id: id_competicion interno (BD).
     * --nombre-competicion: alternativa para localizar id_competicion por nombre.
     * --status: filtro opcional de estado para la consulta a la API.
     */
    protected $signature = 'pm:importar-mundial
        {--code=WC : Código de competición (ej: WC)}
        {--competicion-id= : id_competicion interno}
        {--nombre-competicion= : Buscar id_competicion por nombre (LIKE)}
        {--status= : Filtro de estado (SCHEDULED|TIMED|IN_PLAY|PAUSED|FINISHED...)}';

    protected $description = 'Importa/actualiza partidos desde la API a la tabla partidos';

    /**
     * Ejecuta la importación.
     *
     * @param FootballDataClient $api
     * @return int
     */
    public function handle(FootballDataClient $api): int
    {
        // 1) Resolver id_competicion interno
        $idCompeticion = $this->resolveIdCompeticion();

        if (!$idCompeticion) {
            $this->error('No se pudo resolver id_competicion. Usa --competicion-id o --nombre-competicion.');
            return Command::FAILURE;
        }

        $code = (string) $this->option('code');

        // 2) Construir query opcional (filtro de estado)
        $query = [];
        if ($this->option('status')) {
            $query['status'] = $this->option('status');
        }

        // 3) Llamada a API
        try {
            $payload = $api->competitionMatches($code, $query);
        } catch (\Throwable $e) {
            $this->error('Error consultando la API: ' . $e->getMessage());
            Log::error('API error (competitionMatches)', ['exception' => $e]);
            return Command::FAILURE;
        }

        $matches = $payload['matches'] ?? [];

        $procesados   = 0;
        $creados      = 0;
        $actualizados = 0;

        // 4) Recorrer partidos de la API y guardar en BD
        foreach ($matches as $m) {

            $apiMatchId = $m['id'] ?? null;
            $utcDate    = $m['utcDate'] ?? null;
            $status     = $m['status'] ?? null;
            $stage      = $m['stage'] ?? null;
            $group      = $m['group'] ?? null;

            $homeName  = $m['homeTeam']['name'] ?? null;
            $awayName  = $m['awayTeam']['name'] ?? null;

            // Si faltan datos mínimos, no procesamos este partido
            if (!$apiMatchId || !$utcDate || !$homeName || !$awayName) {
                continue;
            }

            // Datos de equipos
            $homeShort = $m['homeTeam']['shortName'] ?? $homeName;
            $homeTLA   = $m['homeTeam']['tla'] ?? null;
            $homeCrest = $m['homeTeam']['crest'] ?? null;

            $awayShort = $m['awayTeam']['shortName'] ?? $awayName;
            $awayTLA   = $m['awayTeam']['tla'] ?? null;
            $awayCrest = $m['awayTeam']['crest'] ?? null;

            // 5) Convertir UTC -> Europe/Madrid (hora local España)
            $fechaHoraLocal = Carbon::parse($utcDate, 'UTC')
                ->setTimezone('Europe/Madrid')
                ->format('Y-m-d H:i:s');

            // 6) Mapear status de la API -> estado interno de la aplicación
            $estadoInterno = $this->mapStatus($status);

            // 7) Grupo: "GROUP_A" -> "A" (si no hay, queda null)
            $grupo = $group ? str_replace('GROUP_', '', $group) : null;

            // 8) Marcador real (se usa fullTime como resultado válido)
            $golesLocal = $m['score']['fullTime']['home'] ?? null;
            $golesAway  = $m['score']['fullTime']['away'] ?? null;

            // 9) Datos para insertar/actualizar
            $data = [
                'id_competicion' => $idCompeticion,
                'fecha_hora'     => $fechaHoraLocal,
                'estado'         => $estadoInterno,
                'fase'           => $stage,
                'grupo'          => $grupo,

                'equipo_local_nombre'      => $homeName,
                'equipo_local_shortname'   => $homeShort,
                'equipo_local_tla'         => $homeTLA,
                'equipo_local_crest_url'   => $homeCrest,

                'equipo_visitante_nombre'    => $awayName,
                'equipo_visitante_shortname' => $awayShort,
                'equipo_visitante_tla'       => $awayTLA,
                'equipo_visitante_crest_url' => $awayCrest,

                'goles_local'     => $golesLocal,
                'goles_visitante' => $golesAway,
            ];

            // 10) Upsert por api_match_id
            $existing = Partido::where('api_match_id', $apiMatchId)->first();

            if ($existing) {
                $existing->fill($data)->save();
                $actualizados++;
            } else {
                Partido::create(array_merge(['api_match_id' => $apiMatchId], $data));
                $creados++;
            }

            $procesados++;
        }

        $this->info("Importación completada. Procesados: {$procesados} | Creados: {$creados} | Actualizados: {$actualizados}");
        return Command::SUCCESS;
    }

    /**
     * Resuelve el id_competicion interno según las opciones del comando.
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

        // Heurística simple si no se pasa nada
        $c = Competicion::where('nombre', 'like', '%Mundial%')
            ->orWhere('nombre', 'like', '%World Cup%')
            ->first();

        return $c?->id_competicion;
    }

    /**
     * Mapea el estado devuelto por la API a los estados internos del proyecto.
     */
    private function mapStatus(?string $status): string
    {
        return match ($status) {
            'FINISHED'         => 'finalizado',
            'IN_PLAY', 'PAUSED'=> 'en_juego',
            'SCHEDULED', 'TIMED'=> 'programado',
            default            => 'programado',
        };
    }
}