<?php

namespace App\Http\Requests\Compras;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU-23: Validación para recepcionar mercadería
 */
class StoreRecepcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orden_compra_id' => 'required|exists:ordenes_compra,id',
            'observaciones' => 'required|string|min:10|max:1000',
            'items' => 'required|array|min:1',
            'items.*.detalle_orden_id' => 'required|exists:detalle_ordenes_compra,id',
            'items.*.cantidad_recibida' => 'required|integer|min:0',
            'items.*.observacion_item' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'orden_compra_id.required' => 'Debe seleccionar una Orden de Compra.',
            'orden_compra_id.exists' => 'La Orden de Compra seleccionada no existe.',
            'observaciones.required' => 'Se requiere un motivo o una observación para la recepción de mercadería.',
            'observaciones.min' => 'La observación debe tener al menos :min caracteres.',
            'items.required' => 'Debe ingresar al menos un ítem a recepcionar.',
            'items.min' => 'Debe ingresar al menos un ítem a recepcionar.',
            'items.*.detalle_orden_id.required' => 'El ítem debe estar asociado a un detalle de la OC.',
            'items.*.detalle_orden_id.exists' => 'El ítem no pertenece a ninguna Orden de Compra.',
            'items.*.cantidad_recibida.required' => 'Debe ingresar la cantidad recibida para cada ítem.',
            'items.*.cantidad_recibida.min' => 'La cantidad recibida no puede ser negativa.',
        ];
    }
}
