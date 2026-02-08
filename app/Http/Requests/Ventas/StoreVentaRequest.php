<?php

namespace App\Http\Requests\Ventas;

use App\Models\Cliente;
use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVentaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        // Esto asegura que solo un usuario autenticado pueda intentar una venta.
        // La restricción por roles se aplica a nivel de Policy o Middleware.
        return auth()->check();
    }

    /**
     * Obtiene las reglas de validación que aplican a la solicitud.
     */
    public function rules(): array
    {
        return [
            'clienteID' => ['required', 'exists:clientes,clienteID'],
            
            // CAMBIO: Validar Medio de Pago Configurable
            'medio_pago_id' => ['required', 'exists:medios_pago,medioPagoID'],
            
            // Productos
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_producto_id' => ['nullable', 'integer', 'exists:precios_producto,id'],

            // Descuentos por ítem
            'items.*.descuentos_item' => ['nullable', 'array'],
            'items.*.descuentos_item.*.descuento_id' => ['required', 'exists:descuentos,descuento_id'],
            'items.*.descuentos_item.*.monto_aplicado_item' => ['nullable', 'numeric', 'min:0'],

            // Descuentos globales (aplicados a la venta completa)
            'descuentos_globales' => ['nullable', 'array'],
            'descuentos_globales.*.descuento_id' => ['required', 'exists:descuentos,descuento_id'],

            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
}
