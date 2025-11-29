<?php

namespace App\Http\Requests\Descuentos;

use Illuminate\Foundation\Http\FormRequest;

class StoreDescuentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'codigo' => 'required|string|max:50|unique:descuentos,codigo',
            'descripcion' => 'required|string|max:255',
            
            // Validar IDs de tablas maestras
            'tipo_descuento_id' => 'required|exists:tipos_descuento,id',
            'aplicabilidad_descuento_id' => 'required|exists:aplicabilidades_descuento,id',
            
            'valor' => 'required|numeric|min:0',
            'valido_desde' => 'required|date',
            'valido_hasta' => 'nullable|date|after_or_equal:valido_desde',
            'activo' => 'boolean',
        ];
    }
}