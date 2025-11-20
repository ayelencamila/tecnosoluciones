<?php

namespace App\Services\Ventas;

// Usamos todos los modelos y excepciones que creamos/definimos
use App\Events\VentaRegistrada;
use App\Exceptions\Ventas\LimiteCreditoExcedidoException;
use App\Exceptions\Ventas\SinStockException;
use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\DetalleVenta;
use App\Models\MovimientoStock; // <--- NUEVO
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\Stock; // <--- NUEVO
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarVentaService
{
    /**
     * Orquesta el caso de uso completo para registrar una nueva venta.
     */
    public function handle(array $datosValidados): Venta
    {
        // 1. OBTENER ENTIDADES
        $cliente = Cliente::with('tipoCliente', 'cuentaCorriente.estadoCuentaCorriente')->findOrFail($datosValidados['clienteID']);
        $vendedorUserID = $datosValidados['userID'];
        $metodoPago = $datosValidados['metodo_pago'];

        // 2. CÁLCULO (Pre-Transacción)
        $calculos = $this->calcularTotalesVenta($datosValidados, $cliente);

        // 3. VALIDACIÓN DE LÓGICA DE NEGOCIO (Pre-Transacción)
        if ($metodoPago === 'cuenta_corriente') {
            $this->validarEstadoCredito($cliente, $calculos['totalFinalVenta']);
        }
        // VALIDACIÓN DE STOCK RE-IMPLEMENTADA (Paso 9)
        $this->validarStockPrevio($calculos['itemsAProcesar']);

        // 4. INICIAR TRANSACCIÓN ATÓMICA
        return DB::transaction(function () use ($datosValidados, $cliente, $vendedorUserID, $metodoPago, $calculos) {

            // 5. (CREATOR - GRASP) CREAR LA VENTA (Maestro)
            $venta = Venta::create([
                'clienteID' => $cliente->clienteID,
                'user_id' => $vendedorUserID,
                'numero_comprobante' => $datosValidados['numero_comprobante'] ?? 'V-'.time(),
                'fecha_venta' => Carbon::now(),
                'subtotal' => $calculos['totalVentaBruto'],
                'total_descuentos' => $calculos['totalDescuentosFinal'],
                'total' => $calculos['totalFinalVenta'],
                'anulada' => false,
                'observaciones' => $datosValidados['observaciones'] ?? null,
            ]);

            // 6. GUARDAR DETALLES Y PIVOTES
            $venta->detalles()->saveMany($calculos['detallesParaGuardar']);

            if (! empty($calculos['descuentosGlobalesParaGuardar'])) {
                $venta->descuentos()->attach($calculos['descuentosGlobalesParaGuardar']);
            }
            // Los descuentos por ítem se guardan más adelante para tener el detalle_venta_id

            // === Lógica de Descuento de Stock y Registro de Movimiento (Paso 10/RF5) ===
            foreach ($calculos['detallesParaGuardar'] as $detalle) {
                // El modelo DetalleVenta se guarda en el paso anterior, pero necesitamos el ID para el pivote.
                // Usamos el 'pivot' temporal para adjuntar los descuentos por ítem
                $descuentoInfo = collect($calculos['descuentosItemParaGuardar'])->firstWhere('detalle', $detalle);
                if ($descuentoInfo && !empty($descuentoInfo['mapaPivote'])) {
                     // Adjuntar descuentos al detalle recién guardado
                    $detalle->descuentos()->attach($descuentoInfo['mapaPivote']);
                }

                // Descuento y Movimiento (SOLO si es un producto/no servicio)
                if ($detalle->producto->unidadMedida !== 'Servicio') { // Asumo que tus productos de servicio tienen 'Servicio' en unidadMedida
                    $stockRegistro = Stock::where('productoID', $detalle->productoID)->first();
                    
                    if (!$stockRegistro) {
                        // Esto no debería pasar si la validación es correcta, pero es un seguro
                         throw new SinStockException($detalle->producto->nombre, $detalle->cantidad, 0, "No hay registro de stock para el producto.");
                    }
                    
                    $stockAnterior = $stockRegistro->cantidad_disponible;
                    $cantidadVendida = (int) $detalle->cantidad;

                    // 1. Descontar Stock (Delegar a Information Expert Stock)
                    $stockRegistro->decrement('cantidad_disponible', $cantidadVendida); 
                    
                    // 2. Registrar Movimiento de Stock (CU-05, PosCondición: descargas de stock)
                    MovimientoStock::create([
                        'productoID' => $detalle->productoID,
                        'tipoMovimiento' => 'SALIDA',
                        'cantidad' => $cantidadVendida,
                        'stockAnterior' => $stockAnterior,
                        'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible, 
                        'motivo' => 'Venta N° ' . $venta->numero_comprobante,
                        'referenciaID' => $venta->venta_id,
                        'referenciaTabla' => 'ventas',
                    ]);
                }
            }
            // =================================================================================

            // 7. LÓGICA CUENTA CORRIENTE (Post-Creación Venta)
            if ($calculos['totalFinalVenta'] > 0 && $metodoPago === 'cuenta_corriente') {
                $cuentaCorriente = $cliente->cuentaCorriente;
                if (! $cuentaCorriente) {
                    throw new \Exception('El cliente no tiene una cuenta corriente asignada para ventas a crédito.');
                }
                
                $fechaVencimientoCC = Carbon::now()->addDays($cliente->cuentaCorriente->getDiasGraciaAplicables());

                $cuentaCorriente->registrarDebito(
                    $calculos['totalFinalVenta'],
                    'Venta N° '.$venta->numero_comprobante,
                    $fechaVencimientoCC, 
                    $venta->venta_id, 
                    'ventas',
                    $vendedorUserID
                );
                Log::info("Débito registrado en CC para Venta ID: {$venta->venta_id}");
            }

            // 8. DISPARAR EVENTO (Paso 11)
            event(new VentaRegistrada($venta, $metodoPago, $vendedorUserID));

            Log::info("Venta registrada con éxito: ID {$venta->venta_id}");

            return $venta;
        });
    }

    /**
     * Valida el stock de todos los items ANTES de iniciar la transacción. (Paso 9)
     */
    private function validarStockPrevio(array $itemsAProcesar): void
    {
        foreach ($itemsAProcesar as $item) {
            $producto = Producto::findOrFail($item['productoID']);
            
            // Solo validar stock si no es un servicio (Asumo que los servicios no tienen stock)
            if ($producto->unidadMedida !== 'Servicio') {
                 if (! $producto->tieneStock($item['cantidad'])) {
                    throw new SinStockException($producto->nombre, $item['cantidad'], $producto->stock_total);
                }
            }
        }
    }


    /**
     * Calcula todos los totales de la venta y valida stock/precios.
     * (Se agregó 'itemsAProcesar' al return para la nueva validación)
     */
    private function calcularTotalesVenta(array $datosValidados, Cliente $cliente): array
    {
        $itemsAProcesar = $datosValidados['items'];
        $descuentosGlobalesInput = $datosValidados['descuentos_globales'] ?? [];

        $totalVentaBruto = 0;
        $totalDescuentosItems = 0;
        $detallesParaGuardar = [];
        $descuentosItemParaGuardar = [];

        foreach ($itemsAProcesar as $item) {
            $producto = Producto::findOrFail($item['productoID']);
            
            $precioUnitario = $this->obtenerPrecioParaCliente($producto, $cliente);
            $subtotalItem = (float) $item['cantidad'] * $precioUnitario;
            $totalDescuentoEsteItem = 0;
            $detallesTemporalesDescuento = [];

            if (! empty($item['descuentos_item'])) {
                foreach ($item['descuentos_item'] as $descInput) {
                    $descuento = Descuento::findOrFail($descInput['descuento_id']);
                    $montoAplicado = $descuento->calcularMonto($subtotalItem);
                    $totalDescuentoEsteItem += $montoAplicado;
                    $detallesTemporalesDescuento[] = [
                        'descuento_id' => $descuento->descuento_id,
                        'monto_aplicado_item' => $montoAplicado,
                    ];
                }
            }

            $subtotalNetoItem = $subtotalItem - $totalDescuentoEsteItem;
            $totalVentaBruto += $subtotalItem;
            $totalDescuentosItems += $totalDescuentoEsteItem;

            $detalleVenta = new DetalleVenta([
                'productoID' => $producto->id, 
                'precio_producto_id' => $item['precio_producto_id'] ?? null,
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotalItem,
                'descuento_item' => $totalDescuentoEsteItem,
                'subtotal_neto' => $subtotalNetoItem,
            ]);
            $detallesParaGuardar[] = $detalleVenta;
            $mapaPivoteItems = [];
            foreach ($detallesTemporalesDescuento as $desc) {
                $mapaPivoteItems[$desc['descuento_id']] = ['monto_aplicado_item' => $desc['monto_aplicado_item']];
            }
            $descuentosItemParaGuardar[] = ['detalle' => $detalleVenta, 'mapaPivote' => $mapaPivoteItems];
        }

        $totalDescuentosGlobales = 0;
        $descuentosGlobalesParaGuardar = [];

        foreach ($descuentosGlobalesInput as $descInput) {
            $descuento = Descuento::findOrFail($descInput['descuento_id']);
            $montoAplicado = $descuento->calcularMonto($totalVentaBruto);
            $totalDescuentosGlobales += $montoAplicado;
            $descuentosGlobalesParaGuardar[$descuento->descuento_id] = ['monto_aplicado' => $montoAplicado];
        }

        $totalDescuentosFinal = $totalDescuentosItems + $totalDescuentosGlobales;
        $totalFinalVenta = $totalVentaBruto - $totalDescuentosFinal;

        return [
            'totalVentaBruto' => $totalVentaBruto,
            'totalDescuentosFinal' => $totalDescuentosFinal,
            'totalFinalVenta' => $totalFinalVenta,
            'detallesParaGuardar' => $detallesParaGuardar,
            'descuentosGlobalesParaGuardar' => $descuentosGlobalesParaGuardar,
            'descuentosItemParaGuardar' => $descuentosItemParaGuardar,
            'itemsAProcesar' => $itemsAProcesar, // Se devuelve para la validación previa
        ];
    }
    /**
     * Valida el estado actual Y el límite de crédito del cliente.
     */
    private function validarEstadoCredito(Cliente $cliente, float $montoDeEstaVenta): void
    {
        if (! $cliente->cuentaCorriente) {
            throw new LimiteCreditoExcedidoException("El cliente {$cliente->nombre_completo} no tiene una cuenta corriente habilitada.", 0, 0);
        }
        $cuentaCorriente = $cliente->cuentaCorriente;
        $estadoNombre = $cuentaCorriente->estadoCuentaCorriente?->nombreEstado ?? 'Desconocido';

        if ($estadoNombre === 'Bloqueada') {
            throw new LimiteCreditoExcedidoException("La cuenta corriente de {$cliente->nombre_completo} se encuentra BLOQUEADA. Solo se permiten ventas al contado.", 0, 0);
        }
        if ($estadoNombre === 'Pendiente de Aprobación') {
            throw new LimiteCreditoExcedidoException("La cuenta corriente de {$cliente->nombre_completo} está PENDIENTE DE APROBACIÓN. No se permiten ventas a crédito hasta su revisión.", 0, 0);
        }

        $saldoActual = $cuentaCorriente->saldo; 
        $limiteCredito = $cuentaCorriente->getLimiteCreditoAplicable(); 

        if (! is_numeric($limiteCredito)) {
            throw new \Exception('Error de configuración: Límite de crédito no válido para el cliente.');
        }

        if (($saldoActual + $montoDeEstaVenta) > $limiteCredito) {
            $disponible = $limiteCredito - $saldoActual;
            throw new LimiteCreditoExcedidoException(
                "La venta excede el límite de crédito del cliente. Límite: $$limiteCredito, Saldo Actual: $$saldoActual, Disponible: $$disponible, Monto Venta: $$montoDeEstaVenta",
                $limiteCredito,
                $saldoActual
            );
        }
    }

    /**
     * Obtiene el precio vigente para un producto y tipo de cliente.
     */
    private function obtenerPrecioParaCliente(Producto $producto, Cliente $cliente): float
    {
        $precioProducto = PrecioProducto::where('productoID', $producto->id) 
            ->where('tipoClienteID', $cliente->tipoClienteID)
            ->where('fechaDesde', '<=', Carbon::now())
            ->where(function ($query) {
                $query->where('fechaHasta', '>=', Carbon::now())
                      ->orWhereNull('fechaHasta');
            })
            ->orderBy('fechaDesde', 'desc')
            ->first();

        if ($precioProducto) {
            return (float) $precioProducto->precio;
        }

        throw new \Exception("No se encontró un precio vigente para el producto '{$producto->nombre}' y el tipo de cliente '{$cliente->tipoCliente->nombreEstado}'.");
    }
}
