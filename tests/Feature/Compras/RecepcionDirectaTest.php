<?php

namespace Tests\Feature\Compras;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Models\User;
use App\Models\Rol;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\UnidadMedida;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Deposito;
use App\Models\TipoMovimientoStock;
use App\Models\RecepcionMercaderia;
use App\Models\DetalleRecepcion;
use App\Models\MovimientoStock;

class RecepcionDirectaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Proveedor $proveedor;
    protected Producto $producto1;
    protected Producto $producto2;
    protected Deposito $deposito;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $rol = Rol::create([
            'nombre' => 'administrador',
            'descripcion' => 'Admin',
            'permisos' => [],
            'activo' => true,
        ]);
        $this->admin = User::factory()->create(['rol_id' => $rol->rol_id]);

        // Paramétricas
        $categoria = CategoriaProducto::create(['nombre' => 'Repuestos', 'activo' => true]);
        $estado = EstadoProducto::create(['nombre' => 'Activo', 'descripcion' => 'Disponible']);
        $unidad = UnidadMedida::create(['nombre' => 'Unidad', 'abreviatura' => 'u', 'activo' => true]);
        $this->deposito = Deposito::create([
            'nombre' => 'Depósito Principal', 'activo' => true, 'esPrincipal' => true,
        ]);
        TipoMovimientoStock::create(['nombre' => 'Entrada (Compra)', 'signo' => 1, 'activo' => true]);

        $this->proveedor = Proveedor::create([
            'razon_social' => 'Proveedor Test SA',
            'cuit' => '30123456789',
            'email' => 'test@proveedor.com',
            'activo' => true,
        ]);

        // Productos con stock inicial
        $this->producto1 = Producto::create([
            'codigo' => 'PROD-001', 'nombre' => 'Producto 1',
            'categoriaProductoID' => $categoria->id, 'estadoProductoID' => $estado->id,
            'unidad_medida_id' => $unidad->id, 'precio_costo' => 5000,
        ]);
        Stock::create([
            'productoID' => $this->producto1->id,
            'deposito_id' => $this->deposito->deposito_id,
            'cantidad_disponible' => 10, 'stock_minimo' => 5,
        ]);

        $this->producto2 = Producto::create([
            'codigo' => 'PROD-002', 'nombre' => 'Producto 2',
            'categoriaProductoID' => $categoria->id, 'estadoProductoID' => $estado->id,
            'unidad_medida_id' => $unidad->id, 'precio_costo' => null,
        ]);
        Stock::create([
            'productoID' => $this->producto2->id,
            'deposito_id' => $this->deposito->deposito_id,
            'cantidad_disponible' => 0, 'stock_minimo' => 3,
        ]);
    }

    /** @test */
    public function puede_crear_recepcion_directa_tipo_total()
    {
        $response = $this->actingAs($this->admin)->post(route('recepciones.store-directo'), [
            'proveedor_id' => $this->proveedor->id,
            'tipo' => 'total',
            'observaciones' => 'Reposición de stock mensual',
            'productos' => [
                [
                    'producto_id' => $this->producto1->id,
                    'cantidad' => 5,
                    'precio_unitario' => 6000,
                ],
            ],
        ]);

        $response->assertRedirect();

        // Verificar recepción creada
        $recepcion = RecepcionMercaderia::whereNull('orden_compra_id')->first();
        $this->assertNotNull($recepcion);
        $this->assertEquals('total', $recepcion->tipo);
        $this->assertEquals($this->proveedor->id, $recepcion->proveedor_id);
        $this->assertStringContainsString('Reposición', $recepcion->observaciones);

        // Verificar detalle
        $detalle = $recepcion->detalles->first();
        $this->assertEquals($this->producto1->id, $detalle->producto_id);
        $this->assertEquals(5, $detalle->cantidad_recibida);
        $this->assertEquals(6000, $detalle->precio_unitario);
    }

    /** @test */
    public function recepcion_directa_actualiza_stock()
    {
        $this->actingAs($this->admin)->post(route('recepciones.store-directo'), [
            'proveedor_id' => $this->proveedor->id,
            'tipo' => 'total',
            'observaciones' => 'Reposición test stock',
            'productos' => [
                [
                    'producto_id' => $this->producto1->id,
                    'cantidad' => 15,
                    'precio_unitario' => 5000,
                ],
            ],
        ]);

        // Stock original era 10, recibimos 15 → debe ser 25
        $stock = Stock::where('productoID', $this->producto1->id)->first();
        $this->assertEquals(25, $stock->cantidad_disponible);

        // Verificar movimiento de stock registrado
        $movimiento = MovimientoStock::where('productoID', $this->producto1->id)
            ->latest('id')->first();
        $this->assertNotNull($movimiento);
        $this->assertEquals(15, $movimiento->cantidad);
    }

    /** @test */
    public function recepcion_directa_actualiza_costo_ponderado()
    {
        // Producto1: 10 unidades a $5.000
        $this->actingAs($this->admin)->post(route('recepciones.store-directo'), [
            'proveedor_id' => $this->proveedor->id,
            'tipo' => 'total',
            'observaciones' => 'Reposición a nuevo precio',
            'productos' => [
                [
                    'producto_id' => $this->producto1->id,
                    'cantidad' => 10,
                    'precio_unitario' => 7000,
                ],
            ],
        ]);

        // Promedio ponderado: (10*5000 + 10*7000) / 20 = 6000
        $this->producto1->refresh();
        $this->assertEquals(6000, $this->producto1->precio_costo);
    }

    /** @test */
    public function recepcion_directa_con_producto_sin_costo_previo()
    {
        // Producto2: 0 unidades, sin precio_costo
        $this->actingAs($this->admin)->post(route('recepciones.store-directo'), [
            'proveedor_id' => $this->proveedor->id,
            'tipo' => 'total',
            'observaciones' => 'Primera compra de este producto',
            'productos' => [
                [
                    'producto_id' => $this->producto2->id,
                    'cantidad' => 20,
                    'precio_unitario' => 3500,
                ],
            ],
        ]);

        // Sin costo previo → toma el precio directo
        $this->producto2->refresh();
        $this->assertEquals(3500, $this->producto2->precio_costo);

        // Stock debe ser 20
        $stock = Stock::where('productoID', $this->producto2->id)->first();
        $this->assertEquals(20, $stock->cantidad_disponible);
    }

    /** @test */
    public function recepcion_directa_tipo_parcial()
    {
        $response = $this->actingAs($this->admin)->post(route('recepciones.store-directo'), [
            'proveedor_id' => $this->proveedor->id,
            'tipo' => 'parcial',
            'observaciones' => 'Entrega parcial del pedido',
            'productos' => [
                [
                    'producto_id' => $this->producto1->id,
                    'cantidad' => 3,
                    'precio_unitario' => 5500,
                ],
            ],
        ]);

        $response->assertRedirect();

        $recepcion = RecepcionMercaderia::whereNull('orden_compra_id')->first();
        $this->assertEquals('parcial', $recepcion->tipo);
    }

    /** @test */
    public function recepcion_directa_multiples_productos()
    {
        $this->actingAs($this->admin)->post(route('recepciones.store-directo'), [
            'proveedor_id' => $this->proveedor->id,
            'tipo' => 'total',
            'observaciones' => 'Compra de varios productos',
            'productos' => [
                [
                    'producto_id' => $this->producto1->id,
                    'cantidad' => 5,
                    'precio_unitario' => 5000,
                ],
                [
                    'producto_id' => $this->producto2->id,
                    'cantidad' => 10,
                    'precio_unitario' => 3000,
                ],
            ],
        ]);

        $recepcion = RecepcionMercaderia::whereNull('orden_compra_id')->first();
        $this->assertCount(2, $recepcion->detalles);

        // Verificar stocks actualizados
        $stock1 = Stock::where('productoID', $this->producto1->id)->first();
        $this->assertEquals(15, $stock1->cantidad_disponible); // 10 + 5

        $stock2 = Stock::where('productoID', $this->producto2->id)->first();
        $this->assertEquals(10, $stock2->cantidad_disponible); // 0 + 10
    }

    /** @test */
    public function no_permite_recepcion_sin_observaciones()
    {
        $response = $this->actingAs($this->admin)->post(route('recepciones.store-directo'), [
            'proveedor_id' => $this->proveedor->id,
            'tipo' => 'total',
            'observaciones' => '',
            'productos' => [
                [
                    'producto_id' => $this->producto1->id,
                    'cantidad' => 5,
                    'precio_unitario' => 5000,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('observaciones');
    }

    /** @test */
    public function no_permite_tipo_invalido()
    {
        $response = $this->actingAs($this->admin)->post(route('recepciones.store-directo'), [
            'proveedor_id' => $this->proveedor->id,
            'tipo' => 'inexistente',
            'observaciones' => 'Test tipo inválido',
            'productos' => [
                [
                    'producto_id' => $this->producto1->id,
                    'cantidad' => 5,
                    'precio_unitario' => 5000,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('tipo');
    }
}
