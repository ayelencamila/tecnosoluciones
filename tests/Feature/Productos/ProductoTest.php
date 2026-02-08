<?php

namespace Tests\Feature\Productos;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Rol;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\EstadoProducto;
use App\Models\UnidadMedida;
use App\Models\TipoCliente;
use App\Models\PrecioProducto;
use App\Models\Stock;
use App\Models\Deposito;

class ProductoTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected CategoriaProducto $categoria;
    protected EstadoProducto $estadoActivo;
    protected UnidadMedida $unidad;
    protected Deposito $deposito;
    protected TipoCliente $tipoMinorista;
    protected TipoCliente $tipoMayorista;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol administrador
        $rol = Rol::create([
            'nombre' => 'administrador',
            'descripcion' => 'Administrador del sistema',
            'permisos' => [],
            'activo' => true,
        ]);

        $this->admin = User::factory()->create(['rol_id' => $rol->rol_id]);

        // Tablas paramétricas
        $this->categoria = CategoriaProducto::create(['nombre' => 'Repuestos', 'activo' => true]);
        $this->estadoActivo = EstadoProducto::create(['nombre' => 'Activo', 'descripcion' => 'Disponible']);
        EstadoProducto::create(['nombre' => 'Inactivo', 'descripcion' => 'No disponible']);
        $this->unidad = UnidadMedida::create(['nombre' => 'Unidad', 'abreviatura' => 'u', 'activo' => true]);
        $this->deposito = Deposito::create([
            'nombre' => 'Depósito Principal',
            'descripcion' => 'Principal',
            'activo' => true,
            'esPrincipal' => true,
        ]);

        $this->tipoMinorista = TipoCliente::create([
            'nombreTipo' => 'Minorista',
            'descripcion' => 'Cliente minorista',
            'activo' => true,
        ]);
        $this->tipoMayorista = TipoCliente::create([
            'nombreTipo' => 'Mayorista',
            'descripcion' => 'Cliente mayorista',
            'activo' => true,
        ]);
    }

    /** @test */
    public function puede_crear_producto_con_precios_y_stock_inicial()
    {
        $response = $this->actingAs($this->admin)->post(route('productos.store'), [
            'codigo' => 'SSD-480',
            'nombre' => 'Disco SSD 480GB',
            'descripcion' => 'Disco sólido de 480GB',
            'es_servicio' => false,
            'unidad_medida_id' => $this->unidad->id,
            'categoriaProductoID' => $this->categoria->id,
            'estadoProductoID' => $this->estadoActivo->id,
            'precio_costo' => 45000,
            'stock_minimo' => 5,
            'cantidad_inicial' => 10,
            'precios' => [
                ['tipoClienteID' => $this->tipoMinorista->tipoClienteID, 'precio' => 55000],
                ['tipoClienteID' => $this->tipoMayorista->tipoClienteID, 'precio' => 50000],
            ],
        ]);

        $response->assertRedirect();

        $producto = Producto::where('codigo', 'SSD-480')->first();
        $this->assertNotNull($producto);
        $this->assertEquals('Disco SSD 480GB', $producto->nombre);
        $this->assertEquals(45000, $producto->precio_costo);
        $this->assertFalse($producto->es_servicio);

        // Verificar precios de venta
        $precioMin = PrecioProducto::where('productoID', $producto->id)
            ->where('tipoClienteID', $this->tipoMinorista->tipoClienteID)
            ->whereNull('fechaHasta')->first();
        $this->assertEquals(55000, $precioMin->precio);

        $precioMay = PrecioProducto::where('productoID', $producto->id)
            ->where('tipoClienteID', $this->tipoMayorista->tipoClienteID)
            ->whereNull('fechaHasta')->first();
        $this->assertEquals(50000, $precioMay->precio);

        // Verificar stock inicial
        $stock = Stock::where('productoID', $producto->id)->first();
        $this->assertNotNull($stock);
        $this->assertEquals(10, $stock->cantidad_disponible);
        $this->assertEquals(5, $stock->stock_minimo);
    }

    /** @test */
    public function puede_crear_producto_servicio_sin_stock()
    {
        $response = $this->actingAs($this->admin)->post(route('productos.store'), [
            'codigo' => 'SRV-001',
            'nombre' => 'Instalación de Software',
            'es_servicio' => true,
            'unidad_medida_id' => $this->unidad->id,
            'categoriaProductoID' => $this->categoria->id,
            'estadoProductoID' => $this->estadoActivo->id,
            'precios' => [
                ['tipoClienteID' => $this->tipoMinorista->tipoClienteID, 'precio' => 15000],
            ],
        ]);

        $response->assertRedirect();

        $producto = Producto::where('codigo', 'SRV-001')->first();
        $this->assertTrue($producto->es_servicio);

        // Servicios no crean stock
        $stock = Stock::where('productoID', $producto->id)->first();
        $this->assertNull($stock);
    }

    /** @test */
    public function no_permite_crear_producto_sin_precios()
    {
        $response = $this->actingAs($this->admin)->post(route('productos.store'), [
            'codigo' => 'TEST-001',
            'nombre' => 'Producto sin precio',
            'unidad_medida_id' => $this->unidad->id,
            'categoriaProductoID' => $this->categoria->id,
            'estadoProductoID' => $this->estadoActivo->id,
            'precios' => [],
        ]);

        $response->assertSessionHasErrors('precios');
        $this->assertDatabaseMissing('productos', ['codigo' => 'TEST-001']);
    }

    /** @test */
    public function no_permite_codigo_duplicado()
    {
        Producto::create([
            'codigo' => 'DUP-001',
            'nombre' => 'Producto Original',
            'categoriaProductoID' => $this->categoria->id,
            'estadoProductoID' => $this->estadoActivo->id,
            'unidad_medida_id' => $this->unidad->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('productos.store'), [
            'codigo' => 'DUP-001',
            'nombre' => 'Producto Duplicado',
            'unidad_medida_id' => $this->unidad->id,
            'categoriaProductoID' => $this->categoria->id,
            'estadoProductoID' => $this->estadoActivo->id,
            'precios' => [
                ['tipoClienteID' => $this->tipoMinorista->tipoClienteID, 'precio' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('codigo');
    }

    /** @test */
    public function puede_editar_producto_y_actualizar_precios()
    {
        // Crear producto
        $producto = Producto::create([
            'codigo' => 'EDIT-001',
            'nombre' => 'Producto Original',
            'categoriaProductoID' => $this->categoria->id,
            'estadoProductoID' => $this->estadoActivo->id,
            'unidad_medida_id' => $this->unidad->id,
            'precio_costo' => 30000,
        ]);

        PrecioProducto::create([
            'productoID' => $producto->id,
            'tipoClienteID' => $this->tipoMinorista->tipoClienteID,
            'precio' => 40000,
            'fechaDesde' => now()->subDays(30)->toDateString(),
            'usuarioID' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('productos.update', $producto->id), [
            'codigo' => 'EDIT-001',
            'nombre' => 'Producto Editado',
            'unidad_medida_id' => $this->unidad->id,
            'categoriaProductoID' => $this->categoria->id,
            'estadoProductoID' => $this->estadoActivo->id,
            'precio_costo' => 35000,
            'motivo' => 'Actualización de precio por reposición',
            'precios' => [
                ['tipoClienteID' => $this->tipoMinorista->tipoClienteID, 'precio' => 50000],
            ],
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $producto->refresh();
        $this->assertEquals('Producto Editado', $producto->nombre);
        $this->assertEquals(35000, $producto->precio_costo);

        // Verificar que el precio viejo se cerró y hay uno nuevo
        $precioViejo = PrecioProducto::where('productoID', $producto->id)
            ->where('precio', 40000)
            ->whereNotNull('fechaHasta')
            ->first();
        $this->assertNotNull($precioViejo);

        $precioNuevo = PrecioProducto::where('productoID', $producto->id)
            ->where('precio', 50000)
            ->whereNull('fechaHasta')
            ->first();
        $this->assertNotNull($precioNuevo);
    }

    /** @test */
    public function puede_dar_de_baja_producto()
    {
        $producto = Producto::create([
            'codigo' => 'BAJA-001',
            'nombre' => 'Producto a dar de baja',
            'categoriaProductoID' => $this->categoria->id,
            'estadoProductoID' => $this->estadoActivo->id,
            'unidad_medida_id' => $this->unidad->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('productos.darDeBaja', $producto->id), [
            'motivo' => 'Producto descontinuado por el fabricante',
        ]);

        $response->assertRedirect();

        $producto->refresh();
        $estadoInactivo = EstadoProducto::where('nombre', 'Inactivo')->first();
        $this->assertEquals($estadoInactivo->id, $producto->estadoProductoID);
    }
}
