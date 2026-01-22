<?php

namespace Tests\Unit\Services\Compras;

use App\Models\Producto;
use App\Models\Stock;
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\User;
use App\Models\Rol;
use App\Services\Compras\MonitoreoStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitarios para CU-20: MonitoreoStockService
 * 
 * Verifica:
 * - Detección de productos bajo stock mínimo
 * - Detección de productos de alta rotación
 * - Combinación de criterios sin duplicados
 */
class MonitoreoStockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MonitoreoStockService $service;
    protected CategoriaProducto $categoria;
    protected EstadoProducto $estadoActivo;
    protected User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear categoría y estado
        $this->categoria = CategoriaProducto::create([
            'nombre' => 'Test',
            'descripcion' => 'Categoría de prueba',
        ]);

        $this->estadoActivo = EstadoProducto::create([
            'nombreEstado' => 'Activo',
            'descripcion' => 'Producto activo',
        ]);

        $rol = Rol::create(['nombre' => 'Vendedor']);
        $this->usuario = User::factory()->create(['rolID' => $rol->rolID]);

        $this->service = app(MonitoreoStockService::class);
    }

    /**
     * Test: Detectar productos bajo stock mínimo
     */
    public function test_detecta_productos_bajo_stock_minimo()
    {
        // ARRANGE
        $productoBajo = Producto::create([
            'codigo' => 'BAJO001',
            'nombre' => 'Producto Bajo Stock',
            'categoriaProductoID' => $this->categoria->categoriaProductoID,
            'estadoProductoID' => $this->estadoActivo->estadoProductoID,
            'precioVenta' => 100,
            'stock_minimo' => 50,
        ]);

        Stock::create([
            'producto_id' => $productoBajo->productoID,
            'deposito_id' => 1,
            'cantidad_disponible' => 10, // Por debajo de 50
            'cantidad_reservada' => 0,
        ]);

        $productoOk = Producto::create([
            'codigo' => 'OK001',
            'nombre' => 'Producto Stock OK',
            'categoriaProductoID' => $this->categoria->categoriaProductoID,
            'estadoProductoID' => $this->estadoActivo->estadoProductoID,
            'precioVenta' => 100,
            'stock_minimo' => 20,
        ]);

        Stock::create([
            'producto_id' => $productoOk->productoID,
            'deposito_id' => 1,
            'cantidad_disponible' => 100, // Por encima de 20
            'cantidad_reservada' => 0,
        ]);

        // ACT
        $productosBajoStock = $this->service->detectarProductosBajoStock();

        // ASSERT
        $this->assertCount(1, $productosBajoStock);
        $this->assertEquals($productoBajo->productoID, $productosBajoStock->first()->productoID);
    }

    /**
     * Test: Detectar productos de alta rotación (ventas en últimos 30 días)
     */
    public function test_detecta_productos_alta_rotacion()
    {
        // ARRANGE
        $productoAlta = Producto::create([
            'codigo' => 'ALTA001',
            'nombre' => 'Producto Alta Rotación',
            'categoriaProductoID' => $this->categoria->categoriaProductoID,
            'estadoProductoID' => $this->estadoActivo->estadoProductoID,
            'precioVenta' => 100,
            'stock_minimo' => 10,
        ]);

        Stock::create([
            'producto_id' => $productoAlta->productoID,
            'deposito_id' => 1,
            'cantidad_disponible' => 50,
            'cantidad_reservada' => 0,
        ]);

        // Crear ventas recientes (alta rotación)
        for ($i = 0; $i < 5; $i++) {
            $venta = Venta::create([
                'clienteID' => null,
                'vendedorID' => $this->usuario->id,
                'fecha' => now()->subDays($i * 5),
                'total' => 1000,
            ]);

            DetalleVenta::create([
                'ventaID' => $venta->ventaID,
                'productoID' => $productoAlta->productoID,
                'cantidad' => 20,
                'precioUnitario' => 100,
                'subtotal' => 2000,
            ]);
        }

        $productoBaja = Producto::create([
            'codigo' => 'BAJA001',
            'nombre' => 'Producto Baja Rotación',
            'categoriaProductoID' => $this->categoria->categoriaProductoID,
            'estadoProductoID' => $this->estadoActivo->estadoProductoID,
            'precioVenta' => 100,
            'stock_minimo' => 10,
        ]);

        Stock::create([
            'producto_id' => $productoBaja->productoID,
            'deposito_id' => 1,
            'cantidad_disponible' => 50,
            'cantidad_reservada' => 0,
        ]);

        // Solo 1 venta (baja rotación)
        $venta = Venta::create([
            'clienteID' => null,
            'vendedorID' => $this->usuario->id,
            'fecha' => now()->subDays(15),
            'total' => 100,
        ]);

        DetalleVenta::create([
            'ventaID' => $venta->ventaID,
            'productoID' => $productoBaja->productoID,
            'cantidad' => 2,
            'precioUnitario' => 100,
            'subtotal' => 200,
        ]);

        // ACT
        $productosAltaRotacion = $this->service->detectarProductosAltaRotacion(
            diasAnalisis: 30,
            umbralVentas: 50 // Más de 50 unidades en 30 días
        );

        // ASSERT
        $this->assertCount(1, $productosAltaRotacion);
        $this->assertEquals($productoAlta->productoID, $productosAltaRotacion->first()->productoID);
    }

    /**
     * Test: Combinación de criterios sin duplicados
     */
    public function test_detecta_productos_necesitan_reposicion_sin_duplicados()
    {
        // ARRANGE - Producto que cumple AMBOS criterios
        $productoDobleCriterio = Producto::create([
            'codigo' => 'DOBLE001',
            'nombre' => 'Producto Doble Criterio',
            'categoriaProductoID' => $this->categoria->categoriaProductoID,
            'estadoProductoID' => $this->estadoActivo->estadoProductoID,
            'precioVenta' => 100,
            'stock_minimo' => 100, // Alto mínimo
        ]);

        Stock::create([
            'producto_id' => $productoDobleCriterio->productoID,
            'deposito_id' => 1,
            'cantidad_disponible' => 20, // Bajo el mínimo
            'cantidad_reservada' => 0,
        ]);

        // Crear ventas altas (también alta rotación)
        for ($i = 0; $i < 10; $i++) {
            $venta = Venta::create([
                'clienteID' => null,
                'vendedorID' => $this->usuario->id,
                'fecha' => now()->subDays($i * 2),
                'total' => 1000,
            ]);

            DetalleVenta::create([
                'ventaID' => $venta->ventaID,
                'productoID' => $productoDobleCriterio->productoID,
                'cantidad' => 10,
                'precioUnitario' => 100,
                'subtotal' => 1000,
            ]);
        }

        // ACT
        $productosReposicion = $this->service->detectarProductosNecesitanReposicion(
            diasAnalisis: 30,
            umbralVentas: 50
        );

        // ASSERT - Solo debe aparecer UNA vez aunque cumpla ambos criterios
        $ids = $productosReposicion->pluck('productoID');
        $this->assertEquals(1, $ids->count());
        $this->assertEquals($productoDobleCriterio->productoID, $ids->first());
    }

    /**
     * Test: No detecta productos sin stock definido
     */
    public function test_ignora_productos_sin_stock()
    {
        // ARRANGE - Producto sin registro de stock
        Producto::create([
            'codigo' => 'SINSTOCK001',
            'nombre' => 'Producto Sin Stock',
            'categoriaProductoID' => $this->categoria->categoriaProductoID,
            'estadoProductoID' => $this->estadoActivo->estadoProductoID,
            'precioVenta' => 100,
            'stock_minimo' => 50,
        ]);

        // ACT
        $productosBajoStock = $this->service->detectarProductosBajoStock();

        // ASSERT - No debe detectar productos sin registro de stock
        $this->assertCount(0, $productosBajoStock);
    }

    /**
     * Test: Respeta productos inactivos
     */
    public function test_ignora_productos_inactivos()
    {
        // ARRANGE
        $estadoInactivo = EstadoProducto::create([
            'nombreEstado' => 'Inactivo',
            'descripcion' => 'Producto inactivo',
        ]);

        $productoInactivo = Producto::create([
            'codigo' => 'INACTIVO001',
            'nombre' => 'Producto Inactivo',
            'categoriaProductoID' => $this->categoria->categoriaProductoID,
            'estadoProductoID' => $estadoInactivo->estadoProductoID,
            'precioVenta' => 100,
            'stock_minimo' => 100,
        ]);

        Stock::create([
            'producto_id' => $productoInactivo->productoID,
            'deposito_id' => 1,
            'cantidad_disponible' => 5, // Muy bajo
            'cantidad_reservada' => 0,
        ]);

        // ACT
        $productosBajoStock = $this->service->detectarProductosBajoStock();

        // ASSERT - No debe detectar productos inactivos
        $idsDetectados = $productosBajoStock->pluck('productoID');
        $this->assertNotContains($productoInactivo->productoID, $idsDetectados);
    }
}
