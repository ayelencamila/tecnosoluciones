<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Importante para policies

// Modelos
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\MedioPago;
use App\Models\Descuento;

// Requests
use App\Http\Requests\Ventas\StoreVentaRequest;
use App\Http\Requests\Ventas\AnularVentaRequest; // <--- Asegúrate de tener este archivo

// Servicios
use App\Services\Ventas\RegistrarVentaService;
use App\Services\Ventas\AnularVentaService;      // <--- Asegúrate de tener este archivo
use App\Services\Comprobantes\ComprobanteService;

// Excepciones
use App\Exceptions\Ventas\SinStockException;
use App\Exceptions\Ventas\LimiteCreditoExcedidoException;
use App\Exceptions\Ventas\VentaYaAnuladaException;

class VentaController extends Controller
{
    use AuthorizesRequests;

    /**
     * Listado de Ventas (Index)
     */
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

        // Filtro por estado
        if ($request->filled('estado')) {
             if ($request->estado === 'anulada') {
                 $query->whereHas('estado', fn($q) => $q->where('nombreEstado', 'Anulada'));
             } elseif ($request->estado === 'activa') {
                 $query->whereHas('estado', fn($q) => $q->where('nombreEstado', '!=', 'Anulada'));
             }
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
                'anulada' => ($v->estado->nombreEstado ?? '') === 'Anulada',
            ]);

        return Inertia::render('Ventas/ListadoVentas', [
            'ventas' => $ventas,
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    /**
     * Formulario de Nueva Venta (Create)
     */
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
                    $p->setAttribute('stock_total', $p->stock_total); 
                    return $p;
                }),

            'mediosPago' => MedioPago::where('activo', true)->get(),
            'descuentos' => Descuento::where('activo', true)->get(),
        ]);
    }

    /**
     * Procesar la Venta (Store)
     */
    public function store(StoreVentaRequest $request, RegistrarVentaService $service): RedirectResponse
    {
        try {
            // Pasamos el ID del usuario actual al servicio
            $venta = $service->handle(
                $request->validated(), 
                $request->user()->id
            );

            return redirect()->route('ventas.index')
                ->with('success', 'Venta registrada exitosamente.');

        } catch (SinStockException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        } catch (LimiteCreditoExcedidoException $e) {
            return back()->withErrors(['medio_pago_id' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error("Error al registrar venta: " . $e->getMessage());
            return back()->withErrors(['error' => 'Ocurrió un error inesperado: ' . $e->getMessage()]);
        }
    }

    /**
     * Ver Detalle de Venta (Show)
     */
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

    /**
     * Anular Venta (El método que te faltaba o estaba dañado)
     */
    public function anular(AnularVentaRequest $request, $id, AnularVentaService $service): RedirectResponse
    {
        try {
            $venta = Venta::findOrFail($id);
            
            // Si usas Policies, descomenta esto:
            // $this->authorize('anular', $venta);

            $service->handle($venta, $request->motivo_anulacion, $request->user()->id);

            return redirect()->back() // O route('ventas.index')
                ->with('success', 'Venta anulada correctamente. El stock ha sido revertido.');

        } catch (VentaYaAnuladaException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error("Error anulando venta {$id}: " . $e->getMessage());
            return back()->withErrors(['error' => 'Error al anular: ' . $e->getMessage()]);
        }
    }

    /**
     * Imprimir Comprobante de Venta
     * CU-05 Paso 12: "El sistema genera un comprobante o voucher de venta"
     * 
     * @param int $id ID de la venta
     * @param ComprobanteService $service Servicio para preparar datos
     * @return \Illuminate\View\View Vista del comprobante lista para imprimir
     */
    public function imprimirComprobante($id, ComprobanteService $service)
    {
        $venta = Venta::with([
            'cliente', 
            'vendedor', 
            'estado', 
            'medioPago', 
            'detalles.producto', 
            'descuentos'
        ])->findOrFail($id);

        // Preparar datos siguiendo lineamientos de Kendall
        $datos = $service->prepararDatosComprobanteVenta($venta);

        // Retornar vista Blade optimizada para impresión con window.print()
        return view('comprobantes.comprobante-venta', $datos);
    }

    /**
     * Imprimir Comprobante de Anulación / Nota de Crédito
     * CU-06 Paso 10: "Confirma la anulación exitosa de la venta y presenta un comprobante interno de anulación/crédito"
     * 
     * @param int $id ID de la venta anulada
     * @param ComprobanteService $service Servicio para preparar datos
     * @return \Illuminate\View\View Vista del comprobante de anulación lista para imprimir
     */
    public function imprimirComprobanteAnulacion($id, ComprobanteService $service)
    {
        $venta = Venta::with([
            'cliente', 
            'vendedor', 
            'estado', 
            'medioPago', 
            'detalles.producto', 
            'descuentos'
        ])->findOrFail($id);

        // Verificar que la venta esté anulada
        if ($venta->estado->nombreEstado !== 'Anulada') {
            return back()->withErrors(['error' => 'Solo se puede imprimir comprobante de anulación para ventas anuladas.']);
        }

        // Preparar datos siguiendo lineamientos de Kendall
        $datos = $service->prepararDatosComprobanteAnulacion($venta);

        // Retornar vista Blade optimizada para impresión con window.print()
        return view('comprobantes.comprobante-anulacion', $datos);
    }
}
