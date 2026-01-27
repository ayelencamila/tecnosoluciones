<?php

namespace Tests\Feature\Compras;

use App\Models\DetalleOrdenCompra;
use App\Models\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\CategoriaProducto;
use App\Models\RecepcionMercaderia;
use App\Models\Stock;
use App\Models\MovimientoStock;
use App\Models\TipoMovimientoStock;
use App\Models\Deposito;
use App\Models\User;
use App\Models\Rol;
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
    protected TipoMovimientoStock $tipoEntrada;
    protected Deposito $deposito;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear estados de OC
        $this->estadoEnviada = EstadoOrdenCompra::create([
            'nombre' => 'Enviada',
            'descripcion' => 'OC enviada al proveedor',
            'color' => '#3B82F6',
        ]);
        $this->estadoRecibidaParcial = EstadoOrdenCompra::create([
            'nombre' => 'Recibida Parcial',
            'descripcion' => 'OC recibida parcialmente',
            'color' => '#F59E0B',
        ]);
        $this->estadoRecibidaTotal = EstadoOrdenCompra::create([
            'nombre' => 'Recibida Total',
            'descripcion' => 'OC recibida completamente',
            'color' => '#10B981',
        ]);

        // Crear tipo de movimiento de stock
        $this->tipoEntrada = TipoMovimientoStock::create([
            'codigo' => 'ENTRADA_COMPRA',
            'nombre' => 'Entrada por Compra',
            'afecta_stock' => 'incrementa',
        ]);

        // Crear depósito
        $this->deposito = Deposito::create([
            'nombre' => 'Depósito Principal',
            'descripcion' => 'Depósito principal de la empresa',
            'activo' => true,
        ]);

        // Crear rol y usuario
        $rol = Rol::create(['nombre' => 'Administrador']);
        $this->usuario = User::factory()->create(['rolID' => $rol->rolID]);

        // Crear proveedor
        $this->proveedor = Proveedor::create([
            'razon_social' => 'Proveedor Test S.A.',
            'cuit' => '30-12345678-9',
            'email' => 'proveedor@test.com',
            'telefono' => '1112345678',
            'direccion' => 'Calle Falsa 123',
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
            'categoriaProductoID' => $categoria->categoriaProductoID,
            'precioVenta' => 1000,
            'stock_minimo' => 10,
        ]);

        // Crear stock inicial
        Stock::create([
            'producto_id' => $this->producto->productoID,
            'deposito_id' => $this->deposito->id ?? 1,
            'cantidad_disponible' => 5,
            'cantidad_reservada' => 0,
        ]);

        // Crear OC con detalle
        $this->ordenCompra = OrdenCompra::create([
            'codigo_orden' => 'OC-TEST-001',
            'proveedor_id' => $this->proveedor->id,
            'estado_id' => $this->estadoEnviada->id,
            'user_id' => $this->usuario->id,
            'fecha_emision' => now(),
            'fecha_entrega_estimada' => now()->addDays(7),
            'total' => 50000,
        ]);

        DetalleOrdenCompra::create([
            'orden_id' => $this->ordenCompra->id,
            'producto_id' => $this->producto->productoID,
            'cantidad_pedida' => 100,
            'cantidad_recibida' => 0,
            'precio_unitario' => 500,
            'subtotal' => 50000,
        ]);

        $this->service = app(RecepcionarMercaderiaService::class);
    }

    /**
     * Test CU-23: Recepción total de mercadería
     */
    public function test_recepcion_total_actualiza_stock_y_estado_oc()
    {
        // ARRANGE
        $stockInicial = Stock::where('producto_id', $this->producto->productoID)->first();
        $cantidadInicial = $stockInicial->cantidad_disponible;

        $items = [
            [
                'producto_id' => $this->producto->productoID,
                'cantidad_recibida' => 100, // Todo lo pedido
                'observaciones' => 'Recepción completa',
            ],
        ];

        // ACT
        $resultado = $this->service->ejecutar(
            $this->ordenCompra->id,
            $items,
            $this->usuario->id
        );

        // ASSERT
        $this->assertNotNull($resultado['recepcion']);
        $this->assertEquals('TOTAL', $resultado['recepcion']->tipo);

        // Stock actualizado
        $stockInicial->refresh();
        $this->assertEquals($cantidadInicial + 100, $stockInicial->cantidad_disponible);

        // OC en estado Recibida Total
        $this->ordenCompra->refresh();
        $this->assertEquals($this->estadoRecibidaTotal->id, $this->ordenCompra->estado_id);

        // Movimiento de stock creado
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $this->producto->productoID,
            'cantidad' => 100,
        ]);
    }

    /**
     * Test CU-23: Recepción parcial de mercadería
     */
    public function test_recepcion_parcial_mantiene_oc_pendiente()
    {
        // ARRANGE
        $items = [
            [
                'producto_id' => $this->producto->productoID,
                'cantidad_recibida' => 50, // Solo la mitad
                'observaciones' => 'Recepción parcial',
            ],
        ];

        // ACT
        $resultado = $this->service->ejecutar(
            $this->ordenCompra->id,
            $items,
            $this->usuario->id
        );

        // ASSERT
        $this->assertEquals('PARCIAL', $resultado['recepcion']->tipo);

        // OC en estado Recibida Parcial
        $this->ordenCompra->refresh();
        $this->assertEquals($this->estadoRecibidaParcial->id, $this->ordenCompra->estado_id);

        // Detalle actualizado con cantidad parcial
        $detalle = $this->ordenCompra->detalles->first();
        $this->assertEquals(50, $detalle->cantidad_recibida);
        $this->assertEquals(50, $detalle->cantidad_pendiente); // 100 - 50
    }

    /**
     * Test CU-23: Múltiples recepciones parciales hasta completar
     */
    public function test_multiples_recepciones_parciales()
    {
        // ARRANGE - Primera recepción parcial
        $items1 = [
            ['producto_id' => $this->producto->productoID, 'cantidad_recibida' => 30],
        ];

        // ACT - Primera recepción
        $this->service->ejecutar($this->ordenCompra->id, $items1, $this->usuario->id);

        // ASSERT
        $this->ordenCompra->refresh();
        $this->assertEquals($this->estadoRecibidaParcial->id, $this->ordenCompra->estado_id);

        // ACT - Segunda recepción parcial
        $items2 = [
            ['producto_id' => $this->producto->productoID, 'cantidad_recibida' => 40],
        ];
        $this->service->ejecutar($this->ordenCompra->id, $items2, $this->usuario->id);

        // ASSERT
        $this->ordenCompra->refresh();
        $this->assertEquals($this->estadoRecibidaParcial->id, $this->ordenCompra->estado_id);

        // ACT - Tercera recepción que completa
        $items3 = [
            ['producto_id' => $this->producto->productoID, 'cantidad_recibida' => 30],
        ];
        $this->service->ejecutar($this->ordenCompra->id, $items3, $this->usuario->id);

        // ASSERT - Ahora debe ser Total
        $this->ordenCompra->refresh();
        $this->assertEquals($this->estadoRecibidaTotal->id, $this->ordenCompra->estado_id);

        // Detalle debe tener todo recibido
        $detalle = $this->ordenCompra->detalles->first();
        $this->assertEquals(100, $detalle->cantidad_recibida);
        $this->assertEquals(0, $detalle->cantidad_pendiente);

        // Stock debe haberse incrementado en 100 total
        $stock = Stock::where('producto_id', $this->producto->productoID)->first();
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
                'producto_id' => $this->producto->productoID,
                'cantidad_recibida' => 150, // Excede los 100 pedidos
            ],
        ];

        // ACT & ASSERT
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('La cantidad a recibir (150) excede la cantidad pendiente (100)');

        $this->service->ejecutar($this->ordenCompra->id, $items, $this->usuario->id);
    }

    /**
     * Test CU-23: Registro de auditoría en recepción
     */
    public function test_recepcion_registra_auditoria()
    {
        // ARRANGE
        $items = [
            ['producto_id' => $this->producto->productoID, 'cantidad_recibida' => 100],
        ];

        // ACT
        $this->service->ejecutar($this->ordenCompra->id, $items, $this->usuario->id);

        // ASSERT
        $this->assertDatabaseHas('auditorias', [
            'accion' => 'RECEPCION_MERCADERIA',
            'tabla_afectada' => 'recepciones_mercaderia',
            'usuarioID' => $this->usuario->id,
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
        $response->assertInertia(fn ($page) => $page
            ->component('Compras/Recepciones/Index')
            ->has('ordenesPendientes')
            ->has('estadosPermitidos')
        );
    }
}
