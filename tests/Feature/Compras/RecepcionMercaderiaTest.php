<?php

namespace Tests\Feature\Compras;

use App\Models\CategoriaProducto;
use App\Models\Deposito;
use App\Models\DetalleOrdenCompra;
use App\Models\EstadoOrdenCompra;
use App\Models\EstadoProducto;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Stock;
use App\Models\TipoMovimientoStock;
use App\Models\User;
use App\Services\Compras\RecepcionarMercaderiaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Feature para CU-23: Recepción de Mercadería
 *
 * Verifica:
 * - Recepción total de mercadería
 * - Recepción parcial de mercadería
 * - Actualización automática de stock
 * - Cambio de estado de OC
 * - Registro en auditoría
 */
class RecepcionMercaderiaTest extends TestCase
{
    use RefreshDatabase;

    protected RecepcionarMercaderiaService $service;
    protected User $usuario;
    protected Proveedor $proveedor;
    protected Producto $producto;
    protected OrdenCompra $ordenCompra;
    protected EstadoOrdenCompra $estadoEnviada;
    protected EstadoOrdenCompra $estadoRecibidaParcial;
    protected EstadoOrdenCompra $estadoRecibidaTotal;
    protected Deposito $deposito;
    protected DetalleOrdenCompra $detalleOC;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear estados de OC (migración los seedea, usar firstOrCreate)
        $this->estadoEnviada = EstadoOrdenCompra::firstOrCreate(
            ['nombre' => 'Enviada'],
            ['descripcion' => 'OC enviada al proveedor']
        );
        $this->estadoRecibidaParcial = EstadoOrdenCompra::firstOrCreate(
            ['nombre' => 'Recibida Parcial'],
            ['descripcion' => 'OC recibida parcialmente']
        );
        $this->estadoRecibidaTotal = EstadoOrdenCompra::firstOrCreate(
            ['nombre' => 'Recibida Total'],
            ['descripcion' => 'OC recibida completamente']
        );

        // Crear depósito con ID 1 (el service hardcodea deposito_id=1)
        \Illuminate\Support\Facades\DB::table('depositos')->insert([
            'deposito_id' => 1,
            'nombre' => 'Depósito Principal',
            'descripcion' => 'Depósito principal de la empresa',
            'activo' => true,
            'esPrincipal' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->deposito = Deposito::find(1);

        // Crear rol y usuario
        $rol = Rol::firstOrCreate(
            ['nombre' => 'administrador'],
            ['descripcion' => 'Administrador', 'activo' => true]
        );
        $this->usuario = User::factory()->create(['rol_id' => $rol->rol_id]);

        // Crear proveedor
        $this->proveedor = Proveedor::create([
            'razon_social' => 'Proveedor Test S.A.',
            'cuit' => '30123456789',
            'email' => 'proveedor@test.com',
            'telefono' => '1112345678',
            'activo' => true,
        ]);

        // Crear categoría y producto
        $categoria = CategoriaProducto::create([
            'nombre' => 'Electrónica',
            'descripcion' => 'Productos electrónicos',
        ]);
        $this->producto = Producto::create([
            'codigo' => 'PROD001',
            'nombre' => 'Producto Test',
            'categoriaProductoID' => $categoria->id,
            'unidad_medida_id' => \App\Models\UnidadMedida::firstOrCreate(
                ['nombre' => 'Unidad'],
                ['abreviatura' => 'u', 'activo' => true]
            )->id,
            'estadoProductoID' => EstadoProducto::create([
                'nombre' => 'Activo',
                'descripcion' => 'Producto activo',
            ])->id,
        ]);

        // Crear stock inicial
        Stock::create([
            'productoID' => $this->producto->id,
            'deposito_id' => $this->deposito->deposito_id,
            'cantidad_disponible' => 5,
            'stock_minimo' => 10,
        ]);

        // Crear OC con detalle
        $this->ordenCompra = OrdenCompra::create([
            'numero_oc' => 'OC-TEST-001',
            'proveedor_id' => $this->proveedor->id,
            'estado_id' => $this->estadoEnviada->id,
            'user_id' => $this->usuario->id,
            'fecha_emision' => now(),
            'total_final' => 50000,
        ]);

        $this->detalleOC = DetalleOrdenCompra::create([
            'orden_compra_id' => $this->ordenCompra->id,
            'producto_id' => $this->producto->id,
            'cantidad_pedida' => 100,
            'cantidad_recibida' => 0,
            'precio_unitario' => 500,
        ]);

        // Crear tipo de movimiento de stock (necesario para el service)
        TipoMovimientoStock::firstOrCreate(
            ['nombre' => 'Entrada (Compra)'],
            ['signo' => 1, 'activo' => true]
        );

        $this->service = app(RecepcionarMercaderiaService::class);
    }

    /**
     * Test CU-23: Recepción total de mercadería
     */
    public function test_recepcion_total_actualiza_stock_y_estado_oc()
    {
        // ARRANGE
        $stockInicial = Stock::where('productoID', $this->producto->id)->first();
        $cantidadInicial = $stockInicial->cantidad_disponible;

        $items = [
            [
                'detalle_orden_id' => $this->detalleOC->id,
                'cantidad_recibida' => 100,
                'observacion_item' => 'Recepción completa',
            ],
        ];

        // ACT
        $resultado = $this->service->ejecutar(
            $this->ordenCompra->id,
            $items,
            'Recepción completa',
            $this->usuario->id
        );

        // ASSERT
        $this->assertNotNull($resultado);

        // Stock actualizado
        $stockInicial->refresh();
        $this->assertEquals($cantidadInicial + 100, $stockInicial->cantidad_disponible);

        // OC en estado Recibida Total
        $this->ordenCompra->refresh();
        $this->assertEquals($this->estadoRecibidaTotal->id, $this->ordenCompra->estado_id);
    }

    /**
     * Test CU-23: Recepción parcial de mercadería
     */
    public function test_recepcion_parcial_mantiene_oc_pendiente()
    {
        // ARRANGE
        $items = [
            [
                'detalle_orden_id' => $this->detalleOC->id,
                'cantidad_recibida' => 50,
                'observacion_item' => 'Recepción parcial',
            ],
        ];

        // ACT
        $resultado = $this->service->ejecutar(
            $this->ordenCompra->id,
            $items,
            'Recepción parcial',
            $this->usuario->id
        );

        // ASSERT
        // OC en estado Recibida Parcial
        $this->ordenCompra->refresh();
        $this->assertEquals($this->estadoRecibidaParcial->id, $this->ordenCompra->estado_id);

        // Detalle actualizado con cantidad parcial
        $detalle = DetalleOrdenCompra::where('orden_compra_id', $this->ordenCompra->id)->first();
        $this->assertEquals(50, $detalle->cantidad_recibida);
    }

    /**
     * Test CU-23: Múltiples recepciones parciales hasta completar
     */
    public function test_multiples_recepciones_parciales()
    {
        // ACT - Primera recepción parcial
        $items1 = [
            ['detalle_orden_id' => $this->detalleOC->id, 'cantidad_recibida' => 30],
        ];
        $this->service->ejecutar($this->ordenCompra->id, $items1, 'Parcial 1', $this->usuario->id);

        // ASSERT
        $this->ordenCompra->refresh();
        $this->assertEquals($this->estadoRecibidaParcial->id, $this->ordenCompra->estado_id);

        // ACT - Segunda recepción parcial
        $items2 = [
            ['detalle_orden_id' => $this->detalleOC->id, 'cantidad_recibida' => 40],
        ];
        $this->service->ejecutar($this->ordenCompra->id, $items2, 'Parcial 2', $this->usuario->id);

        // ASSERT
        $this->ordenCompra->refresh();
        $this->assertEquals($this->estadoRecibidaParcial->id, $this->ordenCompra->estado_id);

        // ACT - Tercera recepción que completa
        $items3 = [
            ['detalle_orden_id' => $this->detalleOC->id, 'cantidad_recibida' => 30],
        ];
        $this->service->ejecutar($this->ordenCompra->id, $items3, 'Parcial 3', $this->usuario->id);

        // ASSERT - Ahora debe ser Total
        $this->ordenCompra->refresh();
        $this->assertEquals($this->estadoRecibidaTotal->id, $this->ordenCompra->estado_id);

        // Stock debe haberse incrementado en 100 total
        $stock = Stock::where('productoID', $this->producto->id)->first();
        $this->assertEquals(105, $stock->cantidad_disponible); // 5 inicial + 100
    }

    /**
     * Test CU-23: Validación de cantidad excede pedido
     */
    public function test_recepcion_no_puede_exceder_cantidad_pedida()
    {
        // ARRANGE
        $items = [
            [
                'detalle_orden_id' => $this->detalleOC->id,
                'cantidad_recibida' => 150,
            ],
        ];

        // ACT & ASSERT
        $this->expectException(\Exception::class);
        $this->service->ejecutar($this->ordenCompra->id, $items, 'Exceso', $this->usuario->id);
    }

    /**
     * Test CU-23: Registro de auditoría en recepción
     */
    public function test_recepcion_registra_auditoria()
    {
        // ARRANGE
        $items = [
            ['detalle_orden_id' => $this->detalleOC->id, 'cantidad_recibida' => 100],
        ];

        // ACT
        $resultado = $this->service->ejecutar($this->ordenCompra->id, $items, 'Auditoría test', $this->usuario->id);

        // ASSERT - Verificar que la recepción se creó correctamente
        $this->assertNotNull($resultado);
        $this->assertDatabaseHas('recepciones_mercaderia', [
            'orden_compra_id' => $this->ordenCompra->id,
            'user_id' => $this->usuario->id,
        ]);
    }

    /**
     * Test CU-23: Controlador muestra OC pendientes de recepción
     */
    public function test_index_muestra_oc_pendientes_recepcion()
    {
        // ARRANGE
        $this->actingAs($this->usuario);

        // ACT
        $response = $this->get(route('recepciones.index'));

        // ASSERT
        $response->assertStatus(200);
    }
}
