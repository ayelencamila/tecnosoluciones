<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use App\Models\Direccion;
use App\Models\Localidad;
use App\Models\Auditoria;
use App\Http\Requests\Proveedores\StoreProveedorRequest;
use App\Http\Requests\Proveedores\UpdateProveedorRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProveedorController extends Controller
{
    // CU-18: Consultar Proveedores
    public function index(Request $request)
    {
        $query = Proveedor::with(['direccion.localidad.provincia'])
            ->when($request->search, function ($q, $search) {
                $q->where('razon_social', 'like', "%{$search}%")
                  ->orWhere('cuit', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->provincia_id, function ($q, $provinciaId) {
                $q->whereHas('direccion.localidad', function ($query) use ($provinciaId) {
                    $query->where('provinciaID', $provinciaId);
                });
            })
            ->when($request->estado !== null && $request->estado !== '', function ($q) use ($request) {
                $q->where('activo', $request->estado === 'activo');
            })
            ->when($request->calificacion_min, function ($q, $calMin) {
                $q->where('calificacion', '>=', $calMin);
            })
            ->when($request->sort_column, function ($q) use ($request) {
                $q->orderBy($request->sort_column, $request->sort_direction ?? 'asc');
            }, function ($q) {
                $q->orderBy('razon_social', 'asc');
            });

        $stats = [
            'total' => Proveedor::count(),
            'activos' => Proveedor::where('activo', true)->count(),
            'mejorCalificacion' => Proveedor::max('calificacion'),
        ];

        return Inertia::render('Proveedores/Index', [
            'proveedores' => $query->paginate(10)->withQueryString(),
            'provincias' => \App\Models\Provincia::orderBy('nombre')->get(),
            'filters' => $request->only(['search', 'provincia_id', 'estado', 'calificacion_min', 'sort_column', 'sort_direction']),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return Inertia::render('Proveedores/Create', [
            'provincias' => \App\Models\Provincia::orderBy('nombre')->get(),
            'localidades' => Localidad::with('provincia')->orderBy('nombre')->get()
        ]);
    }

    public function show(Proveedor $proveedor)
    {
        $proveedor->load('direccion.localidad.provincia');
        return Inertia::render('Proveedores/Show', [
            'proveedor' => $proveedor,
        ]);
    }

    // CU-16: Registrar Proveedor
    public function store(StoreProveedorRequest $request)
    {
        DB::transaction(function () use ($request) {
            // 1. Crear Dirección
            $direccion = Direccion::create([
                'calle' => $request->calle,
                'altura' => $request->altura,
                'localidadID' => $request->localidad_id,
            ]);

            // 2. Crear Proveedor
            $proveedor = Proveedor::create([
                'razon_social' => $request->razon_social,
                'cuit' => $request->cuit,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'forma_pago_preferida' => $request->forma_pago_preferida,
                'plazo_entrega_estimado' => $request->plazo_entrega_estimado,
                'direccion_id' => $direccion->direccionID,
                'activo' => true
            ]);

            // 3. Auditoría Automática (Creación)
            Auditoria::registrar(
                accion: Auditoria::ACCION_CREAR_PROVEEDOR,
                tabla: 'proveedores',
                registroId: $proveedor->id,
                detalles: "Alta de proveedor: {$proveedor->razon_social}"
            );
        });

        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado exitosamente.');
    }

    public function edit(Proveedor $proveedor)
    {
        $proveedor->load('direccion.localidad.provincia');
        return Inertia::render('Proveedores/Edit', [
            'proveedor' => $proveedor,
            'provincias' => \App\Models\Provincia::orderBy('nombre')->get(),
            'localidades' => Localidad::with('provincia')->orderBy('nombre')->get()
        ]);
    }

    // CU-17: Modificar Proveedor
    public function update(UpdateProveedorRequest $request, Proveedor $proveedor)
    {
        DB::transaction(function () use ($request, $proveedor) {
            $datosAnteriores = $proveedor->load('direccion')->toArray();

            // 1. Actualizar Dirección
            $proveedor->direccion->update([
                'calle' => $request->calle,
                'altura' => $request->altura,
                'localidadID' => $request->localidad_id,
            ]);

            // 2. Actualizar Proveedor
            $proveedor->update($request->except(['motivo', 'calle', 'altura', 'localidad_id', 'provincia_id']));

            // 3. Auditoría con MOTIVO (Lineamiento Kendall)
            Auditoria::registrar(
                accion: Auditoria::ACCION_MODIFICAR_PROVEEDOR,
                tabla: 'proveedores',
                registroId: $proveedor->id,
                datosAnteriores: $datosAnteriores,
                datosNuevos: $proveedor->fresh()->toArray(),
                motivo: $request->motivo, // <-- El motivo que viene del form
                detalles: "Modificación de datos maestros."
            );
        });

        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    // CU-21: Actualizar Calificación de Proveedor
    public function actualizarCalificacion(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'calificacion' => 'required|numeric|min:0|max:5'
        ]);

        $proveedor->update([
            'calificacion' => $request->calificacion
        ]);

        return redirect()->back()->with('success', 'Calificación actualizada correctamente.');
    }

    /**
     * Buscar proveedores para autocompletado (API interna)
     */
    public function buscar(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $proveedores = Proveedor::where('activo', true)
            ->where(function ($q) use ($query) {
                $q->where('razon_social', 'like', "%{$query}%")
                  ->orWhere('cuit', 'like', "%{$query}%");
            })
            ->limit(15)
            ->get(['id', 'razon_social', 'cuit']);

        return response()->json($proveedores);
    }

    // CU-19: Dar de baja Proveedor
    public function destroy(Request $request, Proveedor $proveedor)
    {
        $request->validate(['motivo' => 'required|string|min:5']);

        try {
            // Lógica encapsulada en el modelo (tu código anterior)
            $proveedor->darDeBaja($request->motivo);
            return redirect()->back()->with('success', 'Proveedor dado de baja correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}