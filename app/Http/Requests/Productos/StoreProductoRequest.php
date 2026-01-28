<?php

namespace App\Http\Requests\Productos;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // Datos Maestros
            'codigo' => ['required', 'string', 'max:50', 'unique:productos,codigo'],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // Max 2MB
            'es_servicio' => ['nullable', 'boolean'],
            
            // IDs de Tablas Maestras
            'marca_id' => ['nullable', 'exists:marcas,id'],
            'unidad_medida_id' => ['required', 'exists:unidades_medida,id'],
            'categoriaProductoID' => ['required', 'exists:categorias_producto,id'],
            'estadoProductoID' => ['required', 'exists:estados_producto,id'],
            'proveedor_habitual_id' => ['nullable', 'exists:proveedores,id'],

            // Stock (Opcionales)
            'stock_minimo' => ['nullable', 'integer', 'min:0'],
            'cantidad_inicial' => ['nullable', 'integer', 'min:0'],

            // --- VALIDACIÓN DE PRECIOS ---
            // Sin esto, Laravel elimina los precios antes de llegar al controlador
            'precios' => ['required', 'array', 'min:1'],
            'precios.*.tipoClienteID' => ['required', 'exists:tipos_cliente,tipoClienteID'],
            'precios.*.precio' => ['required', 'numeric', 'min:0'],
            // -----------------------------------------
        ];
    }

    public function messages(): array
    {
        return [
            'unidad_medida_id.required' => 'Debe seleccionar una unidad de medida.',
            'categoriaProductoID.required' => 'Debe seleccionar una categoría.',
            'precios.required' => 'Debe ingresar al menos un precio para el producto.',
        ];
    }
}