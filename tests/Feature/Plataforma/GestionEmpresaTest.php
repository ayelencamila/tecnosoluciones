<?php

namespace Tests\Feature\Plataforma;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Los dos huecos operativos que tenía el panel de plataforma:
 *
 *  - Editar los datos de una empresa cliente (cambió de domicilio, corrigió
 *    el CUIT). Antes había que entrar como esa empresa, lo que además dejaba
 *    la auditoría como si el cambio lo hubiera hecho ella.
 *
 *  - Devolverle el acceso al administrador de un cliente. Si es uno solo y
 *    pierde la contraseña o queda bloqueado por intentos fallidos, nadie
 *    dentro de esa empresa puede resolverlo.
 */
class GestionEmpresaTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    private Empresa $empresa;

    private User $adminEmpresa;

    private User $vendedorEmpresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->super = $this->superUsuario();

        $this->empresa = Empresa::create([
            'nombre' => 'Cliente Original',
            'slug' => 'cliente-original-'.uniqid(),
            'cuit' => '30111111111',
            'telefono' => '3764000000',
            'activa' => true,
        ]);

        [$this->adminEmpresa, $this->vendedorEmpresa] = TenantContext::paraEmpresa(
            $this->empresa->id,
            function () {
                $rolAdmin = Rol::create([
                    'empresa_id' => $this->empresa->id,
                    'nombre' => 'administrador',
                    'es_admin_empresa' => true,
                    'descripcion' => 'Admin',
                    'permisos' => [],
                    'activo' => true,
                ]);

                $rolVendedor = Rol::create([
                    'empresa_id' => $this->empresa->id,
                    'nombre' => 'vendedor',
                    'es_admin_empresa' => false,
                    'descripcion' => 'Vendedor',
                    'permisos' => ['ventas.ver'],
                    'activo' => true,
                ]);

                return [
                    User::factory()->create([
                        'empresa_id' => $this->empresa->id,
                        'rol_id' => $rolAdmin->rol_id,
                    ]),
                    User::factory()->create([
                        'empresa_id' => $this->empresa->id,
                        'rol_id' => $rolVendedor->rol_id,
                    ]),
                ];
            }
        );
    }

    protected function tearDown(): void
    {
        TenantContext::limpiar();

        parent::tearDown();
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
    public function el_operador_puede_editar_los_datos_de_una_empresa(): void
    {
        $this->actingAs($this->super)
            ->put(route('super.empresas.update', $this->empresa->id), [
                'nombre' => 'Cliente Renombrado SRL',
                'cuit' => '30222222222',
                'telefono' => '3764999999',
                'email' => 'contacto@renombrado.test',
                'direccion' => 'San Martín 500',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('empresas', [
            'id' => $this->empresa->id,
            'nombre' => 'Cliente Renombrado SRL',
            'cuit' => '30222222222',
            'direccion' => 'San Martín 500',
        ]);
    }

    /**
     * El slug se usa en claves internas, así que no se edita desde acá
     * aunque venga en el formulario.
     *
     * @test
     */
    public function la_edicion_no_cambia_el_identificador_ni_el_estado_del_acceso(): void
    {
        $slugOriginal = $this->empresa->slug;

        $this->actingAs($this->super)
            ->put(route('super.empresas.update', $this->empresa->id), [
                'nombre' => 'Cliente Original',
                'slug' => 'slug-inyectado',
                'activa' => false,
            ])
            ->assertRedirect();

        $this->empresa->refresh();

        $this->assertSame($slugOriginal, $this->empresa->slug);
        $this->assertTrue($this->empresa->activa, 'Suspender tiene su propia acción auditada');
    }

    /** @test */
    public function no_se_puede_repetir_el_cuit_de_otra_empresa(): void
    {
        $otra = Empresa::create([
            'nombre' => 'Otro Cliente',
            'slug' => 'otro-cliente-'.uniqid(),
            'cuit' => '30333333333',
            'activa' => true,
        ]);

        $this->actingAs($this->super)
            ->put(route('super.empresas.update', $this->empresa->id), [
                'nombre' => 'Cliente Original',
                'cuit' => $otra->cuit,
            ])
            ->assertSessionHasErrors('cuit');

        $this->assertSame('30111111111', $this->empresa->fresh()->cuit);
    }

    /** @test */
    public function el_administrador_de_una_empresa_no_puede_editar_otra_empresa(): void
    {
        $this->actingAs($this->adminEmpresa)
            ->put(route('super.empresas.update', $this->empresa->id), ['nombre' => 'Hackeada'])
            ->assertNotFound();

        $this->assertSame('Cliente Original', $this->empresa->fresh()->nombre);
    }

    /**
     * El caso que motiva la acción: administrador único, bloqueado por
     * intentos fallidos y con la cuenta desactivada. Las tres cosas se
     * resuelven juntas porque por separado no alcanzan para que pueda entrar.
     *
     * @test
     */
    public function el_operador_puede_devolverle_el_acceso_al_administrador(): void
    {
        $passwordAnterior = $this->adminEmpresa->password;

        $this->adminEmpresa->update([
            'bloqueado_hasta' => now()->addDay(),
            'activo' => false,
        ]);

        $respuesta = $this->actingAs($this->super)->post(
            route('super.empresas.restablecer-acceso', [$this->empresa->id, $this->adminEmpresa->id])
        );

        $respuesta->assertRedirect();
        $respuesta->assertSessionHas('password_temporal');

        $this->adminEmpresa->refresh();

        $this->assertNull($this->adminEmpresa->bloqueado_hasta, 'Se levanta el bloqueo');
        $this->assertTrue($this->adminEmpresa->activo, 'Se reactiva la cuenta');
        $this->assertNotSame($passwordAnterior, $this->adminEmpresa->password, 'Cambia la contraseña');

        // La contraseña temporal entregada es la que efectivamente quedó.
        $temporal = session('password_temporal');
        $this->assertTrue(Hash::check($temporal, $this->adminEmpresa->password));
    }

    /**
     * La bitácora de la empresa afectada tiene que mostrar que el operador
     * de la plataforma le tocó el acceso a su administrador.
     *
     * @test
     */
    public function el_restablecimiento_queda_auditado_dentro_de_la_empresa(): void
    {
        $this->actingAs($this->super)->post(
            route('super.empresas.restablecer-acceso', [$this->empresa->id, $this->adminEmpresa->id])
        );

        $this->assertDatabaseHas('auditorias', [
            'empresa_id' => $this->empresa->id,
            'tabla_afectada' => 'users',
            'registro_id' => $this->adminEmpresa->id,
            'accion' => \App\Models\Auditoria::ACCION_RESTABLECER_PASSWORD,
        ]);
    }

    /**
     * Para el resto del personal ya existe /admin/usuarios y es su propio
     * administrador quien debe resolverlo.
     *
     * @test
     */
    public function la_accion_es_solo_para_el_administrador_de_la_empresa(): void
    {
        $this->actingAs($this->super)
            ->post(route('super.empresas.restablecer-acceso', [$this->empresa->id, $this->vendedorEmpresa->id]))
            ->assertStatus(422);
    }

    /** @test */
    public function no_se_puede_restablecer_un_usuario_de_otra_empresa(): void
    {
        $otra = Empresa::create([
            'nombre' => 'Otro Cliente',
            'slug' => 'otro-cliente-'.uniqid(),
            'activa' => true,
        ]);

        $this->actingAs($this->super)
            ->post(route('super.empresas.restablecer-acceso', [$otra->id, $this->adminEmpresa->id]))
            ->assertNotFound();
    }

    /** @test */
    public function un_usuario_de_empresa_no_puede_restablecer_accesos(): void
    {
        $this->actingAs($this->adminEmpresa)
            ->post(route('super.empresas.restablecer-acceso', [$this->empresa->id, $this->adminEmpresa->id]))
            ->assertNotFound();
    }

    /**
     * La contraseña temporal tiene que ser fuerte: la genera el sistema y el
     * cliente la usa una vez, pero mientras exista es una credencial válida.
     *
     * @test
     */
    public function la_contrasena_temporal_es_larga_y_aleatoria(): void
    {
        $generadas = [];

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->super)->post(
                route('super.empresas.restablecer-acceso', [$this->empresa->id, $this->adminEmpresa->id])
            );

            $generadas[] = session('password_temporal');
        }

        foreach ($generadas as $password) {
            $this->assertGreaterThanOrEqual(12, Str::length($password));
        }

        $this->assertCount(3, array_unique($generadas), 'No se puede repetir entre restablecimientos');
    }
}
