<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajustar según permisos cuando se implemente autenticación
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
            'categoriaProductoID.exists' => 'La categoría seleccionada no es válida',
            'estadoProductoID.required' => 'Debe seleccionar un estado',
            'estadoProductoID.exists' => 'El estado seleccionado no es válido',
            'precios.required' => 'Debe ingresar al menos un precio',
            'precios.min' => 'Debe ingresar al menos un precio (minorista o mayorista)',
            'precios.*.tipoClienteID.required' => 'Debe seleccionar el tipo de cliente para el precio',
            'precios.*.tipoClienteID.exists' => 'El tipo de cliente seleccionado no es válido',
            'precios.*.precio.required' => 'El precio es obligatorio',
            'precios.*.precio.numeric' => 'El precio debe ser un número',
            'precios.*.precio.min' => 'El precio debe ser mayor o igual a 0',
            'stockActual.integer' => 'El stock actual debe ser un número entero',
            'stockActual.min' => 'El stock actual no puede ser negativo',
            'stockMinimo.integer' => 'El stock mínimo debe ser un número entero',
            'stockMinimo.min' => 'El stock mínimo no puede ser negativo',
        ];
    }
}
