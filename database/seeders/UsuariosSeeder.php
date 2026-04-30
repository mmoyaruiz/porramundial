<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('usuarios')->insert([
            [
                'id_usuario' => 1,
                'nombre_usuario' => 'miguel_moya',
                'correo_electronico' => 'miguel@example.com',
                'password_hash' => Hash::make('Password123!'),
                'fecha_registro' => '2026-01-10 10:00:00',
                'es_activo' => 1,
            ],
            [
                'id_usuario' => 2,
                'nombre_usuario' => 'ana_garcia',
                'correo_electronico' => 'ana@example.com',
                'password_hash' => Hash::make('Password123!'),
                'fecha_registro' => '2026-01-12 11:30:00',
                'es_activo' => 1,
            ],
            [
                'id_usuario' => 3,
                'nombre_usuario' => 'juan_perez',
                'correo_electronico' => 'juan@example.com',
                'password_hash' => Hash::make('Password123!'),
                'fecha_registro' => '2026-01-15 09:15:00',
                'es_activo' => 1,
            ],
            [
                'id_usuario' => 4,
                'nombre_usuario' => 'lucia_rios',
                'correo_electronico' => 'lucia@example.com',
                'password_hash' => Hash::make('Password123!'),
                'fecha_registro' => '2026-01-18 18:45:00',
                'es_activo' => 1,
            ],
            [
                'id_usuario' => 5,
                'nombre_usuario' => 'carlos_sanz',
                'correo_electronico' => 'carlos@example.com',
                'password_hash' => Hash::make('Password123!'),
                'fecha_registro' => '2026-01-20 16:20:00',
                'es_activo' => 1,
            ],
            [
                'id_usuario' => 6,
                'nombre_usuario' => 'marta_lopez',
                'correo_electronico' => 'marta@example.com',
                'password_hash' => Hash::make('Password123!'),
                'fecha_registro' => '2026-01-22 08:05:00',
                'es_activo' => 1,
            ],
        ]);
    }
}


