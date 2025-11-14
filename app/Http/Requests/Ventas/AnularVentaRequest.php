<?php

namespace App\Http\Requests\Ventas;

use Illuminate\Foundation\Http\FormRequest;

class AnularVentaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta petición.
     */
    public function authorize(): bool
    {
        // Por ahora, solo si está logueado puede intentar anular.
        // Aquí iría una Policy más adelante.
        return auth()->check();
    }

    /**
     * Obtiene las reglas de validación que aplican a la petición.
     */
    public function rules(): array
    {
        return [
            'motivo_anulacion' => 'required|string|min:10|max:255',
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'motivo_anulacion.required' => 'Debes proporcionar un motivo para anular la venta.',
            'motivo_anulacion.min' => 'El motivo de anulación debe tener al menos 10 caracteres.',
            'motivo_anulacion.max' => 'El motivo de anulación no debe exceder los 255 caracteres.',
        ];
    }
}
