<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'clienteID'   => ['required', 'integer', 'exists:clientes,clienteID'],
            'monto'       => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'medioPagoID' => ['required', 'exists:medios_pago,medioPagoID'],
            'observaciones' => ['nullable', 'string', 'max:255'],
            // Imputaciones manuales (opcional - si no se envía, será automático)
            'imputaciones' => ['nullable', 'array'],
            'imputaciones.*.venta_id' => ['required', 'exists:ventas,venta_id'],
            'imputaciones.*.monto_imputado' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'clienteID.required' => 'Debe seleccionar un cliente.',
            'medioPagoID.required' => 'Debe seleccionar un método de pago válido.',
            'monto.min' => 'El monto del pago debe ser mayor a 0.',
            'imputaciones.*.venta_id.exists' => 'Una de las ventas seleccionadas no existe.',
            'imputaciones.*.monto_imputado.required' => 'Debe especificar el monto a imputar para cada venta.',
        ];
    }

    /**
     * Validaciones adicionales después de las reglas básicas
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Si hay imputaciones manuales, validar que no excedan el monto del pago
            if ($this->has('imputaciones') && is_array($this->imputaciones)) {
                $totalImputado = collect($this->imputaciones)->sum('monto_imputado');
                
                if ($totalImputado > $this->monto) {
                    $validator->errors()->add(
                        'imputaciones',
                        "La suma de imputaciones (\${$totalImputado}) no puede exceder el monto del pago (\${$this->monto})."
                    );
                }
            }
        });
    }
}