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
                'nombre_usuario' => 'superadmin',
                'correo_electronico' => 'superadmin@superadmin.com',
                'password_hash' => Hash::make('123456'),
                'fecha_registro' => '2026-01-10 10:00:00',
                'es_activo' => 1,
            ],
            [
                'id_usuario' => 2,
                'nombre_usuario' => 'usuario2',
                'correo_electronico' => 'usuario2@gmail.com',
                'password_hash' => Hash::make('111111'),
                'fecha_registro' => '2026-01-12 11:30:00',
                'es_activo' => 1,
            ],
            [
                'id_usuario' => 3,
                'nombre_usuario' => 'usuario3',
                'correo_electronico' => 'usuario3@gmail.com',
                'password_hash' => Hash::make('111111'),
                'fecha_registro' => '2026-01-15 09:15:00',
                'es_activo' => 1,
            ],
            [
                'id_usuario' => 4,
                'nombre_usuario' => 'usuario4',
                'correo_electronico' => 'usuario4@gmail.com',
                'password_hash' => Hash::make('111111'),
                'fecha_registro' => '2026-01-18 18:45:00',
                'es_activo' => 1,
            ],
            [
                'id_usuario' => 5,
                'nombre_usuario' => 'usuario5',
                'correo_electronico' => 'usuario5@gmail.com',
                'password_hash' => Hash::make('111111'),
                'fecha_registro' => '2026-01-20 16:20:00',
                'es_activo' => 1,
            ],
            [
                'id_usuario' => 6,
                'nombre_usuario' => 'usuario6',
                'correo_electronico' => 'usuario6@gmail.com',
                'password_hash' => Hash::make('111111'),
                'fecha_registro' => '2026-01-22 08:05:00',
                'es_activo' => 1,
            ],
        ]);
    }
}


