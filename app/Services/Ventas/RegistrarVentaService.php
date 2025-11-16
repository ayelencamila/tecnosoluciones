<?php

namespace App\Services\Ventas;

// Usamos todos los modelos y excepciones que creamos/definimos
use App\Events\VentaRegistrada;
use App\Exceptions\Ventas\LimiteCreditoExcedidoException;
use App\Exceptions\Ventas\SinStockException;
use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\DetalleVenta;
use App\Models\PrecioProducto;
use App\Models\Producto;
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

        // 2.1 CALCULAR FECHA DE VENCIMIENTO (si aplica)
        $fechaVencimiento = null;
        if ($metodoPago === 'cuenta_corriente') {
            $fechaVencimiento = Carbon::now()->addDays($cliente->cuentaCorriente->getDiasGraciaAplicables());
        }

        // 3. VALIDACIÓN DE LÓGICA DE NEGOCIO (Pre-Transacción)
        if ($metodoPago === 'cuenta_corriente') {
            $this->validarEstadoCredito($cliente, $calculos['totalFinalVenta']);
        }

        // 4. INICIAR TRANSACCIÓN ATÓMICA
        return DB::transaction(function () use ($datosValidados, $cliente, $vendedorUserID, $metodoPago, $calculos) {

            // 5. (CREATOR - GRASP) CREAR LA VENTA (Maestro)
            $venta = Venta::create([
                'clienteID' => $cliente->clienteID,
                'user_id' => $vendedorUserID,
                'numero_comprobante' => $datosValidados['numero_comprobante'] ?? 'V-'.time(),
                'fecha_venta' => Carbon::now(),
                'fecha_vencimiento' => $fechaVencimiento,
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

            foreach ($calculos['descuentosItemParaGuardar'] as $infoDescuento) {
                $detalleGuardado = $infoDescuento['detalle'];
                if (! empty($infoDescuento['mapaPivote'])) {
                    $detalleGuardado->descuentos()->attach($infoDescuento['mapaPivote']);
                }
            }

            // 7. LÓGICA CUENTA CORRIENTE (Post-Creación Venta)
            if ($calculos['totalFinalVenta'] > 0 && $metodoPago === 'cuenta_corriente') {
                $cuentaCorriente = $cliente->cuentaCorriente;
                if (! $cuentaCorriente) {
                    throw new \Exception('El cliente no tiene una cuenta corriente asignada para ventas a crédito.');
                }

                $cuentaCorriente->registrarDebito(
                    $calculos['totalFinalVenta'],
                    'Venta N° '.$venta->numero_comprobante,
                    $fechaVencimiento, // Usamos la fecha calculada previamente
                    $venta->venta_id, 
                    'ventas',
                    $vendedorUserID
                );
                Log::info("Débito registrado en CC para Venta ID: {$venta->venta_id}");
            }

            // 8. DISPARAR EVENTO
            event(new VentaRegistrada($venta, $metodoPago, $vendedorUserID));

            Log::info("Venta registrada con éxito: ID {$venta->venta_id}");

            return $venta;
        });
    }

    /**
     * Calcula todos los totales de la venta y valida stock/precios.
     * (Este método no necesita cambios, tu lógica es correcta)
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
            $this->validarStock($producto, $item['cantidad']);
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
        ];
    }

    /**
     * (CU-05 Excepción 3a)
     */
    private function validarStock(Producto $producto, int $cantidadPedida): void
    {
        if (! $producto->tieneStock($cantidadPedida)) {
            throw new SinStockException($producto->nombre, $cantidadPedida, $producto->stockActual);
        }
    }

    /**
     * Valida el estado actual Y el límite de crédito del cliente.
     * Incluye validación de saldo vencido (CU-09).
     */
    private function validarEstadoCredito(Cliente $cliente, float $montoDeEstaVenta): void
    {
        if (! $cliente->cuentaCorriente) {
            throw new LimiteCreditoExcedidoException("El cliente {$cliente->nombre_completo} no tiene una cuenta corriente habilitada.", 0, 0);
        }
        $cuentaCorriente = $cliente->cuentaCorriente;
        $estadoNombre = $cuentaCorriente->estadoCuentaCorriente?->nombreEstado ?? 'Desconocido';

        // 1. VALIDAR ESTADO DE LA CUENTA
        if ($estadoNombre === 'Bloqueada') {
            throw new LimiteCreditoExcedidoException("La cuenta corriente de {$cliente->nombre_completo} se encuentra BLOQUEADA. Solo se permiten ventas al contado.", 0, 0);
        }
        if ($estadoNombre === 'Pendiente de Aprobación') {
            throw new LimiteCreditoExcedidoException("La cuenta corriente de {$cliente->nombre_completo} está PENDIENTE DE APROBACIÓN. No se permiten ventas a crédito hasta su revisión.", 0, 0);
        }

        // 2. VALIDAR SALDO VENCIDO (CU-09 Excepción 4a - Política de negocio)
        $saldoVencido = $cuentaCorriente->calcularSaldoVencido();
        if ($saldoVencido > 0) {
            throw new LimiteCreditoExcedidoException(
                "El cliente {$cliente->nombre_completo} tiene deuda vencida de $$saldoVencido. Debe regularizar su situación antes de realizar nuevas compras a crédito.",
                0,
                $saldoVencido
            );
        }

        // 3. VALIDAR LÍMITE DE CRÉDITO
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
