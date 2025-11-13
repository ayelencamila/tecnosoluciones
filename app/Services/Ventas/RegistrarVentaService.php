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
use Illuminate\Support\Facades\DB; // Usamos esta para evitar duplicidad y por convención de Laravel
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
        // Obtenemos todos los totales y datos ANTES de abrir la transacción.
        $calculos = $this->calcularTotalesVenta($datosValidados, $cliente);

        // 3. VALIDACIÓN DE LÓGICA DE NEGOCIO (Pre-Transacción)
        if ($metodoPago === 'cuenta_corriente') {
            // (FIX 3) Ahora pasamos el total calculado a la validación.
            $this->validarEstadoCredito($cliente, $calculos['totalFinalVenta']);
        }

        // La validación de Stock ahora también ocurre en calcularTotalesVenta()
        // así que si llegamos aquí, el stock está OK.

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
                'forma_pago' => $metodoPago,
                'fecha_vencimiento' => ($metodoPago === 'cuenta_corriente')
                                            ? Carbon::now()->addDays($cliente->cuentaCorriente->getDiasGraciaAplicables())
                                            : null,
            ]);

            // 6. GUARDAR DETALLES Y PIVOTES
            $venta->detalles()->saveMany($calculos['detallesParaGuardar']);

            if (! empty($calculos['descuentosGlobalesParaGuardar'])) {
                $venta->descuentos()->attach($calculos['descuentosGlobalesParaGuardar']);
            }

            foreach ($calculos['descuentosItemParaGuardar'] as $infoDescuento) {
                // $infoDescuento['detalle'] fue guardado con saveMany,
                // así que ya tiene ID.
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
                    $venta->fecha_vencimiento, // Usamos la fecha de vencimiento de la venta
                    $venta->venta_id, // (Ajusta a tu PK 'venta_id')
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
     * Esta función NO escribe en la BD, solo calcula.
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

            // --- A. Validación de Stock ---
            $this->validarStock($producto, $item['cantidad']);

            // --- B. Obtención de Precio ---
            $precioUnitario = $this->obtenerPrecioParaCliente($producto, $cliente);

            // --- C. Cálculo de Subtotal (Item) ---
            $subtotalItem = (float) $item['cantidad'] * $precioUnitario;
            $totalDescuentoEsteItem = 0;
            $detallesTemporalesDescuento = [];

            // --- D. Cálculo de Descuentos (Por Item) ---
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

            // --- E. Acumular Totales ---
            $totalVentaBruto += $subtotalItem;
            $totalDescuentosItems += $totalDescuentoEsteItem;

            // --- F. Preparar Modelos (en memoria) ---
            $detalleVenta = new DetalleVenta([
                'productoID' => $producto->id, // (Asegúrate que esta sea tu FK a Producto)
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

        // 5. CÁLCULO DE DESCUENTOS GLOBALES
        $totalDescuentosGlobales = 0;
        $descuentosGlobalesParaGuardar = [];

        foreach ($descuentosGlobalesInput as $descInput) {
            $descuento = Descuento::findOrFail($descInput['descuento_id']);
            $montoAplicado = $descuento->calcularMonto($totalVentaBruto);
            $totalDescuentosGlobales += $montoAplicado;
            $descuentosGlobalesParaGuardar[$descuento->descuento_id] = ['monto_aplicado' => $montoAplicado];
        }

        // 6. CÁLCULO DE TOTALES FINALES
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
        // (Esto ya estaba bien)
        if (! $producto->tieneStock($cantidadPedida)) {
            throw new SinStockException($producto->nombre, $cantidadPedida, $producto->stockActual);
        }
    }

    /**
     * Valida el estado actual Y el límite de crédito del cliente.
     * (FIX 3)
     */
    private function validarEstadoCredito(Cliente $cliente, float $montoDeEstaVenta): void
    {
        if (! $cliente->cuentaCorriente) {
            throw new LimiteCreditoExcedidoException("El cliente {$cliente->nombre_completo} no tiene una cuenta corriente habilitada.", 0, 0);
        }

        $cuentaCorriente = $cliente->cuentaCorriente;
        $estadoNombre = $cuentaCorriente->estadoCuentaCorriente?->nombreEstado ?? 'Desconocido';

        // 1. Validar Estado
        if ($estadoNombre === 'Bloqueada') {
            throw new LimiteCreditoExcedidoException("La cuenta corriente de {$cliente->nombre_completo} se encuentra BLOQUEADA. Solo se permiten ventas al contado.", 0, 0);
        }
        if ($estadoNombre === 'Pendiente de Aprobación') {
            throw new LimiteCreditoExcedidoException("La cuenta corriente de {$cliente->nombre_completo} está PENDIENTE DE APROBACIÓN. No se permiten ventas a crédito hasta su revisión.", 0, 0);
        }

        // 2. (FIX 3) Validar Límite de Crédito (Monto)
        $saldoActual = $cuentaCorriente->saldo_actual; // Asumo que tienes una propiedad 'saldo_actual'
        $limiteCredito = $cuentaCorriente->getLimiteCreditoAplicable(); // Asumo que este método existe

        // Asegúrate que $limiteCredito sea un número
        if (! is_numeric($limiteCredito)) {
            // Log::error("El cliente {$cliente->clienteID} no tiene un límite de crédito numérico.");
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
        // (Tu lógica de precios - Asegúrate que la PK de Producto sea correcta)
        // Tu código anterior usaba 'productoID' en la consulta, pero 'producto->id' en
        // la sección de 'DetalleVenta'. Sé consistente.
        // Voy a usar $producto->id, asumiendo que es la PK.

        $precioProducto = PrecioProducto::where('productoID', $producto->id) // Usando $producto->id
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
