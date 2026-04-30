<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la creación de porras.
 */
class PorraStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ya protegemos por middleware auth
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required','string','max:100'],
            'descripcion' => ['nullable','string'],
            'id_competicion' => ['required','integer','exists:competiciones,id_competicion'],
            'es_publica' => ['required','boolean'],
            'max_participantes' => ['nullable','integer','min:2'],

            // Reglas de puntuación (según tu tabla porras)
            'puntos_ganador' => ['required','integer','min:0'],
            'puntos_marcador' => ['required','integer','min:0'],
            'puntos_campeon_grupo' => ['required','integer','min:0'],
            'puntos_ganador_torneo' => ['required','integer','min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_competicion.exists' => 'La competición seleccionada no existe.',
        ];
    }
}
