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

        return Inertia::render('Clientes/Index', [
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
     * Verifica si un cliente puede ser dado de baja (API JSON para modal)
     */
    public function verificarBaja(Cliente $cliente)
    {
        $cliente->load(['cuentaCorriente', 'ventas.estado', 'reparaciones.estado']);
        
        return response()->json([
            'operacionesPendientes' => $cliente->getOperacionesPendientes(),
            'puedeSerDadoDeBaja' => $cliente->puedeSerDadoDeBaja(),
        ]);
    }

    /**
     * Muestra la confirmación para dar de baja (CU-04 Paso 2-5)
     */
    public function confirmDelete(Cliente $cliente)
    {
        $cliente->load(['tipoCliente', 'estadoCliente', 'cuentaCorriente', 'ventas.estado', 'reparaciones.estado']);
        
        return Inertia::render('Clientes/ConfirmDelete', [
            'cliente' => $cliente,
            'operacionesPendientes' => $cliente->getOperacionesPendientes(),
            'puedeSerDadoDeBaja' => $cliente->puedeSerDadoDeBaja(),
        ]);
    }

    /**
     * Da de baja un cliente (CU-04)
     * 
     * Excepciones manejadas:
     * - 4a: Operaciones pendientes (verificado en modelo)
     * - 9a: Error al registrar la baja (capturado aquí)
     * - 9b: Error en historial (manejado en modelo, flujo continúa)
     */
    public function darDeBaja(DarDeBajaClienteRequest $request, Cliente $cliente)
    {
        try {
            // Delegamos al Modelo Experto (maneja excepciones 4a, 9a, 9b)
            $cliente->darDeBaja($request->motivo);
            
            // CU-04 Paso 10: Confirma la baja exitosa
            return redirect()->back()->with('success', 'El cliente ha sido dado de baja exitosamente.');

        } catch (\Exception $e) {
            // CU-04 Excepción 9a: Error al procesar la baja
            Log::error('CU-04: Error al dar de baja cliente', [
                'clienteID' => $cliente->clienteID,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['motivo' => $e->getMessage()]);
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
                $motivo = $request->input('motivo', 'Cliente reactivado manualmente por admin.');
                $cliente->reactivar($motivo);
            } else {
                $request->validate([
                    'motivo' => 'required|string|min:3|max:255',
                ], [
                    'motivo.required' => 'Debe ingresar un motivo para desactivar el cliente.',
                    'motivo.min' => 'El motivo debe tener al menos 3 caracteres.',
                ]);
                $cliente->darDeBaja($request->input('motivo'));
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
            ->whereHas('estadoCliente', fn($q) => $q->where('nombreEstado', 'Activo'))
            ->where(function ($q) use ($query) {
                $q->where('nombre', 'like', "%{$query}%")
                  ->orWhere('apellido', 'like', "%{$query}%")
                  ->orWhere('DNI', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['clienteID', 'nombre', 'apellido', 'DNI', 'tipoClienteID', 'cuentaCorrienteID']);

        return response()->json($clientes);
    }
}