<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\CategoriaGasto;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GastoController extends Controller
{
    public function index(Request $request)
    {
        $gastos = Gasto::with(['categoria', 'usuario'])
            ->when($request->search, function ($query, $search) {
                $query->where('descripcion', 'like', "%{$search}%")
                    ->orWhere('comprobante', 'like', "%{$search}%");
            })
            ->when($request->categoria_id, function ($query, $categoriaId) {
                $query->where('categoria_gasto_id', $categoriaId);
            })
            ->when($request->tipo, function ($query, $tipo) {
                $query->whereHas('categoria', function ($q) use ($tipo) {
                    $q->where('tipo', $tipo);
                });
            })
            ->when($request->mes && $request->anio, function ($query) use ($request) {
                $query->whereMonth('fecha', $request->mes)
                    ->whereYear('fecha', $request->anio);
            })
            ->when($request->estado !== null, function ($query) use ($request) {
                $query->where('anulado', $request->estado === 'anulado');
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $categorias = CategoriaGasto::activas()->orderBy('nombre')->get();

        return Inertia::render('Gastos/Index', [
            'gastos' => $gastos,
            'categorias' => $categorias,
            'filters' => $request->only(['search', 'categoria_id', 'tipo', 'mes', 'anio', 'estado']),
        ]);
    }

    public function create()
    {
        $categorias = CategoriaGasto::activas()->orderBy('tipo')->orderBy('nombre')->get();

        return Inertia::render('Gastos/Create', [
            'categorias' => $categorias,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria_gasto_id' => 'required|exists:categorias_gasto,categoria_gasto_id',
            'fecha' => 'required|date',
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'comprobante' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
        ]);

        $validated['usuario_id'] = Auth::id();

        $gasto = Gasto::create($validated);

        Auditoria::create([
            'accion' => 'CREACION',
            'tablaAfectada' => 'gastos',
            'valorNuevo' => json_encode($gasto->load('categoria')->toArray()),
            'usuarioID' => Auth::id(),
            'ip' => $request->ip(),
            'motivo' => 'Registro de gasto/pérdida'
        ]);

        return redirect()->route('gastos.index')
            ->with('success', 'Gasto registrado correctamente.');
    }

    public function edit(Gasto $gasto)
    {
        if ($gasto->anulado) {
            return back()->with('error', 'No se puede editar un gasto anulado.');
        }

        $categorias = CategoriaGasto::activas()->orderBy('tipo')->orderBy('nombre')->get();

        return Inertia::render('Gastos/Edit', [
            'gasto' => $gasto->load('categoria'),
            'categorias' => $categorias,
        ]);
    }

    public function update(Request $request, Gasto $gasto)
    {
        if ($gasto->anulado) {
            return back()->with('error', 'No se puede modificar un gasto anulado.');
        }

        $validated = $request->validate([
            'categoria_gasto_id' => 'required|exists:categorias_gasto,categoria_gasto_id',
            'fecha' => 'required|date',
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'comprobante' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
        ]);

        $valorAnterior = $gasto->toArray();
        $gasto->update($validated);

        Auditoria::create([
            'accion' => 'MODIFICACION',
            'tablaAfectada' => 'gastos',
            'valorAnterior' => json_encode($valorAnterior),
            'valorNuevo' => json_encode($gasto->fresh()->load('categoria')->toArray()),
            'usuarioID' => Auth::id(),
            'ip' => $request->ip(),
            'motivo' => 'Modificación de gasto/pérdida'
        ]);

        return redirect()->route('gastos.index')
            ->with('success', 'Gasto actualizado correctamente.');
    }

    public function anular(Request $request, Gasto $gasto)
    {
        if ($gasto->anulado) {
            return back()->with('error', 'El gasto ya está anulado.');
        }

        $gasto->update(['anulado' => true]);

        Auditoria::create([
            'accion' => 'ANULACION',
            'tablaAfectada' => 'gastos',
            'valorAnterior' => json_encode(['anulado' => false]),
            'valorNuevo' => json_encode(['anulado' => true]),
            'usuarioID' => Auth::id(),
            'ip' => $request->ip(),
            'motivo' => $request->motivo ?? 'Anulación de gasto'
        ]);

        return back()->with('success', 'Gasto anulado correctamente.');
    }

    public function destroy(Request $request, Gasto $gasto)
    {
        Auditoria::create([
            'accion' => 'ELIMINACION',
            'tablaAfectada' => 'gastos',
            'valorAnterior' => json_encode($gasto->toArray()),
            'usuarioID' => Auth::id(),
            'ip' => $request->ip(),
            'motivo' => 'Eliminación de gasto'
        ]);

        $gasto->delete();

        return redirect()->route('gastos.index')
            ->with('success', 'Gasto eliminado correctamente.');
    }
}
