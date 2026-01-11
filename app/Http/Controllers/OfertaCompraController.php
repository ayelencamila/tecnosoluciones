<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compras\StoreOfertaRequest;
use App\Services\Compras\RegistrarOfertaService;
use App\Models\OfertaCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\SolicitudCotizacion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class OfertaCompraController extends Controller
{
    /**
     * CU-21 (Paso 1): Listar ofertas para gestión
     */
    public function index(Request $request): Response
    {
        // Usamos query scope (asumiendo que implementarás scopeSearch en el modelo OfertaCompra)
        // Si no, usa where simple por ahora.
        $query = OfertaCompra::with(['proveedor', 'estado', 'user'])
            ->latest('fecha_recepcion');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('codigo_oferta', 'like', "%{$term}%")
                  ->orWhereHas('proveedor', fn($p) => $p->where('razon_social', 'like', "%{$term}%"));
            });
        }

        $ofertas = $query->paginate(10)->withQueryString();

        return Inertia::render('Compras/Ofertas/Index', [
            'ofertas' => $ofertas,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * CU-21 (Paso 2): Formulario de registro de oferta
     */
    public function create(Request $request): Response
    {
        // Si viene de una solicitud (CU-20), precargamos datos
        $solicitudId = $request->query('solicitud_id');
        
        return Inertia::render('Compras/Ofertas/Create', [
            'proveedores' => Proveedor::where('activo', true)->orderBy('razon_social')->get(['id', 'razon_social']),
            // Enviamos productos simplificados para el selector del array dinámico
            'productos' => Producto::where('activo', true) // Asumiendo campo activo o estado
                ->select('id', 'nombre', 'codigo', 'ultimo_precio_compra')
                ->orderBy('nombre')
                ->get(),
            'solicitud_id' => $solicitudId,
        ]);
    }

    /**
     * CU-21 (Paso 14): Procesar y guardar la oferta
     */
    public function store(StoreOfertaRequest $request, RegistrarOfertaService $service): RedirectResponse
    {
        try {
            $oferta = $service->ejecutar($request->validated(), $request->user()->id);

            return redirect()->route('ofertas.show', $oferta->id)
                ->with('success', "Oferta {$oferta->codigo_oferta} registrada correctamente.");

        } catch (\Exception $e) {
            Log::error("Error al registrar oferta: " . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'Ocurrió un error inesperado al registrar la oferta. ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * CU-21 (Paso 11): Ver detalle y comparar
     */
    public function show($id): Response
    {
        $oferta = OfertaCompra::with(['proveedor', 'detalles.producto', 'estado', 'user', 'solicitud'])
            ->findOrFail($id);

        return Inertia::render('Compras/Ofertas/Show', [
            'oferta' => $oferta
        ]);
    }
}