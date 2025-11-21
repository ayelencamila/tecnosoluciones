<?php

namespace App\Http\Requests\Reparaciones;

use Illuminate\Foundation\Http\FormRequest;

class StoreReparacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // O tu lógica de roles
    }

    public function rules(): array
    {
        return [
            // Cliente
            'clienteID' => ['required', 'exists:clientes,clienteID'],
            
            // Datos del Equipo (IRQ-06)
            'equipo_marca' => ['required', 'string', 'max:100'],
            'equipo_modelo' => ['required', 'string', 'max:100'],
            'numero_serie_imei' => ['nullable', 'string', 'max:100'],
            'clave_bloqueo' => ['nullable', 'string', 'max:50'],
            'accesorios_dejados' => ['nullable', 'string', 'max:500'],
            'falla_declarada' => ['required', 'string', 'max:1000'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            
            // Fechas
            'fecha_promesa' => ['nullable', 'date', 'after_or_equal:today'],

            // Imágenes (NUEVO REQUERIMIENTO)
            // Validamos que sea un array y que cada ítem sea una imagen válida
            'imagenes' => ['nullable', 'array', 'max:5'], // Máximo 5 fotos por ejemplo
            'imagenes.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // Max 5MB por foto

            // Repuestos/Servicios iniciales (Opcional al ingreso, pero lo dejamos listo)
            'items' => ['nullable', 'array'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ];
    }
}