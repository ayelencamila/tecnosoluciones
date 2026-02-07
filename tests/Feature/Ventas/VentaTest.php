<?php

namespace Tests\Feature\Ventas;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
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
use App\Models\EstadoVenta;
use App\Models\MovimientoStock;
use Illuminate\Support\Facades\DB;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Cliente $cliente;
    protected Producto $producto;
    protected Producto $servicio;
    protected MedioPago $medioPagoEfectivo;
    protected TipoCliente $tipoMinorista;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake();

        // Rol + usuario
        $rol = Rol::create([
            'nombre' => 'administrador',
            'descripcion' => 'Admin',
            'permisos' => [],
            'activo' => true,
        ]);
        $this->admin = User::factory()->create(['rol_id' => $rol->rol_id]);

        // Paramétricas
        $categoria = CategoriaProducto::create(['nombre' => 'Electrónica', 'activo' => true]);
        $estadoProd = EstadoProducto::create(['nombre' => 'Activo', 'descripcion' => 'OK']);
        $unidad = UnidadMedida::create(['nombre' => 'Unidad', 'abreviatura' => 'u', 'activo' => true]);
        $deposito = Deposito::create(['nombre' => 'Depósito Principal', 'activo' => true, 'esPrincipal' => true]);
        $this->tipoMinorista = TipoCliente::create(['nombreTipo' => 'Minorista', 'descripcion' => 'Público general', 'activo' => true]);

        // Estados de venta — use DB::table to set explicit PKs on auto-increment column
        DB::table('estados_venta')->insert([
            ['estadoVentaID' => 1, 'nombreEstado' => 'Pendiente', 'created_at' => now(), 'updated_at' => now()],
            ['estadoVentaID' => 2, 'nombreEstado' => 'Completada', 'created_at' => now(), 'updated_at' => now()],
            ['estadoVentaID' => 3, 'nombreEstado' => 'Anulada', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Tipos de movimiento de stock
        TipoMovimientoStock::create(['nombre' => 'Salida (Venta)', 'signo' => -1, 'activo' => true]);
        TipoMovimientoStock::create(['nombre' => 'Devolución (Entrada)', 'signo' => 1, 'activo' => true]);

        // Medios de pago
        $this->medioPagoEfectivo = MedioPago::create(['nombre' => 'Efectivo', 'recargo_porcentaje' => 0, 'activo' => true]);

        // Estado cliente
        $estadoCliente = EstadoCliente::create(['nombreEstado' => 'Activo', 'descripcion' => 'Cliente activo']);

        // Cliente minorista
        $this->cliente = Cliente::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'DNI' => '33444555',
            'mail' => 'juan@test.com',
            'tipoClienteID' => $this->tipoMinorista->tipoClienteID,
            'estadoClienteID' => $estadoCliente->estadoClienteID,
        ]);

        // Producto con stock
        $this->producto = Producto::create([
            'codigo' => 'ELEC-001',
            'nombre' => 'Auriculares Bluetooth',
            'categoriaProductoID' => $categoria->id,
            'estadoProductoID' => $estadoProd->id,
            'unidad_medida_id' => $unidad->id,
            'precio_costo' => 15000,
        ]);
        Stock::create([
            'productoID' => $this->producto->id,
            'deposito_id' => $deposito->deposito_id,
            'cantidad_disponible' => 50,
            'stock_minimo' => 5,
        ]);
        PrecioProducto::create([
            'productoID' => $this->producto->id,
            'tipoClienteID' => $this->tipoMinorista->tipoClienteID,
            'precio' => 25000,
            'fechaDesde' => now()->subMonth(),
            'fechaHasta' => null,
        ]);

        // Servicio (sin stock)
        $this->servicio = Producto::create([
            'codigo' => 'SERV-001',
            'nombre' => 'Instalación',
            'categoriaProductoID' => $categoria->id,
            'estadoProductoID' => $estadoProd->id,
            'unidad_medida_id' => $unidad->id,
            'es_servicio' => true,
        ]);
        PrecioProducto::create([
            'productoID' => $this->servicio->id,
            'tipoClienteID' => $this->tipoMinorista->tipoClienteID,
            'precio' => 10000,
            'fechaDesde' => now()->subMonth(),
            'fechaHasta' => null,
        ]);
    }

    /** @test */
    public function puede_registrar_venta_con_producto_fisico()
    {
        $precioProducto = PrecioProducto::where('productoID', $this->producto->id)->first();

        $response = $this->actingAs($this->admin)->post(route('ventas.store'), [
            'clienteID' => $this->cliente->clienteID,
            'medio_pago_id' => $this->medioPagoEfectivo->medioPagoID,
            'items' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 3,
                    'precio_producto_id' => $precioProducto->id,
                ],
            ],
        ]);

        $response->assertRedirect(route('ventas.index'));
        $response->assertSessionHas('success');

        // Verificar venta creada
        $venta = Venta::first();
        $this->assertNotNull($venta);
        $this->assertEquals($this->cliente->clienteID, $venta->clienteID);
        $this->assertEquals(EstadoVenta::COMPLETADA, $venta->estado_venta_id);
        $this->assertEquals(75000, $venta->total); // 3 x 25000

        // Verificar stock decrementado
        $stock = Stock::where('productoID', $this->producto->id)->first();
        $this->assertEquals(47, $stock->cantidad_disponible); // 50 - 3
    }

    /** @test */
    public function puede_registrar_venta_de_servicio_sin_afectar_stock()
    {
        $precioServicio = PrecioProducto::where('productoID', $this->servicio->id)->first();

        $response = $this->actingAs($this->admin)->post(route('ventas.store'), [
            'clienteID' => $this->cliente->clienteID,
            'medio_pago_id' => $this->medioPagoEfectivo->medioPagoID,
            'items' => [
                [
                    'producto_id' => $this->servicio->id,
                    'cantidad' => 1,
                    'precio_producto_id' => $precioServicio->id,
                ],
            ],
        ]);

        $response->assertRedirect(route('ventas.index'));

        $venta = Venta::first();
        $this->assertEquals(10000, $venta->total);

        // No debe haber movimiento de stock para servicios
        $movimiento = MovimientoStock::where('productoID', $this->servicio->id)->first();
        $this->assertNull($movimiento);
    }

    /** @test */
    public function no_permite_venta_sin_stock_suficiente()
    {
        $precioProducto = PrecioProducto::where('productoID', $this->producto->id)->first();

        $response = $this->actingAs($this->admin)->post(route('ventas.store'), [
            'clienteID' => $this->cliente->clienteID,
            'medio_pago_id' => $this->medioPagoEfectivo->medioPagoID,
            'items' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 999,
                    'precio_producto_id' => $precioProducto->id,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertNull(Venta::first());
    }

    /** @test */
    public function no_permite_venta_sin_items()
    {
        $response = $this->actingAs($this->admin)->post(route('ventas.store'), [
            'clienteID' => $this->cliente->clienteID,
            'medio_pago_id' => $this->medioPagoEfectivo->medioPagoID,
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
    }

    /** @test */
    public function puede_anular_venta_y_revertir_stock()
    {
        $precioProducto = PrecioProducto::where('productoID', $this->producto->id)->first();

        // Primero crear la venta
        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'clienteID' => $this->cliente->clienteID,
            'medio_pago_id' => $this->medioPagoEfectivo->medioPagoID,
            'items' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 5,
                    'precio_producto_id' => $precioProducto->id,
                ],
            ],
        ]);

        $venta = Venta::first();
        $this->assertEquals(45, Stock::where('productoID', $this->producto->id)->value('cantidad_disponible'));

        // Anular la venta
        $response = $this->actingAs($this->admin)->post(route('ventas.anular', $venta->venta_id), [
            'motivo_anulacion' => 'El cliente se arrepintió de la compra y pidió devolución completa.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar estado anulada
        $venta->refresh();
        $this->assertEquals(EstadoVenta::ANULADA, $venta->estado_venta_id);
        $this->assertNotNull($venta->motivo_anulacion);

        // Verificar stock revertido
        $stock = Stock::where('productoID', $this->producto->id)->first();
        $this->assertEquals(50, $stock->cantidad_disponible); // Vuelve a 50
    }

    /** @test */
    public function no_permite_anular_venta_ya_anulada()
    {
        $precioProducto = PrecioProducto::where('productoID', $this->producto->id)->first();

        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'clienteID' => $this->cliente->clienteID,
            'medio_pago_id' => $this->medioPagoEfectivo->medioPagoID,
            'items' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 2,
                    'precio_producto_id' => $precioProducto->id,
                ],
            ],
        ]);

        $venta = Venta::first();

        // Primera anulación
        $this->actingAs($this->admin)->post(route('ventas.anular', $venta->venta_id), [
            'motivo_anulacion' => 'Primera anulación: error en la carga de productos del pedido.',
        ]);

        // Segunda anulación (debe fallar)
        $response = $this->actingAs($this->admin)->post(route('ventas.anular', $venta->venta_id), [
            'motivo_anulacion' => 'Segunda anulación: esto no debería funcionar correctamente.',
        ]);

        $response->assertSessionHasErrors('error');
    }

    /** @test */
    public function no_permite_anular_sin_motivo_suficiente()
    {
        $precioProducto = PrecioProducto::where('productoID', $this->producto->id)->first();

        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'clienteID' => $this->cliente->clienteID,
            'medio_pago_id' => $this->medioPagoEfectivo->medioPagoID,
            'items' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 1,
                    'precio_producto_id' => $precioProducto->id,
                ],
            ],
        ]);

        $venta = Venta::first();

        // Motivo corto (min:10)
        $response = $this->actingAs($this->admin)->post(route('ventas.anular', $venta->venta_id), [
            'motivo_anulacion' => 'Corto',
        ]);

        $response->assertSessionHasErrors('motivo_anulacion');
    }

    /** @test */
    public function venta_multiple_items_calcula_total_correcto()
    {
        $precioProducto = PrecioProducto::where('productoID', $this->producto->id)->first();
        $precioServicio = PrecioProducto::where('productoID', $this->servicio->id)->first();

        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'clienteID' => $this->cliente->clienteID,
            'medio_pago_id' => $this->medioPagoEfectivo->medioPagoID,
            'items' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 2,
                    'precio_producto_id' => $precioProducto->id,
                ],
                [
                    'producto_id' => $this->servicio->id,
                    'cantidad' => 1,
                    'precio_producto_id' => $precioServicio->id,
                ],
            ],
        ]);

        $venta = Venta::first();
        // 2 * 25000 + 1 * 10000 = 60000
        $this->assertEquals(60000, $venta->total);
        $this->assertCount(2, $venta->detalles);
    }
}
