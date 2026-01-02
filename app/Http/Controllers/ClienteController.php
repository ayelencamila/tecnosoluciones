<?php

namespace App\Http\Controllers;

// Importaciones de Modelos
use App\Models\Cliente;
use App\Models\Provincia;
use App\Models\TipoCliente;
use App\Models\EstadoCliente;
use App\Models\EstadoCuentaCorriente;
use App\Models\Auditoria;

// --- ARQUITECTURA LARMAN (BCE) ---
// 1. Boundaries (Validación)
use App\Http\Requests\Clientes\StoreClienteRequest;
use App\Http\Requests\Clientes\UpdateClienteRequest;
use App\Http\Requests\Clientes\DarDeBajaClienteRequest;

// 2. Controls (Lógica de Negocio)
use App\Services\Clientes\RegistrarClienteService;
use App\Services\Clientes\UpdateClienteService;
// --- FIN ARQUITECTURA LARMAN ---

// Clases de Laravel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;

class ClienteController extends Controller
{
    /**
     * Muestra una lista de clientes (CU-03)
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'tipo_cliente_id', 'estado_cliente_id', 'provincia_id', 'sort_column', 'sort_direction']);
        $sortColumn = $filters['sort_column'] ?? 'apellido'; // Mejor por defecto apellido
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        
        $allowedSortColumns = ['nombre', 'apellido', 'DNI', 'mail', 'whatsapp', 'created_at', 'tipoClienteID', 'estadoClienteID'];
        
        if (! in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'apellido';
        }

        $query = Cliente::query()
            ->with(['tipoCliente', 'estadoCliente', 'direccion.localidad.provincia']);

        if (isset($filters['search']) && $filters['search']) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nombre', 'like', '%'.$searchTerm.'%')
                  ->orWhere('apellido', 'like', '%'.$searchTerm.'%')
                  ->orWhere('DNI', 'like', '%'.$searchTerm.'%')
                  ->orWhere('mail', 'like', '%'.$searchTerm.'%'); // Agregado mail a la búsqueda
            });
        }

        if (isset($filters['tipo_cliente_id']) && $filters['tipo_cliente_id']) {
            $query->where('tipoClienteID', $filters['tipo_cliente_id']);
        }
        if (isset($filters['estado_cliente_id']) && $filters['estado_cliente_id']) {
            $query->where('estadoClienteID', $filters['estado_cliente_id']);
        }
        if (isset($filters['provincia_id']) && $filters['provincia_id']) {
            $query->whereHas('direccion.localidad', function ($q) use ($filters) {
                $q->where('provinciaID', $filters['provincia_id']);
            });
        }

        // Ordenamiento
        if ($sortColumn === 'tipoClienteID') {
            $query->join('tipos_cliente', 'clientes.tipoClienteID', '=', 'tipos_cliente.tipoClienteID')
                ->orderBy('tipos_cliente.nombreTipo', $sortDirection)
                ->select('clientes.*');
        } elseif ($sortColumn === 'estadoClienteID') {
            $query->join('estados_cliente', 'clientes.estadoClienteID', '=', 'estados_cliente.estadoClienteID')
                ->orderBy('estados_cliente.nombreEstado', $sortDirection)
                ->select('clientes.*');
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $clientes = $query->paginate(10)->withQueryString(); // withQueryString mantiene los filtros al paginar
        
        // Lógica de contadores (Dashboard en Index)
        $counts = [
            'total' => Cliente::count(),
            'activos' => Cliente::whereHas('estadoCliente', fn($q) => $q->where('nombreEstado', 'Activo'))->count(),
            'inactivos' => Cliente::whereHas('estadoCliente', fn($q) => $q->where('nombreEstado', 'Inactivo'))->count(),
        ]; 

        return Inertia::render('Clientes/index', [
            'clientes' => $clientes,
            'estadosCliente' => EstadoCliente::all(['estadoClienteID', 'nombreEstado']),
            'tiposCliente' => TipoCliente::all(['tipoClienteID', 'nombreTipo']),
            'provincias' => Provincia::all(['provinciaID', 'nombre']),
            'filters' => $filters,
            'counts' => $counts,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo cliente (CU-01)
     */
    public function create()
    {
        return Inertia::render('Clientes/Create', [
            'provincias' => Provincia::orderBy('nombre')->get(),
            'tiposCliente' => TipoCliente::where('activo', true)->orderBy('nombreTipo')->get(),
            'estadosCliente' => EstadoCliente::orderBy('nombreEstado')->get(),
            'estadosCuentaCorriente' => EstadoCuentaCorriente::orderBy('nombreEstado')->get(),
        ]);
    }

    /**
     * Almacena un cliente (CU-01)
     */
    public function store(StoreClienteRequest $request, RegistrarClienteService $service)
    {
        try {
            $cliente = $service->handle($request->validated());
            
            return redirect()->route('clientes.show', $cliente->clienteID)
                             ->with('success', 'Cliente registrado exitosamente.');

        } catch (\Exception $e) {
            Log::error("Error al registrar cliente: " . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error al registrar el cliente: '.$e->getMessage()]);
        }
    }

    /**
     * Muestra los detalles de un cliente (CU-03)
     */
    public function show(Cliente $cliente)
    {
        $cliente->load([
            'tipoCliente', 
            'estadoCliente', 
            'direccion.localidad.provincia', 
            'cuentaCorriente.estadoCuentaCorriente',
            'cuentaCorriente.movimientosCC' => function($q) {
                $q->latest('created_at')->limit(20);
            }
        ]);
        
        $historialAuditoria = Auditoria::historialCliente($cliente->clienteID);

        return Inertia::render('Clientes/Show', [
            'cliente' => $cliente,
            'historialAuditoria' => $historialAuditoria,
        ]);
    }

    /**
     * Muestra el formulario para editar un cliente (CU-02)
     */
    public function edit(Cliente $cliente)
    {
        $cliente->load(['tipoCliente', 'estadoCliente', 'direccion.localidad.provincia', 'cuentaCorriente.estadoCuentaCorriente']);

        return Inertia::render('Clientes/Edit', [
            'cliente' => $cliente,
            'provincias' => Provincia::orderBy('nombre')->get(),
            'tiposCliente' => TipoCliente::where('activo', true)->orderBy('nombreTipo')->get(),
            'estadosCliente' => EstadoCliente::orderBy('nombreEstado')->get(),
            'estadosCuentaCorriente' => EstadoCuentaCorriente::orderBy('nombreEstado')->get(),
        ]);
    }

    /**
     * Actualiza un cliente (CU-02)
     */
    public function update(UpdateClienteRequest $request, Cliente $cliente, UpdateClienteService $service)
    {
        try {
            $service->handle($cliente, $request->validated());

            return redirect()->route('clientes.show', $cliente->clienteID)
                             ->with('success', 'Cliente actualizado exitosamente.');

        } catch (\Exception $e) {
            Log::error("Error al actualizar cliente {$cliente->clienteID}: " . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error al actualizar el cliente: '.$e->getMessage()]);
        }
    }

    /**
     * Muestra la confirmación para dar de baja (CU-04 Paso 2-5)
     */
    public function confirmDelete(Cliente $cliente)
    {
        $cliente->load(['tipoCliente', 'estadoCliente', 'cuentaCorriente']);
        
        // CU-04 Paso 4: Verificar operaciones activas pendientes
        $operacionesPendientes = [];
        
        // Ventas pendientes
        $ventasPendientes = $cliente->ventas()
            ->whereHas('estado', function($q) {
                $q->where('nombre', 'Pendiente');
            })
            ->count();
        if ($ventasPendientes > 0) {
            $operacionesPendientes[] = "Ventas pendientes de pago: {$ventasPendientes}";
        }
        
        // Reparaciones en curso
        $reparacionesPendientes = $cliente->reparaciones()
            ->whereHas('estadoReparacion', function($q) {
                $q->whereNotIn('nombre', ['Cancelada', 'Entregada']);
            })
            ->count();
        if ($reparacionesPendientes > 0) {
            $operacionesPendientes[] = "Reparaciones en curso: {$reparacionesPendientes}";
        }
        
        // Deuda pendiente
        if ($cliente->tieneDeudas()) {
            $saldo = $cliente->cuentaCorriente->saldo ?? 0;
            $operacionesPendientes[] = "Deuda pendiente: $" . number_format($saldo, 2);
        }
        
        return Inertia::render('Clientes/ConfirmDelete', [
            'cliente' => $cliente,
            'operacionesPendientes' => $operacionesPendientes,
            'puedeSerDadoDeBaja' => $cliente->puedeSerDadoDeBaja(),
        ]);
    }

    /**
     * Da de baja un cliente (CU-04)
     */
    public function darDeBaja(DarDeBajaClienteRequest $request, Cliente $cliente)
    {
        try {
            // Delegamos al Modelo Experto
            $cliente->darDeBaja($request->motivo);
            
            return redirect()->route('clientes.index')->with('success', 'Cliente dado de baja exitosamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al dar de baja el cliente: '.$e->getMessage()]);
        }
    }

    /**
     * Alterna el estado activo/inactivo de un cliente.
     */
    public function toggleActivo(Cliente $cliente, Request $request)
    {
        try {
            $nuevoEstado = $request->boolean('activo'); 

            if ($nuevoEstado) {
                $cliente->reactivar('Cliente reactivado manualmente por admin.');
            } else {
                $cliente->darDeBaja('Cliente desactivado manualmente por admin.');
            }
            
            return redirect()->back()->with('success', 'Estado del cliente actualizado.');

        } catch (\Exception $e) {
            Log::error('Error al actualizar el estado activo del cliente: '.$e->getMessage(), ['clienteID' => $cliente->clienteID]);
            return redirect()->back()->withErrors(['error' => 'Error al actualizar el estado: '.$e->getMessage()]);
        }
    }

    /**
     * Elimina un cliente (alias de 'darDeBaja')
     */
    public function destroy(Cliente $cliente)
    {
        try {
            $cliente->darDeBaja('Baja por eliminación de sistema (destroy).');
            
            return redirect()->route('clientes.index')->with('success', 'Cliente puesto en estado inactivo exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error al usar destroy (darDeBaja) en cliente: '.$e->getMessage(), ['clienteID' => $cliente->clienteID]);
            return redirect()->back()->withErrors(['error' => 'Error al dar de baja el cliente: '.$e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    //  MÉTODOS API (AJAX / AXIOS)
    // -------------------------------------------------------------------------

    /**
     * API para buscador asíncrono (Select de Reparaciones/Ventas)
     * ¡ESTE ES EL MÉTODO QUE TE FALTABA PARA QUE EL BUSCADOR FUNCIONE!
     */
    public function buscar(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query) {
            return response()->json([]);
        }

        $clientes = Cliente::with(['tipoCliente', 'cuentaCorriente.estadoCuentaCorriente'])
            ->where('nombre', 'like', "%{$query}%")
            ->orWhere('apellido', 'like', "%{$query}%")
            ->orWhere('dni', 'like', "%{$query}%")
            ->limit(20)
            ->get(['clienteID', 'nombre', 'apellido', 'dni', 'tipoClienteID', 'cuentaCorrienteID']);

        return response()->json($clientes);
    }
}