<?php

namespace App\Services\Ventas;

use App\Events\VentaRegistrada;
use App\Exceptions\Ventas\LimiteCreditoExcedidoException;
use App\Exceptions\Ventas\SinStockException;
use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\DetalleVenta;
use App\Models\MovimientoStock; 
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\Venta;
use App\Models\EstadoVenta; 
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
        
        // CAMBIO: Usamos el ID del medio de pago configurable
        $medioPagoId = $datosValidados['medio_pago_id'];

        // 2. CÁLCULO (Pre-Transacción)
        $calculos = $this->calcularTotalesVenta($datosValidados, $cliente);

        // 3. VALIDACIÓN DE LÓGICA DE NEGOCIO (Pre-Transacción)
        // Si el medio de pago es "Cuenta Corriente" (esto depende de cómo decida identificarlo, por ID o por nombre)
        // Por ahora asumiré que si el usuario elige el medio de pago "Cuenta Corriente", validamos límite.
        // Podría buscar el medio de pago en la BD y ver si su nombre es 'Cuenta Corriente'.
        
        // VALIDACIÓN DE STOCK 
        $this->validarStockPrevio($calculos['itemsAProcesar']);

        // 4. INICIAR TRANSACCIÓN ATÓMICA
        return DB::transaction(function () use ($datosValidados, $cliente, $vendedorUserID, $medioPagoId, $calculos) {

            // Determinar Estado Inicial (Lógica de Negocio)
            // Si hay deuda o es diferido, estado = Pendiente (1). Si se paga ya, Completada (2).
            // Por simplicidad inicial: Completada. (A futuro, lógica según medioPago).
            $estadoInicial = EstadoVenta::COMPLETADA; 

            // 5. CREAR LA VENTA (Maestro)
            $venta = Venta::create([
                'clienteID' => $cliente->clienteID,
                'user_id' => $vendedorUserID,
                
                // NUEVOS CAMPOS CONFIGURABLES
                'medio_pago_id' => $medioPagoId,
                'estado_venta_id' => $estadoInicial,
                // -------------------------

                'numero_comprobante' => $datosValidados['numero_comprobante'] ?? 'V-'.time(),
                'fecha_venta' => Carbon::now(),
                'subtotal' => $calculos['totalVentaBruto'],
                'total_descuentos' => $calculos['totalDescuentosFinal'],
                'total' => $calculos['totalFinalVenta'],
                'observaciones' => $datosValidados['observaciones'] ?? null,
            ]);

            // 6. GUARDAR DETALLES Y PIVOTES
            $venta->detalles()->saveMany($calculos['detallesParaGuardar']);

            if (! empty($calculos['descuentosGlobalesParaGuardar'])) {
                $venta->descuentos()->attach($calculos['descuentosGlobalesParaGuardar']);
            }

            // === Lógica de Descuento de Stock y Registro de Movimiento ===
            foreach ($calculos['detallesParaGuardar'] as $detalle) {
                $descuentoInfo = collect($calculos['descuentosItemParaGuardar'])->firstWhere('detalle', $detalle);
                if ($descuentoInfo && !empty($descuentoInfo['mapaPivote'])) {
                    $detalle->descuentos()->attach($descuentoInfo['mapaPivote']);
                }

                // Descuento y Movimiento (SOLO si es un producto/no servicio)
                if ($detalle->producto->unidadMedida !== 'Servicio') {
                    $stockRegistro = Stock::where('productoID', $detalle->productoID)->first();
                    
                    if (!$stockRegistro) {
                         throw new SinStockException($detalle->producto->nombre, $detalle->cantidad, 0, "No hay registro de stock para el producto.");
                    }
                    
                    $stockAnterior = $stockRegistro->cantidad_disponible;
                    $cantidadVendida = (int) $detalle->cantidad;

                    // 1. Descontar Stock
                    $stockRegistro->decrement('cantidad_disponible', $cantidadVendida); 
                    
                    // 2. Registrar Movimiento de Stock
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

            // 7. LÓGICA CUENTA CORRIENTE (Post-Creación Venta)
            // Acá debería validar si el medio de pago es "Cuenta Corriente" buscando el objeto MedioPago
            // if ($esCuentaCorriente) { ... } 
            // (Lo dejamo pendiente para cuando implementes la lógica de "es_cuenta_corriente" en el modelo MedioPago)

            // 8. DISPARAR EVENTO
            // event(new VentaRegistrada($venta, $vendedorUserID));

            Log::info("Venta registrada con éxito: ID {$venta->venta_id}");

            return $venta;
        });
    }

    /**
     * Valida el stock de todos los items ANTES de iniciar la transacción.
     */
    private function validarStockPrevio(array $itemsAProcesar): void
    {
        foreach ($itemsAProcesar as $item) {
            $producto = Producto::findOrFail($item['productoID']);
            
            if ($producto->unidadMedida !== 'Servicio') {
                 if (! $producto->tieneStock($item['cantidad'])) {
                    throw new SinStockException($producto->nombre, $item['cantidad'], $producto->stock_total);
                }
            }
        }
    }

    /**
     * Calcula todos los totales de la venta y valida stock/precios.
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
            'itemsAProcesar' => $itemsAProcesar, 
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
