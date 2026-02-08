<?php

namespace Tests\Feature\Reportes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Rol;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\UnidadMedida;
use App\Models\Deposito;
use App\Models\Stock;
use App\Models\TipoMovimientoStock;
use App\Models\TipoCliente;
use App\Models\PrecioProducto;
use App\Models\MedioPago;
use App\Models\Cliente;
use App\Models\EstadoCliente;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\EstadoVenta;
use App\Models\OrdenCompra;
use App\Models\EstadoOrdenCompra;
use App\Models\RecepcionMercaderia;
use App\Models\DetalleRecepcion;
use App\Models\Proveedor;
use App\Models\Gasto;
use App\Models\CategoriaGasto;
use App\Models\Pago;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteMensualTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected int $mes;
    protected int $anio;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake();
        Cache::flush();

        $rol = Rol::create([
            'nombre' => 'administrador',
            'descripcion' => 'Admin',
            'permisos' => [],
            'activo' => true,
        ]);
        $this->admin = User::factory()->create(['rol_id' => $rol->rol_id]);

        // Paramétricas de estados — use DB::table for explicit PKs
        DB::table('estados_venta')->insert([
            ['estadoVentaID' => 1, 'nombreEstado' => 'Pendiente', 'created_at' => now(), 'updated_at' => now()],
            ['estadoVentaID' => 2, 'nombreEstado' => 'Completada', 'created_at' => now(), 'updated_at' => now()],
            ['estadoVentaID' => 3, 'nombreEstado' => 'Anulada', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->mes = Carbon::now()->month;
        $this->anio = Carbon::now()->year;
    }

    /** @test */
    public function puede_acceder_al_reporte_mensual()
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.mensual'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Reportes/Mensual/Index')
                ->has('planilla')
                ->has('periodo')
                ->has('filters')
        );
    }

    /** @test */
    public function reporte_incluye_ventas_no_anuladas_en_entradas()
    {
        // Crear una venta completada
        $venta = Venta::create([
            'clienteID' => $this->crearClienteMinorista()->clienteID,
            'user_id' => $this->admin->id,
            'estado_venta_id' => EstadoVenta::COMPLETADA,
            'medio_pago_id' => $this->crearMedioPago()->medioPagoID,
            'numero_comprobante' => 'V-TEST-001',
            'fecha_venta' => Carbon::now(),
            'subtotal' => 50000,
            'total_descuentos' => 0,
            'total' => 50000,
        ]);

        // Crear una venta anulada (no debería contar)
        Venta::create([
            'clienteID' => $venta->clienteID,
            'user_id' => $this->admin->id,
            'estado_venta_id' => EstadoVenta::ANULADA,
            'medio_pago_id' => $venta->medio_pago_id,
            'numero_comprobante' => 'V-TEST-002',
            'fecha_venta' => Carbon::now(),
            'subtotal' => 30000,
            'total_descuentos' => 0,
            'total' => 30000,
            'motivo_anulacion' => 'Test anulación',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reportes.mensual', [
            'mes' => $this->mes,
            'anio' => $this->anio,
        ]));

        $response->assertInertia(fn ($page) =>
            $page->where('planilla.entradas.0.total', fn ($val) => (float) $val == 50000)
                ->where('planilla.entradas.0.cantidad', 1)
        );
    }

    /** @test */
    public function reporte_incluye_compras_directas_en_salidas()
    {
        $proveedor = Proveedor::create([
            'razon_social' => 'Proveedor Test SA',
            'cuit' => '30998877665',
            'email' => 'prov@test.com',
            'activo' => true,
        ]);

        $producto = $this->crearProductoConStock();

        // Crear recepción directa (sin OC)
        $recepcion = RecepcionMercaderia::create([
            'numero_recepcion' => 'RD-TEST-001',
            'proveedor_id' => $proveedor->id,
            'orden_compra_id' => null, // ← directa
            'tipo' => 'total',
            'origen' => 'compra_directa',
            'fecha_recepcion' => Carbon::now(),
            'observaciones' => 'Reposición directa de test',
            'user_id' => $this->admin->id,
        ]);

        DetalleRecepcion::create([
            'recepcion_id' => $recepcion->id,
            'producto_id' => $producto->id,
            'cantidad_recibida' => 10,
            'precio_unitario' => 5000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reportes.mensual', [
            'mes' => $this->mes,
            'anio' => $this->anio,
        ]));

        // Verificar que las compras directas aparecen en salidas
        $response->assertInertia(fn ($page) =>
            $page->where('planilla.salidas.1.concepto', 'Compras Directas (Reposiciones)')
                ->where('planilla.salidas.1.total', fn ($val) => (float) $val == 50000)
                ->where('planilla.salidas.1.cantidad', 1)
        );
    }

    /** @test */
    public function reporte_incluye_gastos_operativos_y_perdidas_por_separado()
    {
        $catGasto = CategoriaGasto::create([
            'nombre' => 'Alquiler',
            'tipo' => 'gasto',
            'activo' => true,
        ]);

        $catPerdida = CategoriaGasto::create([
            'nombre' => 'Robo',
            'tipo' => 'perdida',
            'activo' => true,
        ]);

        Gasto::create([
            'categoria_gasto_id' => $catGasto->categoria_gasto_id,
            'fecha' => Carbon::now(),
            'descripcion' => 'Alquiler del local',
            'monto' => 200000,
            'usuario_id' => $this->admin->id,
            'anulado' => false,
        ]);

        Gasto::create([
            'categoria_gasto_id' => $catPerdida->categoria_gasto_id,
            'fecha' => Carbon::now(),
            'descripcion' => 'Mercadería perdida',
            'monto' => 15000,
            'usuario_id' => $this->admin->id,
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reportes.mensual', [
            'mes' => $this->mes,
            'anio' => $this->anio,
        ]));

        $response->assertInertia(fn ($page) =>
            $page->where('planilla.salidas.2.concepto', 'Gasto — Alquiler')
                ->where('planilla.salidas.2.total', fn ($val) => (float) $val == 200000)
                ->where('planilla.salidas.3.concepto', 'Pérdida — Robo')
                ->where('planilla.salidas.3.total', fn ($val) => (float) $val == 15000)
        );
    }

    /** @test */
    public function reporte_calcula_balance_correctamente()
    {
        $cliente = $this->crearClienteMinorista();
        $medioPago = $this->crearMedioPago();

        // Entrada: venta por $100.000
        Venta::create([
            'clienteID' => $cliente->clienteID,
            'user_id' => $this->admin->id,
            'estado_venta_id' => EstadoVenta::COMPLETADA,
            'medio_pago_id' => $medioPago->medioPagoID,
            'numero_comprobante' => 'V-BAL-001',
            'fecha_venta' => Carbon::now(),
            'subtotal' => 100000,
            'total_descuentos' => 0,
            'total' => 100000,
        ]);

        // Salida: gasto por $40.000
        $catGasto = CategoriaGasto::create([
            'nombre' => 'Servicios',
            'tipo' => 'gasto',
            'activo' => true,
        ]);
        Gasto::create([
            'categoria_gasto_id' => $catGasto->categoria_gasto_id,
            'fecha' => Carbon::now(),
            'descripcion' => 'Electricidad',
            'monto' => 40000,
            'usuario_id' => $this->admin->id,
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reportes.mensual', [
            'mes' => $this->mes,
            'anio' => $this->anio,
        ]));

        $response->assertInertia(fn ($page) =>
            $page->where('planilla.total_entradas', fn ($val) => (float) $val == 100000)
                ->where('planilla.total_salidas', fn ($val) => (float) $val == 40000)
                ->where('planilla.balance', fn ($val) => (float) $val == 60000)
        );
    }

    /** @test */
    public function reporte_filtra_por_mes_y_anio()
    {
        $cliente = $this->crearClienteMinorista();
        $medioPago = $this->crearMedioPago();

        // Venta del mes actual
        Venta::create([
            'clienteID' => $cliente->clienteID,
            'user_id' => $this->admin->id,
            'estado_venta_id' => EstadoVenta::COMPLETADA,
            'medio_pago_id' => $medioPago->medioPagoID,
            'numero_comprobante' => 'V-ACTUAL-001',
            'fecha_venta' => Carbon::now(),
            'subtotal' => 50000,
            'total_descuentos' => 0,
            'total' => 50000,
        ]);

        // Venta del mes anterior (no debería contar)
        Venta::create([
            'clienteID' => $cliente->clienteID,
            'user_id' => $this->admin->id,
            'estado_venta_id' => EstadoVenta::COMPLETADA,
            'medio_pago_id' => $medioPago->medioPagoID,
            'numero_comprobante' => 'V-ANTERIOR-001',
            'fecha_venta' => Carbon::now()->subMonth(),
            'subtotal' => 80000,
            'total_descuentos' => 0,
            'total' => 80000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reportes.mensual', [
            'mes' => $this->mes,
            'anio' => $this->anio,
        ]));

        $response->assertInertia(fn ($page) =>
            $page->where('planilla.entradas.0.total', fn ($val) => (float) $val == 50000)
                ->where('planilla.entradas.0.cantidad', 1)
        );
    }

    /** @test */
    public function reporte_incluye_cobranzas_dentro_de_entradas()
    {
        $cliente = $this->crearClienteMinorista();
        $medioPagoEfectivo = $this->crearMedioPago();
        $medioPagoTransf = MedioPago::firstOrCreate(
            ['nombre' => 'Transferencia'],
            ['recargo_porcentaje' => 0, 'activo' => true]
        );

        // Pago en efectivo
        Pago::create([
            'clienteID' => $cliente->clienteID,
            'user_id' => $this->admin->id,
            'monto' => 75000,
            'medioPagoID' => $medioPagoEfectivo->medioPagoID,
            'fecha_pago' => Carbon::now(),
            'numero_recibo' => 'REC-TEST-001',
            'anulado' => false,
        ]);

        // Pago con transferencia
        Pago::create([
            'clienteID' => $cliente->clienteID,
            'user_id' => $this->admin->id,
            'monto' => 25000,
            'medioPagoID' => $medioPagoTransf->medioPagoID,
            'fecha_pago' => Carbon::now(),
            'numero_recibo' => 'REC-TEST-002',
            'anulado' => false,
        ]);

        // Pago anulado (NO debe contar)
        Pago::create([
            'clienteID' => $cliente->clienteID,
            'user_id' => $this->admin->id,
            'monto' => 50000,
            'medioPagoID' => $medioPagoEfectivo->medioPagoID,
            'fecha_pago' => Carbon::now(),
            'numero_recibo' => 'REC-TEST-003',
            'anulado' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reportes.mensual', [
            'mes' => $this->mes,
            'anio' => $this->anio,
        ]));

        // Cobranzas ahora están dentro de entradas (índices 2 y 3, después de Ventas y Reparaciones)
        $response->assertInertia(fn ($page) =>
            $page->where('planilla.total_entradas', fn ($val) => (float) $val == 100000)
                ->has('planilla.entradas', 4) // Ventas + Reparaciones + 2 medios de pago
                ->where('planilla.entradas.2.concepto', fn ($val) => str_starts_with($val, 'Cobranza CC'))
                ->where('planilla.entradas.3.concepto', fn ($val) => str_starts_with($val, 'Cobranza CC'))
        );
    }

    /** @test */
    public function usuario_no_administrador_no_accede_al_reporte()
    {
        $rolVendedor = Rol::firstOrCreate(
            ['nombre' => 'vendedor'],
            ['descripcion' => 'Vendedor', 'permisos' => [], 'activo' => true]
        );
        $vendedor = User::factory()->create(['rol_id' => $rolVendedor->rol_id]);

        $response = $this->actingAs($vendedor)->get(route('reportes.mensual'));

        $response->assertStatus(403);
    }

    // ======================== HELPERS ========================

    private function crearClienteMinorista(): Cliente
    {
        $tipoCliente = TipoCliente::firstOrCreate(
            ['nombreTipo' => 'Minorista'],
            ['descripcion' => 'Público general', 'activo' => true]
        );
        $estadoCliente = EstadoCliente::firstOrCreate(
            ['nombreEstado' => 'Activo'],
            ['descripcion' => 'Cliente activo']
        );

        return Cliente::create([
            'nombre' => 'Test',
            'apellido' => 'Client',
            'DNI' => '12345678',
            'mail' => 'test' . rand(1, 99999) . '@test.com',
            'tipoClienteID' => $tipoCliente->tipoClienteID,
            'estadoClienteID' => $estadoCliente->estadoClienteID,
        ]);
    }

    private function crearMedioPago(): MedioPago
    {
        return MedioPago::firstOrCreate(
            ['nombre' => 'Efectivo'],
            ['recargo_porcentaje' => 0, 'activo' => true]
        );
    }

    private function crearProductoConStock(): Producto
    {
        $categoria = CategoriaProducto::firstOrCreate(['nombre' => 'General'], ['activo' => true]);
        $estado = EstadoProducto::firstOrCreate(['nombre' => 'Activo'], ['descripcion' => 'OK']);
        $unidad = UnidadMedida::firstOrCreate(['nombre' => 'Unidad'], ['abreviatura' => 'u', 'activo' => true]);
        $deposito = Deposito::firstOrCreate(['nombre' => 'Depósito Principal'], ['activo' => true, 'esPrincipal' => true]);

        $producto = Producto::create([
            'codigo' => 'TEST-' . rand(1000, 9999),
            'nombre' => 'Producto Test',
            'categoriaProductoID' => $categoria->id,
            'estadoProductoID' => $estado->id,
            'unidad_medida_id' => $unidad->id,
        ]);

        Stock::create([
            'productoID' => $producto->id,
            'deposito_id' => $deposito->deposito_id,
            'cantidad_disponible' => 100,
            'stock_minimo' => 5,
        ]);

        return $producto;
    }
}
