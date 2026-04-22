<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FootballDataClient
{
    /**
     * GET /v4/competitions/{code}/matches
     * Devuelve el JSON completo de partidos de una competición. [1](https://www.postman.com/api-noob/football-data-org-apis/request/fk4kujw/competition-matches)
     */
    public function competitionMatches(string $code, array $query = []): array
    {
        $url = rtrim(config('services.football_data.base_url'), '/') . "/competitions/{$code}/matches";

        return Http::withHeaders([
                // football-data.org usa X-Auth-Token [2](https://docs.football-data.org/general/v4/match.html)
                'X-Auth-Token' => config('services.football_data.token'),
            ])
            ->acceptJson()
            ->retry(2, 300)          // reintentos suaves por si hay rate limiting intermitente
            ->get($url, $query)
            ->throw()
            ->json();
    }

    /**
     * GET /v4/competitions
     * Útil para depurar o localizar códigos disponibles. [3](https://www.postman.com/api-noob/workspace/football-data-org-apis/request/15523775-ff8904ee-a114-4dfb-8afd-fa27f347fce2)
     */
    public function competitions(): array
    {
        $url = rtrim(config('services.football_data.base_url'), '/') . "/competitions";

        return Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.token'),
            ])
            ->acceptJson()
            ->retry(2, 300)
            ->get($url)
            ->throw()
            ->json();
    }



    public function competitionStandings(string $code, array $query = []): array
{
    $url = rtrim(config('services.football_data.base_url'), '/') . "/competitions/{$code}/standings";

    return \Illuminate\Support\Facades\Http::withHeaders([
            'X-Auth-Token' => config('services.football_data.token'),
        ])
        ->acceptJson()
        ->retry(2, 300)
        ->get($url, $query)
        ->throw()
        ->json();
}
}