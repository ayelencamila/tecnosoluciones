<?php

namespace Tests\Feature\Plataforma;

use App\Models\Empresa;
use App\Models\MedioPago;
use App\Models\MotivoDemoraReparacion;
use App\Models\Rol;
use App\Models\TipoCliente;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Services\Plataforma\AltaEmpresaService;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verifica el tratamiento de los catalogos parametricos:
 *
 *  - Los que son decision de negocio de cada empresa quedan aislados.
 *  - Los compartidos por todas (geografia y estados) son de solo lectura
 *    para el administrador de empresa.
 *  - La integridad referencial entre empresas la hace cumplir la base.
 */
class CatalogosTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::limpiar();

        parent::tearDown();
    }

    /**
     * @return array{0: Empresa, 1: User}
     */
    private function empresaConAdmin(string $nombre): array
    {
        $empresa = Empresa::create([
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.uniqid(),
            'activa' => true,
        ]);

        $rol = Rol::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'administrador',
            'es_admin_empresa' => true,
            'descripcion' => 'Admin',
            'permisos' => [],
            'activo' => true,
        ]);

        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'rol_id' => $rol->rol_id,
        ]);

        return [$empresa, $admin];
    }

    private function superUsuario(): User
    {
        $super = new User();
        $super->empresa_id = null;
        $super->rol_id = null;
        $super->es_super_admin = true;
        $super->name = 'Operador';
        $super->email = 'operador-'.uniqid().'@plataforma.test';
        $super->password = bcrypt('secreto');
        $super->activo = true;
        $super->save();

        return $super;
    }

    /** @test */
    public function los_catalogos_de_negocio_son_propios_de_cada_empresa(): void
    {
        [$empresaA, $adminA] = $this->empresaConAdmin('Empresa A');
        [$empresaB, $adminB] = $this->empresaConAdmin('Empresa B');

        TenantContext::paraEmpresa($empresaA->id, function () {
            MedioPago::create(['nombre' => 'Mercado Pago', 'recargo_porcentaje' => 0, 'activo' => true]);
            TipoCliente::create(['nombreTipo' => 'Distribuidor', 'activo' => true]);
            UnidadMedida::create(['nombre' => 'Caja', 'abreviatura' => 'cj', 'activo' => true]);
        });

        $this->actingAs($adminA);
        $this->assertSame(1, MedioPago::count());
        $this->assertSame(1, TipoCliente::count());
        $this->assertSame(1, UnidadMedida::count());

        $this->actingAs($adminB);
        $this->assertSame(0, MedioPago::count(), 'La empresa B no debe ver los medios de pago de la A');
        $this->assertSame(0, TipoCliente::count());
        $this->assertSame(0, UnidadMedida::count());
    }

    /**
     * La clave natural es unica DENTRO de la empresa, no en todo el sistema:
     * dos empresas distintas pueden tener cada una su "Efectivo".
     *
     * @test
     */
    public function dos_empresas_pueden_tener_un_medio_de_pago_con_el_mismo_nombre(): void
    {
        [$empresaA] = $this->empresaConAdmin('Empresa A');
        [$empresaB] = $this->empresaConAdmin('Empresa B');

        TenantContext::paraEmpresa($empresaA->id, fn () => MedioPago::create(
            ['nombre' => 'Efectivo', 'recargo_porcentaje' => 0, 'activo' => true]
        ));

        TenantContext::paraEmpresa($empresaB->id, fn () => MedioPago::create(
            ['nombre' => 'Efectivo', 'recargo_porcentaje' => 0, 'activo' => true]
        ));

        $this->assertSame(2, MedioPago::withoutGlobalScopes()->where('nombre', 'Efectivo')->count());
    }

    /**
     * La base rechaza por si misma que un documento de una empresa apunte al
     * catalogo de otra, via la FK compuesta (empresa_id, medio_pago_id).
     *
     * @test
     */
    public function la_base_rechaza_una_venta_que_apunta_al_medio_de_pago_de_otra_empresa(): void
    {
        [$empresaA] = $this->empresaConAdmin('Empresa A');
        [$empresaB] = $this->empresaConAdmin('Empresa B');

        $medioDeA = TenantContext::paraEmpresa($empresaA->id, fn () => MedioPago::create(
            ['nombre' => 'Efectivo', 'recargo_porcentaje' => 0, 'activo' => true]
        ));

        $this->expectException(QueryException::class);

        // Venta de la empresa B usando el medio de pago de la empresa A.
        DB::table('ventas')->insert([
            'empresa_id' => $empresaB->id,
            'medio_pago_id' => $medioDeA->medioPagoID,
            'clienteID' => 1,
            'user_id' => 1,
            'estado_venta_id' => 1,
            'fecha_venta' => now(),
            'numero_comprobante' => 'CRUZADA-1',
            'total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function el_admin_de_empresa_no_puede_escribir_los_catalogos_geograficos(): void
    {
        [, $admin] = $this->empresaConAdmin('Empresa A');

        // Las rutas de escritura ya no existen para /admin: solo index y show.
        $this->actingAs($admin)->get(route('admin.provincias.index'))->assertOk();

        $this->actingAs($admin)
            ->post('/admin/provincias', ['nombre' => 'Provincia Inventada'])
            ->assertStatus(405);

        $this->actingAs($admin)
            ->delete('/admin/provincias/1')
            ->assertStatus(405);

        $this->assertDatabaseMissing('provincias', ['nombre' => 'Provincia Inventada']);
    }

    /** @test */
    public function el_admin_de_empresa_no_puede_escribir_los_catalogos_de_estados(): void
    {
        [, $admin] = $this->empresaConAdmin('Empresa A');

        $this->actingAs($admin)->get(route('admin.estados-cliente.index'))->assertOk();

        $this->actingAs($admin)
            ->post('/admin/estados-cliente', ['nombreEstado' => 'Inventado'])
            ->assertStatus(405);

        $this->assertDatabaseMissing('estados_cliente', ['nombreEstado' => 'Inventado']);
    }

    /** @test */
    public function el_admin_de_empresa_no_puede_escribir_los_catalogos_globales_por_api(): void
    {
        [, $admin] = $this->empresaConAdmin('Empresa A');

        // Lectura: permitida.
        $this->actingAs($admin)->getJson('/api/provincias')->assertOk();

        // Escritura: vive bajo el prefijo de plataforma, inalcanzable para la empresa.
        $this->actingAs($admin)
            ->postJson('/api/plataforma/provincias', ['nombre' => 'Por API'])
            ->assertNotFound();

        $this->assertDatabaseMissing('provincias', ['nombre' => 'Por API']);
    }

    /** @test */
    public function el_super_usuario_si_puede_escribir_los_catalogos_globales(): void
    {
        $super = $this->superUsuario();

        $this->actingAs($super)
            ->post(route('super.catalogos.provincias.store'), ['nombre' => 'Provincia Nueva'])
            ->assertRedirect();

        $this->assertDatabaseHas('provincias', ['nombre' => 'Provincia Nueva']);
    }

    /**
     * Una empresa nueva tiene que quedar OPERATIVA: sin medios de pago no
     * puede registrar una venta, sin tipos de cliente no puede dar de alta un
     * cliente y sin unidades de medida no puede crear un producto.
     *
     * @test
     */
    public function el_alta_de_empresa_deja_los_catalogos_cargados(): void
    {
        $empresa = app(AltaEmpresaService::class)->ejecutar(
            ['nombre' => 'Cliente Nuevo'],
            ['name' => 'Dueño', 'email' => 'dueno@nuevo.test', 'password' => 'Str0ng!Pass1'],
        );

        TenantContext::paraEmpresa($empresa->id, function () {
            $this->assertGreaterThan(0, MedioPago::count(), 'Sin medios de pago no podría vender');
            $this->assertGreaterThan(0, TipoCliente::count(), 'Sin tipos de cliente no podría dar de alta clientes');
            $this->assertGreaterThan(0, UnidadMedida::count(), 'Sin unidades de medida no podría crear productos');
            $this->assertGreaterThan(0, MotivoDemoraReparacion::count());
        });
    }
}
