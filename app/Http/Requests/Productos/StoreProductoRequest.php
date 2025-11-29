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
            // Validamos que el código sea único en la tabla productos
            'codigo' => ['required', 'string', 'max:50', 'unique:productos,codigo'],
            
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],

            // Marca es opcional (nullable), pero si viene, debe existir en la tabla 'marcas'
            'marca_id' => ['nullable', 'exists:marcas,id'],
            
            // Unidad de Medida es obligatoria y debe existir en 'unidades_medida'
            'unidad_medida_id' => ['required', 'exists:unidades_medida,id'],
            

            'categoriaProductoID' => ['required', 'exists:categorias_producto,id'],
            'estadoProductoID' => ['required', 'exists:estados_producto,id'],
            'proveedor_habitual_id' => ['nullable', 'exists:proveedores,id'],
            // ----------------------------------

            // Precios (si los manejas en el mismo form)
            // 'precio_base' => ['required', 'numeric', 'min:0'], 
        ];
    }

    public function messages(): array
    {
        return [
            'unidad_medida_id.required' => 'Debes seleccionar una unidad de medida.',
            'categoriaProductoID.required' => 'Debes seleccionar una categoría.',
            'marca_id.exists' => 'La marca seleccionada no es válida.',
        ];
    }
}