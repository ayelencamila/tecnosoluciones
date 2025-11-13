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
        // FIX 1 (Simplificado):
        // En lugar de 'false', que bloquea todo,
        // pedimos que al menos el usuario esté autenticado.
        // Esto arregla el error 403, sin meternos con roles.
        return auth()->check();
    }

    /**
     * Obtiene las reglas de validación que aplican a la solicitud.
     */
    public function rules(): array
    {
        // FIX 2: Añadir las reglas de validación (esto es obligatorio).
        return [
            'clienteID' => [
                'required',
                'integer',
                // Asegura que el cliente exista en la BD
                Rule::exists(Cliente::class, 'clienteID'),
            ],
            'metodo_pago' => ['required', 'string', Rule::in(['efectivo', 'tarjeta', 'cuenta_corriente'])],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'numero_comprobante' => ['nullable', 'string', 'max:255', 'unique:ventas,numero_comprobante'],

            // --- Validación del Carrito (Items) ---
            'items' => ['required', 'array', 'min:1'],
            'items.*.productoID' => [
                'required',
                'integer',
                // Asegura que el producto exista en la BD
                Rule::exists(Producto::class, 'id'),
            ],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.01'], // O 'integer' y 'min:1' si no vendes decimales

            // (Opcional pero recomendado) Validación de descuentos
            'descuentos_globales' => ['nullable', 'array'],
            'descuentos_globales.*.descuento_id' => [
                'required_with:descuentos_globales',
                'integer',
                Rule::exists('descuentos', 'descuento_id'),
            ],
        ];
    }
}
