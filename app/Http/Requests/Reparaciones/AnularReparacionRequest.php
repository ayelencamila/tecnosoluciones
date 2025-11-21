<?php

namespace App\Http\Requests\Reparaciones;

use Illuminate\Foundation\Http\FormRequest;

class AnularReparacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'motivo' => 'required|string|min:5|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Es obligatorio indicar el motivo de la anulación.',
            'motivo.min' => 'El motivo debe ser descriptivo (mínimo 5 caracteres).',
        ];
    }
}