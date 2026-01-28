<?php

namespace App\Http\Requests\Productos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        // Obtenemos el ID del producto
        $productoId = $this->route('producto')->id ?? $this->route('producto');

        return [
            // --- Datos del Catálogo ---
            'codigo' => ['required', 'string', 'max:50', Rule::unique('productos')->ignore($productoId)],
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // Max 2MB
            'eliminar_foto' => ['nullable', 'boolean'],
            'es_servicio' => ['nullable', 'boolean'],

            // CORRECCIÓN: Validamos IDs, no texto
            'marca_id' => 'nullable|exists:marcas,id',
            'unidad_medida_id' => 'required|exists:unidades_medida,id',
            
            'categoriaProductoID' => 'required|exists:categorias_producto,id',
            'estadoProductoID' => 'required|exists:estados_producto,id',
            'proveedor_habitual_id' => 'nullable|exists:proveedores,id',

            // --- Motivo de Auditoría ---
            'motivo' => 'required|string|min:5|max:255', 
            
            // --- Precios ---
            'precios' => 'required|array|min:1',
            'precios.*.tipoClienteID' => 'required|exists:tipos_cliente,tipoClienteID', 
            'precios.*.precio' => 'required|numeric|min:0|max:99999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'El código ya está en uso.',
            'unidad_medida_id.required' => 'Debe seleccionar una unidad de medida.',
            'marca_id.exists' => 'La marca seleccionada no es válida.',
            'motivo.required' => 'Debe ingresar un motivo para la modificación.',
        ];
    }
}