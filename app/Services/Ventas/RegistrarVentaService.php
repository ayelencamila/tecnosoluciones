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
use App\Models\MedioPago; // <--- Necesario para buscar el nombre
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarVentaService
{
    public function handle(array $datosValidados, int $vendedorUserID): Venta
    {
        // 1. OBTENER ENTIDADES
        $cliente = Cliente::with('tipoCliente', 'cuentaCorriente.estadoCuentaCorriente')->findOrFail($datosValidados['clienteID']);
        $medioPagoId = $datosValidados['medio_pago_id'];

        // 2. CÁLCULO
        $calculos = $this->calcularTotalesVenta($datosValidados, $cliente);

        // 3. VALIDACIÓN DE REGLAS DE NEGOCIO
        $this->validarStockPrevio($calculos['itemsAProcesar']);
        
        // Verificar si es Cuenta Corriente para validar límite
        $medioPagoObj = MedioPago::find($medioPagoId);
        // Lógica: Si el nombre del medio de pago tiene "corriente" (insensible a mayúsculas)
        $esCuentaCorriente = $medioPagoObj && str_contains(strtolower($medioPagoObj->nombre), 'corriente');

        if ($esCuentaCorriente) {
            $this->validarEstadoCredito($cliente, $calculos['totalFinalVenta']);
        }

        // 4. TRANSACCIÓN
        return DB::transaction(function () use ($datosValidados, $cliente, $vendedorUserID, $medioPagoId, $calculos, $esCuentaCorriente) {

            // Estado Inicial: Si es CC, queda 'Pendiente'. Si no, 'Completada'.
            $estadoInicial = $esCuentaCorriente ? EstadoVenta::PENDIENTE : EstadoVenta::COMPLETADA; 

            // 5. CREAR VENTA
            $venta = Venta::create([
                'clienteID' => $cliente->clienteID,
                'user_id' => $vendedorUserID,
                'medio_pago_id' => $medioPagoId,
                'estado_venta_id' => $estadoInicial,
                'numero_comprobante' => $datosValidados['numero_comprobante'] ?? 'V-'.time(),
                'fecha_venta' => Carbon::now(),
                'subtotal' => $calculos['totalVentaBruto'],
                'total_descuentos' => $calculos['totalDescuentosFinal'],
                'total' => $calculos['totalFinalVenta'],
                'observaciones' => $datosValidados['observaciones'] ?? null,
            ]);

            // 6. GUARDAR DETALLES
            $venta->detalles()->saveMany($calculos['detallesParaGuardar']);

            if (! empty($calculos['descuentosGlobalesParaGuardar'])) {
                $venta->descuentos()->attach($calculos['descuentosGlobalesParaGuardar']);
            }

            // === STOCK ===
            foreach ($calculos['detallesParaGuardar'] as $detalle) {
                $descuentoInfo = collect($calculos['descuentosItemParaGuardar'])->firstWhere('detalle', $detalle);
                if ($descuentoInfo && !empty($descuentoInfo['mapaPivote'])) {
                    $detalle->descuentos()->attach($descuentoInfo['mapaPivote']);
                }

                if ($detalle->producto->unidadMedida !== 'Servicio') {
                    $stockRegistro = Stock::where('productoID', $detalle->producto_id)->first(); // Usamos producto_id corregido
                    
                    if ($stockRegistro) {
                        $stockAnterior = $stockRegistro->cantidad_disponible;
                        $cantidadVendida = (int) $detalle->cantidad;

                        $stockRegistro->decrement('cantidad_disponible', $cantidadVendida); 
                        
                        MovimientoStock::create([
                            'productoID' => $detalle->producto_id,
                            'tipoMovimiento' => 'SALIDA',
                            'cantidad' => $cantidadVendida,
                            'stockAnterior' => $stockAnterior,
                            'stockNuevo' => $stockRegistro->fresh()->cantidad_disponible, 
                            'motivo' => 'Venta N° ' . $venta->numero_comprobante,
                            'referenciaID' => $venta->venta_id,
                            'referenciaTabla' => 'ventas',
                            'user_id' => $vendedorUserID 
                        ]);
                    }
                }
            }

            // 7. LÓGICA CUENTA CORRIENTE (ACTIVADA AHORA SÍ)
            if ($esCuentaCorriente && $calculos['totalFinalVenta'] > 0) {
                
                $cuentaCorriente = $cliente->cuentaCorriente;
                
                if (!$cuentaCorriente) {
                    throw new \Exception('El cliente no tiene una cuenta corriente habilitada.');
                }

                $diasGracia = $cuentaCorriente->diasGracia ?? 0;
                $fechaVencimientoCC = Carbon::now()->addDays($diasGracia);

                // REGISTRAR DÉBITO (Aumenta Deuda)
                $cuentaCorriente->registrarDebito(
                    $calculos['totalFinalVenta'],
                    'Venta N° ' . $venta->numero_comprobante,
                    $fechaVencimientoCC, 
                    $venta->venta_id, 
                    'ventas',
                    $vendedorUserID
                );
                
                Log::info("Deuda registrada en CC Cliente {$cliente->clienteID}: {$calculos['totalFinalVenta']}");
            }

            // 8. EVENTO
            // event(new VentaRegistrada($venta, $vendedorUserID));

            Log::info("Venta registrada con éxito: ID {$venta->venta_id}");

            return $venta;
        });
    }

    private function validarStockPrevio(array $itemsAProcesar): void
    {
        foreach ($itemsAProcesar as $item) {
            $producto = Producto::findOrFail($item['producto_id']);
            if ($producto->unidadMedida !== 'Servicio') {
                 if (! $producto->tieneStock($item['cantidad'])) {
                    throw new SinStockException($producto->nombre, $item['cantidad'], $producto->stock_total);
                }
            }
        }
    }

    private function calcularTotalesVenta(array $datosValidados, Cliente $cliente): array
    {
        $itemsAProcesar = $datosValidados['items'];
        $descuentosGlobalesInput = $datosValidados['descuentos_globales'] ?? [];

        $totalVentaBruto = 0;
        $totalDescuentosItems = 0;
        $detallesParaGuardar = [];
        $descuentosItemParaGuardar = [];

        foreach ($itemsAProcesar as $item) {
            $producto = Producto::findOrFail($item['producto_id']);
            
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

            // IMPORTANTE: Usamos 'producto_id' para que coincida con la BD
            $detalleVenta = new DetalleVenta([
                'producto_id' => $producto->id, 
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

    private function validarEstadoCredito(Cliente $cliente, float $montoDeEstaVenta): void
    {
        if (! $cliente->cuentaCorriente) {
            throw new LimiteCreditoExcedidoException("El cliente {$cliente->nombre_completo} no tiene una cuenta corriente habilitada.", 0, 0);
        }
        $cuentaCorriente = $cliente->cuentaCorriente;
        $estadoNombre = $cuentaCorriente->estadoCuentaCorriente?->nombreEstado ?? 'Desconocido';

        if ($estadoNombre === 'Bloqueada') {
            throw new LimiteCreditoExcedidoException("La cuenta corriente de {$cliente->nombre_completo} está BLOQUEADA.", 0, 0);
        }
        if ($estadoNombre === 'Pendiente de Aprobación') {
            throw new LimiteCreditoExcedidoException("La cuenta corriente está PENDIENTE DE APROBACIÓN.", 0, 0);
        }

        $saldoActual = $cuentaCorriente->saldo; 
        $limiteCredito = $cuentaCorriente->getLimiteCreditoAplicable(); 

        if (($saldoActual + $montoDeEstaVenta) > $limiteCredito) {
            $disponible = $limiteCredito - $saldoActual;
            throw new LimiteCreditoExcedidoException(
                "Excede límite de crédito. Límite: $$limiteCredito. Disp: $$disponible", $limiteCredito, $saldoActual
            );
        }
    }

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
        // Si no encuentra precio específico, intenta fallback (opcional)
        // return 100.00; 
        
        throw new \Exception("No se encontró un precio vigente para el producto '{$producto->nombre}'.");
    }
}