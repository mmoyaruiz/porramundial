<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class InvitacionesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('invitaciones')->insert([
            [
                'id_invitacion' => 1,
                'id_porra' => 1,
                'id_usuario_invitador' => 2,
                'usuario_destino' => 'usuarioX',
                'email_destino' => 'usuarioX@gmail.com',
                'estado' => 'pendiente',
                'fecha_envio' => '2026-02-10 10:00:00',
                'fecha_respuesta' => null,
            ],
            [
                'id_invitacion' => 2,
                'id_porra' => 4,
                'id_usuario_invitador' => 5,
                'usuario_destino' => 'usuario4',
                'email_destino' => 'usuario4@gmail.com',
                'estado' => 'pendiente',
                'fecha_envio' => '2026-02-11 09:00:00',
                'fecha_respuesta' => null,
            ],
            [
                'id_invitacion' => 3,
                'id_porra' => 4,
                'id_usuario_invitador' => 5,
                'usuario_destino' => 'usuario3',
                'email_destino' => 'usuario3@gmail.com',
                'estado' => 'pendiente',
                'fecha_envio' => '2026-02-11 09:00:00',
                'fecha_respuesta' => null,
            ],
            
        ]);
    }
}

