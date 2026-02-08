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
        // Si estamos actualizando, ignorar el registro actual en la regla unique
        $descuento = $this->route('descuento');
        $ignoreId = $descuento ? $descuento->descuento_id : null;

        $codigoRule = $ignoreId
            ? ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('descuentos', 'codigo')->ignore($ignoreId, 'descuento_id')]
            : 'required|string|max:50|unique:descuentos,codigo';

        return [
            'codigo' => $codigoRule,
            'descripcion' => 'required|string|max:255',
            
            // Validar IDs de tablas maestras
            'tipo_descuento_id' => 'required|exists:tipos_descuento,id',
            'aplicabilidad_descuento_id' => 'required|exists:aplicabilidades_descuento,id',
            
            'valor' => 'required|numeric|min:0',
            'valido_desde' => 'nullable|date',
            'valido_hasta' => 'nullable|date|after_or_equal:valido_desde',
            'activo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del descuento es obligatorio.',
            'codigo.unique' => 'Ya existe un descuento con este código.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'tipo_descuento_id.required' => 'Debe seleccionar un tipo de cálculo.',
            'tipo_descuento_id.exists' => 'El tipo de cálculo seleccionado no es válido.',
            'aplicabilidad_descuento_id.required' => 'Debe seleccionar la aplicabilidad.',
            'aplicabilidad_descuento_id.exists' => 'La aplicabilidad seleccionada no es válida.',
            'valor.required' => 'El valor del descuento es obligatorio.',
            'valor.numeric' => 'El valor debe ser un número.',
            'valor.min' => 'El valor no puede ser negativo.',
            'valido_hasta.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}