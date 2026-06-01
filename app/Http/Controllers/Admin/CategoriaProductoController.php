<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaProducto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoriaProductoController extends Controller
{
    /**
     * LISTAR (Read)
     * Muestra la pantalla principal con la tabla de categorías.
     */
    public function index()
    {
        // Traemos las categorías paginadas (10 por página) para optimizar el rendimiento.
        // Ordenamos por ID descendente para ver las nuevas primero.
        $categorias = CategoriaProducto::orderBy('id', 'desc')->paginate(10);

        // Renderizamos la vista Vue que crearemos en el siguiente paso.
        return Inertia::render('Admin/Categorias/Index', [
            'categorias' => $categorias
        ]);
    }

    /**
     * GUARDAR (Create)
     * Recibe los datos del formulario y crea una nueva categoría.
     */
    public function store(Request $request)
    {
        // 1. Validación (Multi-tenant: unicidad por empresa - Elmasri)
        $request->validate([
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('categorias_producto', 'nombre')
                    ->where('empresa_id', auth()->user()->empresa_id),
            ],
            'descripcion' => 'nullable|string|max:500',
        ], [
            'nombre.unique' => 'Esta categoría ya existe.'
        ]);

        // 2. Creación
        CategoriaProducto::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => true // Por defecto nacen activas
        ]);

        // 3. Respuesta (Redirección automática de Inertia)
        return back()->with('success', 'Categoría creada correctamente.');
    }

    /**
     * ACTUALIZAR (Update)
     * Modifica una categoría existente.
     */
    public function update(Request $request, CategoriaProducto $categoria)
    {
        // 1. Validación (Multi-tenant: unicidad por empresa, ignorando este registro)
        $request->validate([
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('categorias_producto', 'nombre')
                    ->where('empresa_id', auth()->user()->empresa_id)
                    ->ignore($categoria->id),
            ],
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'boolean'
        ]);

        // 2. Actualización
        $categoria->update($request->only(['nombre', 'descripcion', 'activo']));

        return back()->with('success', 'Categoría actualizada.');
    }

    /**
     * ELIMINAR (Delete)
     * Validamos reglas de negocio antes de borrar.
     */
    public function destroy(CategoriaProducto $categoria)
    {
        // Regla de Negocio (Integridad): No borrar si tiene productos asociados.
        if ($categoria->productos()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar: hay productos asociados a esta categoría. Desactívala en su lugar.']);
        }

        $categoria->delete();

        return back()->with('success', 'Categoría eliminada.');
    }
}