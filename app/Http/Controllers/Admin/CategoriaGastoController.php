<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\CategoriaGasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CategoriaGastoController extends Controller
{
    public function index(Request $request)
    {
        $categorias = CategoriaGasto::query()
            ->when($request->search, function ($query, $search) {
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            })
            ->when($request->tipo, function ($query, $tipo) {
                $query->where('tipo', $tipo);
            })
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/CategoriasGasto/Index', [
            'categorias' => $categorias,
            'filters' => $request->only(['search', 'tipo']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/CategoriasGasto/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_gasto,nombre',
            'descripcion' => 'nullable|string|max:255',
            'tipo' => 'required|in:gasto,perdida',
            'activo' => 'boolean',
        ]);

        $categoria = CategoriaGasto::create($validated);

        Auditoria::create([
            'accion' => 'CREACION',
            'tabla_afectada' => 'categorias_gasto',
            'registro_id' => $categoria->getKey(),
            'datos_nuevos' => $categoria->toArray(),
            'usuarioID' => Auth::id(),
            'motivo' => 'Creación de categoría de gasto',
        ]);

        return redirect()->route('admin.categorias-gasto.index')
            ->with('success', 'Categoría de gasto creada correctamente.');
    }

    public function edit(CategoriaGasto $categorias_gasto)
    {
        return Inertia::render('Admin/CategoriasGasto/Edit', [
            'categoria' => $categorias_gasto,
        ]);
    }

    public function update(Request $request, CategoriaGasto $categorias_gasto)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_gasto,nombre,'.$categorias_gasto->categoria_gasto_id.',categoria_gasto_id',
            'descripcion' => 'nullable|string|max:255',
            'tipo' => 'required|in:gasto,perdida',
            'activo' => 'boolean',
        ]);

        $valorAnterior = $categorias_gasto->toArray();
        $categorias_gasto->update($validated);

        Auditoria::create([
            'accion' => 'MODIFICACION',
            'tabla_afectada' => 'categorias_gasto',
            'registro_id' => $categorias_gasto->getKey(),
            'datos_anteriores' => $valorAnterior,
            'datos_nuevos' => $categorias_gasto->fresh()->toArray(),
            'usuarioID' => Auth::id(),
            'motivo' => 'Modificación de categoría de gasto',
        ]);

        return redirect()->route('admin.categorias-gasto.index')
            ->with('success', 'Categoría de gasto actualizada correctamente.');
    }

    public function destroy(Request $request, CategoriaGasto $categorias_gasto)
    {
        // Verificar si tiene gastos asociados
        if ($categorias_gasto->gastos()->exists()) {
            return back()->with('error', 'No se puede eliminar la categoría porque tiene gastos asociados.');
        }

        Auditoria::create([
            'accion' => 'ELIMINACION',
            'tabla_afectada' => 'categorias_gasto',
            'registro_id' => $categorias_gasto->getKey(),
            'datos_anteriores' => $categorias_gasto->toArray(),
            'usuarioID' => Auth::id(),
            'motivo' => 'Eliminación de categoría de gasto',
        ]);

        $categorias_gasto->delete();

        return redirect()->route('admin.categorias-gasto.index')
            ->with('success', 'Categoría de gasto eliminada correctamente.');
    }

    public function toggleActivo(Request $request, CategoriaGasto $categorias_gasto)
    {
        $categorias_gasto->update(['activo' => ! $categorias_gasto->activo]);

        Auditoria::create([
            'accion' => 'MODIFICACION',
            'tabla_afectada' => 'categorias_gasto',
            'registro_id' => $categorias_gasto->getKey(),
            'detalles' => $categorias_gasto->activo ? 'Categoría activada' : 'Categoría desactivada',
            'usuarioID' => Auth::id(),
            'motivo' => 'Cambio de estado de categoría de gasto',
        ]);

        return back()->with('success', 'Estado de la categoría actualizado.');
    }
}
