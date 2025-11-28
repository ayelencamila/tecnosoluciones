<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

// Modelos
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\MedioPago;
use App\Models\Descuento;

// Requests y Servicios
use App\Http\Requests\Ventas\StoreVentaRequest;
use App\Services\Ventas\RegistrarVentaService;
// use App\Services\Ventas\AnularVentaService; // Descomentar cuando lo tengas

// Excepciones
use App\Exceptions\Ventas\SinStockException;
use App\Exceptions\Ventas\LimiteCreditoExcedidoException;

class VentaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Venta::with(['cliente', 'vendedor', 'estado', 'medioPago'])
            ->latest('fecha_venta');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('numero_comprobante', 'like', "%{$search}%")
                  ->orWhereHas('cliente', fn($q) => 
                      $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%")
                  );
        }

        $ventas = $query->paginate(10)
            ->withQueryString()
            ->through(fn ($v) => [
                'venta_id' => $v->venta_id,
                'fecha' => $v->fecha_venta->format('d/m/Y H:i'),
                'comprobante' => $v->numero_comprobante,
                'cliente' => $v->cliente ? "{$v->cliente->apellido}, {$v->cliente->nombre}" : 'Consumidor Final',
                'total' => $v->total,
                'estado' => $v->estado->nombreEstado ?? 'N/A',
                'medio_pago' => $v->medioPago->nombre ?? 'N/A',
                'anulada' => $v->estado->nombreEstado === 'Anulada',
            ]);

        return Inertia::render('Ventas/ListadoVentas', [
            'ventas' => $ventas,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Ventas/FormularioVenta', [
            'clientes' => Cliente::select('clienteID', 'nombre', 'apellido', 'dni', 'tipoClienteID')
                ->with('tipoCliente', 'cuentaCorriente')
                ->orderBy('apellido')
                ->get(),
            
            'productos' => Producto::where('estadoProductoID', 1)
                ->with(['categoria', 'precios'])
                ->get()
                ->map(function($p) {
                    $p->stock_actual = $p->stock_total; 
                    return $p;
                }),

            'mediosPago' => MedioPago::where('activo', true)->get(),
            'descuentos' => Descuento::where('activo', true)->get(),
        ]);
    }

    public function store(StoreVentaRequest $request, RegistrarVentaService $service): RedirectResponse
    {
        try {
            // CORRECCIÓN AQUÍ: Pasamos el ID del usuario como segundo argumento
            $venta = $service->handle(
                $request->validated(), 
                $request->user()->id
            );

            // Redirigir a index o show según prefieras (aquí index para fluidez)
            return redirect()->route('ventas.index')
                ->with('success', 'Venta registrada exitosamente.');

        } catch (SinStockException $e) {
            return back()->withErrors(['items' => $e->getMessage()]); // Error asociado a items
        } catch (LimiteCreditoExcedidoException $e) {
            return back()->withErrors(['medio_pago_id' => $e->getMessage()]); // Error asociado al pago
        } catch (\Exception $e) {
            Log::error("Error al registrar venta: " . $e->getMessage());
            return back()->withErrors(['error' => 'Ocurrió un error inesperado: ' . $e->getMessage()]);
        }
    }

    public function show($id): Response
    {
        $venta = Venta::with([
            'cliente', 
            'vendedor', 
            'estado', 
            'medioPago', 
            'detalles.producto', 
            'descuentos'
        ])->findOrFail($id);

        return Inertia::render('Ventas/DetalleVenta', [
            'venta' => $venta
        ]);
    }
}