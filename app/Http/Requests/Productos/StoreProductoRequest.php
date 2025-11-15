<?php

namespace App\Http\Requests\Productos; 

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; 

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajustar según permisos
    }

    public function rules(): array
    {
        return [
            'codigo' => 'required|string|max:50|unique:productos,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'marca' => 'nullable|string|max:100',
            'unidadMedida' => 'required|string|max:20',
            'categoriaProductoID' => 'required|exists:categorias_producto,id',
            'estadoProductoID' => 'required|exists:estados_producto,id',
            
            // Mantenemos la lógica de stock simple (CU-25)
            'stockActual' => 'nullable|integer|min:0', 
            'stockMinimo' => 'nullable|integer|min:0',

            'precios' => 'required|array|min:1',
            'precios.*.tipoClienteID' => 'required|exists:tipos_cliente,tipoClienteID',
            'precios.*.precio' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código/SKU es obligatorio',
            'codigo.unique' => 'El código/SKU ya existe en el sistema',
            'nombre.required' => 'El nombre del producto es obligatorio',
            'unidadMedida.required' => 'La unidad de medida es obligatoria',
            'categoriaProductoID.required' => 'Debe seleccionar una categoría',
            'estadoProductoID.required' => 'Debe seleccionar un estado',
            'precios.required' => 'Debe ingresar al menos un precio',
            'precios.min' => 'Debe ingresar al menos un precio (minorista o mayorista)',
            'precios.*.precio.required' => 'El precio es obligatorio',
            'stockActual.integer' => 'El stock actual debe ser un número entero',
            'stockActual.min' => 'El stock actual no puede ser negativo',
        ];
    }
}
