<?php

namespace Tests\Unit\Services\Compras;

use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\DetalleVenta;
use App\Models\EstadoCliente;
use App\Models\EstadoProducto;
use App\Models\EstadoVenta;
use App\Models\MedioPago;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Stock;
use App\Models\TipoCliente;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Models\Venta;
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
    protected UnidadMedida $unidadMedida;
    protected Deposito $deposito;
    protected User $usuario;
    protected EstadoVenta $estadoCompletada;
    protected EstadoProducto $estadoActivo;
    protected Cliente $cliente;
    protected MedioPago $medioPago;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear categoría
        $this->categoria = CategoriaProducto::create([
            'nombre' => 'Test',
            'descripcion' => 'Categoría de prueba',
        ]);

        // Crear unidad de medida
        $this->unidadMedida = UnidadMedida::create([
            'nombre' => 'Unidad',
            'abreviatura' => 'u',
            'activo' => true,
        ]);

        // Crear depósito
        $this->deposito = Deposito::create([
            'nombre' => 'Principal',
            'descripcion' => 'Depósito principal',
            'activo' => true,
        ]);

        // Crear estado de producto (necesario para los productos)
        $this->estadoActivo = EstadoProducto::create([
            'nombre' => 'Activo',
            'descripcion' => 'Producto activo',
        ]);

        // Crear estado de venta (necesario para las ventas)
        $this->estadoCompletada = EstadoVenta::factory()->completada()->create();

        $rol = Rol::firstOrCreate(
            ['nombre' => 'vendedor'],
            ['descripcion' => 'Vendedor', 'activo' => true]
        );
        $this->usuario = User::factory()->create(['rol_id' => $rol->rol_id]);

        // Crear cliente (necesario para las ventas)
        $tipoCliente = TipoCliente::firstOrCreate(
            ['nombreTipo' => 'Consumidor Final'],
            ['descripcion' => 'Consumidor final']
        );
        $estadoCliente = EstadoCliente::firstOrCreate(
            ['nombreEstado' => 'Activo'],
            ['descripcion' => 'Cliente activo']
        );
        $this->cliente = Cliente::create([
            'nombre' => 'Cliente',
            'apellido' => 'Test',
            'DNI' => '12345678',
            'tipoClienteID' => $tipoCliente->tipoClienteID,
            'estadoClienteID' => $estadoCliente->estadoClienteID,
        ]);

        // Crear medio de pago (necesario para las ventas)
        $this->medioPago = MedioPago::create([
            'nombre' => 'Efectivo',
            'recargo_porcentaje' => 0,
            'activo' => true,
        ]);

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
            'categoriaProductoID' => $this->categoria->id,
            'unidad_medida_id' => $this->unidadMedida->id,
            'estadoProductoID' => $this->estadoActivo->id,
        ]);

        Stock::create([
            'productoID' => $productoBajo->id,
            'deposito_id' => $this->deposito->deposito_id,
            'cantidad_disponible' => 10,
            'stock_minimo' => 50, // stock_minimo en tabla Stock
        ]);

        $productoOk = Producto::create([
            'codigo' => 'OK001',
            'nombre' => 'Producto Stock OK',
            'categoriaProductoID' => $this->categoria->id,
            'unidad_medida_id' => $this->unidadMedida->id,
            'estadoProductoID' => $this->estadoActivo->id,
        ]);

        Stock::create([
            'productoID' => $productoOk->id,
            'deposito_id' => $this->deposito->deposito_id,
            'cantidad_disponible' => 100,
            'stock_minimo' => 20,
        ]);

        // ACT
        $productosBajoStock = $this->service->detectarProductosBajoStock();

        // ASSERT - Service returns collection of arrays
        $this->assertCount(1, $productosBajoStock);
        $this->assertEquals($productoBajo->id, $productosBajoStock->first()['producto_id']);
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
            'categoriaProductoID' => $this->categoria->id,
            'unidad_medida_id' => $this->unidadMedida->id,
            'estadoProductoID' => $this->estadoActivo->id,
        ]);

        Stock::create([
            'productoID' => $productoAlta->id,
            'deposito_id' => $this->deposito->deposito_id,
            'cantidad_disponible' => 10, // Bajo stock para que cobertura < 14 días
            'stock_minimo' => 5,
        ]);

        // Crear ventas recientes (alta rotación: 200 unidades en 30 días)
        for ($i = 0; $i < 5; $i++) {
            $venta = Venta::create([
                'user_id' => $this->usuario->id,
                'clienteID' => $this->cliente->clienteID,
                'medio_pago_id' => $this->medioPago->medioPagoID,
                'estado_venta_id' => $this->estadoCompletada->estadoVentaID,
                'numero_comprobante' => 'V-ALTA-' . $i,
                'fecha_venta' => now()->subDays($i * 5),
                'total' => 4000,
            ]);

            DetalleVenta::create([
                'venta_id' => $venta->venta_id,
                'producto_id' => $productoAlta->id,
                'cantidad' => 40,
                'precio_unitario' => 100,
                'subtotal' => 4000,
                'subtotal_neto' => 4000,
            ]);
        }

        $productoBaja = Producto::create([
            'codigo' => 'BAJA001',
            'nombre' => 'Producto Baja Rotación',
            'categoriaProductoID' => $this->categoria->id,
            'unidad_medida_id' => $this->unidadMedida->id,
            'estadoProductoID' => $this->estadoActivo->id,
        ]);

        Stock::create([
            'productoID' => $productoBaja->id,
            'deposito_id' => $this->deposito->deposito_id,
            'cantidad_disponible' => 50,
            'stock_minimo' => 5,
        ]);

        // Solo 1 venta pequeña (baja rotación)
        $venta = Venta::create([
            'user_id' => $this->usuario->id,
            'clienteID' => $this->cliente->clienteID,
            'medio_pago_id' => $this->medioPago->medioPagoID,
            'estado_venta_id' => $this->estadoCompletada->estadoVentaID,
            'numero_comprobante' => 'V-BAJA-001',
            'fecha_venta' => now()->subDays(15),
            'total' => 200,
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->venta_id,
            'producto_id' => $productoBaja->id,
            'cantidad' => 2,
            'precio_unitario' => 100,
            'subtotal' => 200,
            'subtotal_neto' => 200,
        ]);

        // ACT - umbral default = UMBRAL_ALTA_ROTACION (10)
        $productosAltaRotacion = $this->service->detectarProductosAltaRotacion(
            diasAnalizar: 30,
            umbral: 50
        );

        // ASSERT - Returns collection of arrays
        $this->assertCount(1, $productosAltaRotacion);
        $this->assertEquals($productoAlta->id, $productosAltaRotacion->first()['producto_id']);
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
            'categoriaProductoID' => $this->categoria->id,
            'unidad_medida_id' => $this->unidadMedida->id,
            'estadoProductoID' => $this->estadoActivo->id,
        ]);

        Stock::create([
            'productoID' => $productoDobleCriterio->id,
            'deposito_id' => $this->deposito->deposito_id,
            'cantidad_disponible' => 5, // Bajo el mínimo Y poca cobertura
            'stock_minimo' => 100, // Alto mínimo → bajo stock
        ]);

        // Crear ventas altas (también alta rotación)
        for ($i = 0; $i < 10; $i++) {
            $venta = Venta::create([
                'user_id' => $this->usuario->id,
                'clienteID' => $this->cliente->clienteID,
                'medio_pago_id' => $this->medioPago->medioPagoID,
                'estado_venta_id' => $this->estadoCompletada->estadoVentaID,
                'numero_comprobante' => 'V-DOBLE-' . $i,
                'fecha_venta' => now()->subDays($i * 2),
                'total' => 1000,
            ]);

            DetalleVenta::create([
                'venta_id' => $venta->venta_id,
                'producto_id' => $productoDobleCriterio->id,
                'cantidad' => 10,
                'precio_unitario' => 100,
                'subtotal' => 1000,
                'subtotal_neto' => 1000,
            ]);
        }

        // ACT - detectarProductosNecesitanReposicion takes no parameters
        $productosReposicion = $this->service->detectarProductosNecesitanReposicion();

        // ASSERT - Solo debe aparecer UNA vez aunque cumpla ambos criterios
        $ids = $productosReposicion->pluck('producto_id');
        $this->assertEquals(1, $ids->unique()->count());
        $this->assertEquals($productoDobleCriterio->id, $ids->first());
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
            'categoriaProductoID' => $this->categoria->id,
            'unidad_medida_id' => $this->unidadMedida->id,
            'estadoProductoID' => $this->estadoActivo->id,
        ]);

        // ACT
        $productosBajoStock = $this->service->detectarProductosBajoStock();

        // ASSERT - No debe detectar productos sin registro de stock
        $this->assertCount(0, $productosBajoStock);
    }

    /**
     * Test: Respeta stock con mínimo en cero (no alerta)
     */
    public function test_ignora_productos_con_stock_minimo_cero()
    {
        // ARRANGE
        $producto = Producto::create([
            'codigo' => 'MINCERO001',
            'nombre' => 'Producto Sin Mínimo',
            'categoriaProductoID' => $this->categoria->id,
            'unidad_medida_id' => $this->unidadMedida->id,
            'estadoProductoID' => $this->estadoActivo->id,
        ]);

        Stock::create([
            'productoID' => $producto->id,
            'deposito_id' => $this->deposito->deposito_id,
            'cantidad_disponible' => 5,
            'stock_minimo' => 0, // Sin mínimo configurado
        ]);

        // ACT
        $productosBajoStock = $this->service->detectarProductosBajoStock();

        // ASSERT - No debe detectar si stock_minimo es 0
        $this->assertCount(0, $productosBajoStock);
    }
}
