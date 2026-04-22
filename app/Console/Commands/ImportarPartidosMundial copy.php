<?php

namespace App\Console\Commands;

use App\Models\Competicion;
use App\Models\Partido;
use App\Services\FootballDataClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportarPartidosMundial extends Command
{
    /**
     * -code: código de competición en football-data (por ejemplo WC).
     * -competicion-id: id_competicion interno (FK en tu BD).
     * -nombre-competicion: alternativa para localizar id_competicion por nombre (LIKE).
     * -status: opcional (SCHEDULED, FINISHED, etc.) según enums de la API. [2](https://docs.football-data.org/general/v4/match.html)
     */
    protected $signature = 'pm:importar-mundial
        {--code=WC : Código football-data de la competición (ej: WC)}
        {--competicion-id= : id_competicion interno (FK)}
        {--nombre-competicion= : Buscar id_competicion por nombre (LIKE)}
        {--status= : Filtro de estado (SCHEDULED|TIMED|IN_PLAY|PAUSED|FINISHED...)}';

    protected $description = 'Importa/actualiza partidos del Mundial desde football-data.org hacia la tabla partidos';

    public function handle(FootballDataClient $api): int
    {
        // 1) Determinar id_competicion interno (FK)
        $idCompeticion = $this->resolveIdCompeticion();
        if (!$idCompeticion) {
            $this->error('No se pudo resolver id_competicion interno. Usa --competicion-id o --nombre-competicion.');
            return Command::FAILURE;
        }

        $code = (string)$this->option('code');

        // 2) Llamada a la API: Competition / Matches [1](https://www.postman.com/api-noob/football-data-org-apis/request/fk4kujw/competition-matches)
        $query = [];
        if ($this->option('status')) {
            $query['status'] = $this->option('status'); // filtros de match list [2](https://docs.football-data.org/general/v4/match.html)
        }

        try {
            $payload = $api->competitionMatches($code, $query);
        } catch (\Throwable $e) {
            $this->error('Error consultando la API: ' . $e->getMessage());
            Log::error('API football-data error', ['exception' => $e]);
            return Command::FAILURE;
        }

        $matches = $payload['matches'] ?? [];
        $procesados = 0;
        $creados = 0;
        $actualizados = 0;

        foreach ($matches as $m) {
            // Campos del recurso Match: id, utcDate, status, stage, group, teams, score [2](https://docs.football-data.org/general/v4/match.html)
            $apiMatchId = $m['id'] ?? null;
            $utcDate    = $m['utcDate'] ?? null;
            $status     = $m['status'] ?? null;
            $stage      = $m['stage'] ?? null;
            $group      = $m['group'] ?? null;

            $homeName      = $m['homeTeam']['name'] ?? null;
            $homeShort     = $m['homeTeam']['shortName'] ?? $homeName;
            $homeTLA       = $m['homeTeam']['tla'] ?? null;
            $homeCrest     = $m['homeTeam']['crest'] ?? null;

            $awayName      = $m['awayTeam']['name'] ?? null;
            $awayShort     = $m['awayTeam']['shortName'] ?? $awayName;
            $awayTLA       = $m['awayTeam']['tla'] ?? null;
            $awayCrest     = $m['awayTeam']['crest'] ?? null;

            

            if (!$apiMatchId || !$utcDate || !$homeName || !$awayName) {
                continue;
            }

            // 3) Convertir UTC -> Europe/Madrid (hora local España)
            $fechaHoraLocal = Carbon::parse($utcDate, 'UTC')->setTimezone('Europe/Madrid')->format('Y-m-d H:i:s');

            // 4) Mapear status API -> estado interno
            $estadoInterno = $this->mapStatus($status);

            // 5) Grupo: GROUP_A -> A (o NULL si eliminatoria)
            $grupo = $group ? str_replace('GROUP_', '', $group) : null;

            // 6) Marcador válido para la porra: fullTime (90 minutos).
            // La API expone score.fullTime.home/away. [2](https://docs.football-data.org/general/v4/match.html)
            $golesLocal = $m['score']['fullTime']['home'] ?? null;
            $golesAway  = $m['score']['fullTime']['away'] ?? null;

            // 7) Upsert por api_match_id (debes tener UNIQUE en BD)
            $existing = Partido::where('api_match_id', $apiMatchId)->first();

            $data = [
                'id_competicion' => $idCompeticion,
                'fecha_hora' => $fechaHoraLocal,
                'estado' => $estadoInterno,
                'fase' => $stage,
                'grupo' => $grupo,

                'equipo_local_nombre' => $homeName,
                'equipo_local_shortname' => $homeShort,
                'equipo_local_tla' => $homeTLA,
                'equipo_local_crest_url' => $homeCrest,

                'equipo_visitante_nombre' => $awayName,
                'equipo_visitante_shortname' => $awayShort,
                'equipo_visitante_tla' => $awayTLA,
                'equipo_visitante_crest_url' => $awayCrest,

                'goles_local' => $golesLocal,
                'goles_visitante' => $golesAway,
            ];

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

    private function resolveIdCompeticion(): ?int
    {
        if ($this->option('competicion-id')) {
            return (int)$this->option('competicion-id');
        }

        if ($this->option('nombre-competicion')) {
            $needle = (string)$this->option('nombre-competicion');
            $c = Competicion::where('nombre', 'like', "%{$needle}%")->first();
            return $c?->id_competicion;
        }

        // Heurística por defecto: intentar encontrar "Mundial" o "World Cup"
        $c = Competicion::where('nombre', 'like', '%Mundial%')->orWhere('nombre', 'like', '%World Cup%')->first();
        return $c?->id_competicion;
    }

    private function mapStatus(?string $status): string
    {
        // Enums de status en football-data: SCHEDULED, TIMED, IN_PLAY, PAUSED, FINISHED... [2](https://docs.football-data.org/general/v4/match.html)
        return match ($status) {
            'FINISHED' => 'finalizado',
            'IN_PLAY', 'PAUSED' => 'en_juego',
            'SCHEDULED', 'TIMED' => 'programado',
            default => 'programado',
        };
    }
}