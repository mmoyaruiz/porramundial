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
                'id_usuario_invitador' => 1,
                'usuario_destino' => 'amigo1',
                'email_destino' => 'amigo1@example.com',
                'estado' => 'pendiente',
                'fecha_envio' => '2026-02-10 10:00:00',
                'fecha_respuesta' => null,
            ],
            [
                'id_invitacion' => 2,
                'id_porra' => 1,
                'id_usuario_invitador' => 1,
                'usuario_destino' => 'ana_garcia',
                'email_destino' => null,
                'estado' => 'aceptada',
                'fecha_envio' => '2026-02-10 10:05:00',
                'fecha_respuesta' => '2026-02-10 11:00:00',
            ],
            [
                'id_invitacion' => 3,
                'id_porra' => 2,
                'id_usuario_invitador' => 2,
                'usuario_destino' => null,
                'email_destino' => 'nuevoempleado@example.com',
                'estado' => 'pendiente',
                'fecha_envio' => '2026-02-11 09:00:00',
                'fecha_respuesta' => null,
            ],
            [
                'id_invitacion' => 4,
                'id_porra' => 3,
                'id_usuario_invitador' => 3,
                'usuario_destino' => 'marta_lopez',
                'email_destino' => 'marta@example.com',
                'estado' => 'aceptada',
                'fecha_envio' => '2026-02-12 20:00:00',
                'fecha_respuesta' => '2026-02-12 22:00:00',
            ],
            [
                'id_invitacion' => 5,
                'id_porra' => 3,
                'id_usuario_invitador' => 3,
                'usuario_destino' => null,
                'email_destino' => 'rechazo@example.com',
                'estado' => 'rechazada',
                'fecha_envio' => '2026-02-13 19:00:00',
                'fecha_respuesta' => '2026-02-13 19:30:00',
            ],
        ]);
    }
}

