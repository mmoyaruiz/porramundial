<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Desactivar comprobación de claves foráneas (MySQL / MariaDB)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Vaciar tablas (orden: hijas → padres)
        DB::table('invitaciones')->truncate();
        DB::table('pronosticos_campeones')->truncate();
        DB::table('pronosticos')->truncate();
        DB::table('participaciones')->truncate();
        DB::table('campeones_reales')->truncate();
        DB::table('porras')->truncate();
        DB::table('partidos')->truncate();
        DB::table('competiciones')->truncate();
        DB::table('usuarios')->truncate();

        // Reactivar claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Ejecutar seeders (orden: padres → hijas)
        $this->call([
            UsuariosSeeder::class,
            CompeticionesSeeder::class,
            PartidosSeeder::class,
            PorrasSeeder::class,
            ParticipacionesSeeder::class,
            PronosticosSeeder::class,
            PronosticosCampeonesSeeder::class,
            InvitacionesSeeder::class,
        ]);
    }
}
