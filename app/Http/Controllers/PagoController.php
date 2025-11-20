<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Cliente;
use App\Services\Pagos\RegistrarPagoService;
use App\Http\Requests\Pagos\StorePagoRequest;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log; 
use Exception;

class PagoController extends Controller
{
    /**
     * Muestra el listado de pagos.
     */
    public function index(Request $request)
    {
        $pagos = Pago::query()
            ->with(['cliente:clienteID,nombre,apellido,DNI', 'cajero:id,name'])
            ->orderBy('fecha_pago', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Pagos/ListadoPagos', [
            'pagos' => $pagos,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Formulario de nuevo pago.
     */
    public function create()
    {
        // Solo mostramos clientes con Cuenta Corriente activa para imputar pagos
        $clientes = Cliente::whereHas('cuentaCorriente')
            ->select('clienteID', 'nombre', 'apellido', 'DNI')
            ->with('cuentaCorriente:cuentaCorrienteID,saldo,estadoCuentaCorrienteID') // Traer saldo para mostrar
            ->orderBy('apellido')
            ->get();

        return Inertia::render('Pagos/FormularioPago', [
            'clientes' => $clientes
        ]);
    }

    /**
     * Guarda el pago.
     */
    public function store(StorePagoRequest $request, RegistrarPagoService $registrarPagoService)
    {
        try {
            $pago = $registrarPagoService->handle($request->validated(), auth()->id());

            return to_route('pagos.show', $pago->pago_id)
                   ->with('success', '¡Pago registrado e imputado con éxito!');

        } catch (Exception $e) {
            Log::error("Error al registrar pago: " . $e->getMessage());
            return back()->withErrors(['message' => 'Error inesperado al registrar el pago.']);
        }
    }

    /**
     * Muestra el recibo.
     */
    public function show(Pago $pago)
    {
        // CARGAMOS LAS VENTAS QUE ESTE PAGO CUBRIÓ
        $pago->load(['cliente', 'cajero', 'ventasImputadas']);

        return Inertia::render('Pagos/DetallePago', [
            'pago' => $pago
        ]);
    }
}