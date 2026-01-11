<?php

namespace App\Http\Requests\Reparaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReparacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            // Cliente (Validamos que exista)
            'clienteID' => ['required', 'exists:clientes,clienteID'],
            
            // Técnico (CU-11 Paso 5: Asignación obligatoria)
            'tecnico_id' => ['required', 'exists:users,id'],

            // Validación de Marca y Modelo con relación cruzada
            'marca_id' => ['required', 'exists:marcas,id'],
            'modelo_id' => [
                'required', 
                'exists:modelos,id',
                // Validación cruzada: el modelo debe pertenecer a la marca seleccionada
                Rule::exists('modelos', 'id')->where(function ($query) {
                    $query->where('marca_id', $this->marca_id);
                })
            ],
            'numero_serie_imei' => ['nullable', 'string', 'max:100'],
            'clave_bloqueo' => ['nullable', 'string', 'max:50'],
            'accesorios_dejados' => ['nullable', 'string', 'max:500'],
            'falla_declarada' => ['required', 'string', 'max:1000'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            
            // Fechas
            'fecha_promesa' => ['nullable', 'date', 'after_or_equal:today'],

            // Imágenes
            'imagenes' => ['nullable', 'array', 'max:5'], 
            'imagenes.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], 

            // Repuestos/Servicios iniciales
            'items' => ['nullable', 'array'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ];
    }
    
    // Opcional: Mensajes personalizados para guiar mejor al usuario
    public function messages(): array
    {
        return [
            'marca_id.required' => 'Debes seleccionar una marca de la lista.',
            'modelo_id.required' => 'Debes seleccionar un modelo válido.',
            'modelo_id.exists' => 'El modelo seleccionado no pertenece a la marca elegida. Por favor, verifica la combinación.',
            'clienteID.required' => 'Es obligatorio asignar un cliente.',
            'tecnico_id.required' => 'Debes asignar un técnico responsable de la reparación.',
        ];
    }
}