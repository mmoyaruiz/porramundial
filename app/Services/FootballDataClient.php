<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Servicio FootballDataClient
 *
 * Encapsula las llamadas HTTP a la API externa para no repetir código en los comandos.
 *
 * Idea:
 * - Centralizar la construcción de URL y cabeceras.
 * - Dejar en un único sitio el token y la base_url (config/services.php + .env).
 * - Devolver siempre el JSON como array.
 */
class FootballDataClient
{
    /**
     * Devuelve todos los partidos de una competición.
     *
     * @param string $code  Código de competición (ej: WC)
     * @param array  $query Parámetros opcionales (ej: status)
     */
    public function competitionMatches(string $code, array $query = []): array
    {
        return $this->get("/competitions/{$code}/matches", $query);
    }

    /**
     * Devuelve el listado de competiciones (útil para pruebas / depuración).
     */
    public function competitions(): array
    {
        return $this->get('/competitions');
    }

    /**
     * Devuelve la clasificación (standings) de una competición.
     * Se usa en el proyecto para obtener campeones de grupo.
     *
     * @param string $code  Código de competición (ej: WC)
     * @param array  $query Parámetros opcionales
     */
    public function competitionStandings(string $code, array $query = []): array
    {
        return $this->get("/competitions/{$code}/standings", $query);
    }

    /**
     * Método interno común para hacer GET.
     * Así evitamos duplicar la misma llamada Http::withHeaders()->get()->json() en cada método.
     */
    private function get(string $path, array $query = []): array
    {
        $base = rtrim(config('services.football_data.base_url'), '/');
        $url  = $base . $path;

        return Http::withHeaders([
                // La API usa un header con el token
                'X-Auth-Token' => config('services.football_data.token'),
            ])
            ->acceptJson()
            ->retry(2, 300)   // reintento suave por fallos puntuales o rate limiting intermitente
            ->get($url, $query)
            ->throw()
            ->json();
    }
}