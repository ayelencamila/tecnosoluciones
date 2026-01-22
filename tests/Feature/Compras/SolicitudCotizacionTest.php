<?php

namespace Tests\Feature\Compras;

use App\Models\CotizacionProveedor;
use App\Models\EstadoSolicitud;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\SolicitudCotizacion;
use App\Models\User;
use App\Models\Rol;
use App\Services\Compras\SolicitudCotizacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests Feature para CU-20: Gestión de Solicitudes de Cotización
 * 
 * Verifica:
 * - Creación de solicitudes
 * - Envío de Magic Links a proveedores
 * - Portal de proveedor y respuestas
 * - Cierre automático de solicitudes vencidas
 * - Ranking de ofertas
 */
class SolicitudCotizacionTest extends TestCase
{
    use RefreshDatabase;

    protected SolicitudCotizacionService $service;
    protected User $usuario;
    protected Proveedor $proveedor;
    protected Producto $producto;
    protected EstadoSolicitud $estadoAbierta;
    protected EstadoSolicitud $estadoEnviada;
    protected EstadoSolicitud $estadoVencida;
    protected EstadoSolicitud $estadoCerrada;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear estados de solicitud
        $this->estadoAbierta = EstadoSolicitud::create([
            'nombre' => 'Abierta',
            'descripcion' => 'Solicitud abierta',
            'color' => '#10B981',
        ]);
        $this->estadoEnviada = EstadoSolicitud::create([
            'nombre' => 'Enviada',
            'descripcion' => 'Solicitud enviada',
            'color' => '#3B82F6',
        ]);
        $this->estadoVencida = EstadoSolicitud::create([
            'nombre' => 'Vencida',
            'descripcion' => 'Solicitud vencida',
            'color' => '#EF4444',
        ]);
        $this->estadoCerrada = EstadoSolicitud::create([
            'nombre' => 'Cerrada',
            'descripcion' => 'Solicitud cerrada',
            'color' => '#6B7280',
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

        $this->service = app(SolicitudCotizacionService::class);
    }

    /**
     * Test CU-20: Crear solicitud de cotización manual
     */
    public function test_puede_crear_solicitud_cotizacion()
    {
        // ARRANGE
        $datos = [
            'observaciones' => 'Solicitud de prueba',
            'fecha_vencimiento' => now()->addDays(7),
        ];
        $productos = [
            [
                'producto_id' => $this->producto->productoID,
                'cantidad_sugerida' => 50,
                'observaciones' => 'Urgente',
            ],
        ];
        $proveedores = [$this->proveedor->id];

        // ACT
        $solicitud = $this->service->crearSolicitud(
            $datos,
            $productos,
            $proveedores,
            $this->usuario->id
        );

        // ASSERT
        $this->assertNotNull($solicitud);
        $this->assertStringStartsWith('SC-', $solicitud->codigo_solicitud);
        $this->assertEquals($this->estadoAbierta->id, $solicitud->estado_id);
        $this->assertCount(1, $solicitud->detalles);
        $this->assertCount(1, $solicitud->cotizacionesProveedores);
    }

    /**
     * Test CU-20: Magic Link genera token único para cada proveedor
     */
    public function test_cotizacion_proveedor_tiene_token_unico()
    {
        // ARRANGE
        $proveedor2 = Proveedor::create([
            'razon_social' => 'Otro Proveedor S.R.L.',
            'cuit' => '30-87654321-0',
            'email' => 'otro@test.com',
            'telefono' => '1187654321',
            'direccion' => 'Calle Real 456',
            'activo' => true,
        ]);

        $solicitud = $this->service->crearSolicitud(
            ['fecha_vencimiento' => now()->addDays(7)],
            [['producto_id' => $this->producto->productoID, 'cantidad_sugerida' => 20]],
            [$this->proveedor->id, $proveedor2->id],
            $this->usuario->id
        );

        // ASSERT
        $cotizaciones = $solicitud->cotizacionesProveedores;
        $this->assertCount(2, $cotizaciones);
        
        // Cada cotización tiene token único
        $tokens = $cotizaciones->pluck('token_unico');
        $this->assertCount(2, $tokens->unique());
        
        foreach ($tokens as $token) {
            $this->assertNotNull($token);
            $this->assertEquals(36, strlen($token)); // UUID format
        }
    }

    /**
     * Test CU-20: Portal de proveedor accesible por token
     */
    public function test_portal_proveedor_accesible_por_token()
    {
        // ARRANGE
        $solicitud = $this->service->crearSolicitud(
            ['fecha_vencimiento' => now()->addDays(7)],
            [['producto_id' => $this->producto->productoID, 'cantidad_sugerida' => 20]],
            [$this->proveedor->id],
            $this->usuario->id
        );

        // Marcar como enviada (simula el envío)
        $cotizacion = $solicitud->cotizacionesProveedores->first();
        $cotizacion->marcarEnviado();

        // ACT - Acceder al portal sin autenticación
        $response = $this->get(route('portal.cotizacion', $cotizacion->token_unico));

        // ASSERT
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Portal/Cotizacion')
            ->has('token')
            ->has('proveedor')
            ->has('solicitud')
            ->has('productos')
        );
    }

    /**
     * Test CU-20: Portal rechaza token inválido
     */
    public function test_portal_rechaza_token_invalido()
    {
        // ACT
        $response = $this->get(route('portal.cotizacion', 'token-invalido-12345'));

        // ASSERT
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Portal/Error')
            ->has('titulo')
            ->has('mensaje')
        );
    }

    /**
     * Test CU-20: Portal rechaza solicitud vencida
     */
    public function test_portal_rechaza_solicitud_vencida()
    {
        // ARRANGE
        $solicitud = $this->service->crearSolicitud(
            ['fecha_vencimiento' => now()->subDays(1)], // Ya vencida
            [['producto_id' => $this->producto->productoID, 'cantidad_sugerida' => 20]],
            [$this->proveedor->id],
            $this->usuario->id
        );

        $cotizacion = $solicitud->cotizacionesProveedores->first();
        $cotizacion->marcarEnviado();

        // ACT
        $response = $this->get(route('portal.cotizacion', $cotizacion->token_unico));

        // ASSERT
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Portal/Error')
            ->where('titulo', 'Solicitud vencida')
        );
    }

    /**
     * Test CU-20: Proveedor puede responder cotización
     */
    public function test_proveedor_puede_responder_cotizacion()
    {
        // ARRANGE
        $solicitud = $this->service->crearSolicitud(
            ['fecha_vencimiento' => now()->addDays(7)],
            [['producto_id' => $this->producto->productoID, 'cantidad_sugerida' => 50]],
            [$this->proveedor->id],
            $this->usuario->id
        );

        $cotizacion = $solicitud->cotizacionesProveedores->first();
        $cotizacion->marcarEnviado();

        // ACT
        $response = $this->post(route('portal.cotizacion.responder', $cotizacion->token_unico), [
            'respuestas' => [
                [
                    'producto_id' => $this->producto->productoID,
                    'precio_unitario' => 850.50,
                    'cantidad_disponible' => 50,
                    'plazo_entrega_dias' => 5,
                    'observaciones' => 'Entrega inmediata',
                ],
            ],
        ]);

        // ASSERT
        $cotizacion->refresh();
        $this->assertNotNull($cotizacion->fecha_respuesta);
        $this->assertCount(1, $cotizacion->respuestas);
        
        $respuesta = $cotizacion->respuestas->first();
        $this->assertEquals(850.50, $respuesta->precio_unitario);
        $this->assertEquals(50, $respuesta->cantidad_disponible);
        $this->assertEquals(5, $respuesta->plazo_entrega_dias);
    }

    /**
     * Test CU-20: Comando cierra solicitudes vencidas automáticamente
     */
    public function test_comando_cierra_solicitudes_vencidas()
    {
        // ARRANGE
        $solicitud = SolicitudCotizacion::create([
            'codigo_solicitud' => 'SC-TEST-001',
            'fecha_emision' => now()->subDays(10),
            'fecha_vencimiento' => now()->subDays(3), // Vencida hace 3 días
            'estado_id' => $this->estadoEnviada->id,
            'user_id' => $this->usuario->id,
        ]);

        // ACT
        $this->artisan('cotizaciones:cerrar-vencidas')
            ->assertSuccessful();

        // ASSERT
        $solicitud->refresh();
        $this->assertEquals($this->estadoVencida->id, $solicitud->estado_id);
    }

    /**
     * Test CU-20: Ranking de ofertas ordenado por precio
     */
    public function test_ranking_ofertas_ordenado_por_precio()
    {
        // ARRANGE
        $proveedor2 = Proveedor::create([
            'razon_social' => 'Proveedor Caro S.A.',
            'cuit' => '30-11111111-1',
            'email' => 'caro@test.com',
            'activo' => true,
        ]);

        $solicitud = $this->service->crearSolicitud(
            ['fecha_vencimiento' => now()->addDays(7)],
            [['producto_id' => $this->producto->productoID, 'cantidad_sugerida' => 10]],
            [$this->proveedor->id, $proveedor2->id],
            $this->usuario->id
        );

        // Simular respuestas
        $cotizacion1 = $solicitud->cotizacionesProveedores->where('proveedor_id', $this->proveedor->id)->first();
        $cotizacion2 = $solicitud->cotizacionesProveedores->where('proveedor_id', $proveedor2->id)->first();

        $cotizacion1->marcarEnviado();
        $cotizacion2->marcarEnviado();

        // Proveedor 1: precio bajo
        $this->service->registrarRespuestaProveedor($cotizacion1, [
            ['producto_id' => $this->producto->productoID, 'precio_unitario' => 500, 'cantidad_disponible' => 10, 'plazo_entrega_dias' => 5],
        ]);

        // Proveedor 2: precio alto
        $this->service->registrarRespuestaProveedor($cotizacion2, [
            ['producto_id' => $this->producto->productoID, 'precio_unitario' => 800, 'cantidad_disponible' => 10, 'plazo_entrega_dias' => 3],
        ]);

        // ACT
        $ranking = $this->service->obtenerRankingOfertas($solicitud);

        // ASSERT
        $this->assertCount(2, $ranking);
        
        // El primero debe ser el más barato
        $this->assertEquals($this->proveedor->id, $ranking[0]['proveedor']->id);
        $this->assertEquals(5000, $ranking[0]['total']); // 500 * 10
        
        // El segundo debe ser el más caro
        $this->assertEquals($proveedor2->id, $ranking[1]['proveedor']->id);
        $this->assertEquals(8000, $ranking[1]['total']); // 800 * 10
    }
}
