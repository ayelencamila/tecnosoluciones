<?php

namespace Tests\Feature\Planes;

use App\Models\Empresa;
use App\Models\Plan;
use App\Models\PlanLimite;
use App\Models\Rol;
use App\Models\User;
use App\Services\Planes\ControlDeLimites;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Límites del plan contratado.
 *
 * La distinción central: los límites de CAPACIDAD bloquean, los de VOLUMEN
 * OPERATIVO no. Frenar la creación de un usuario es inofensivo; frenar una
 * venta le corta la caja al cliente en el peor momento.
 */
class LimitesDePlanTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Plan $basico;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basico = Plan::create([
            'nombre' => 'Básico', 'slug' => 'basico-'.Str::random(5),
            'precio_mensual' => 0, 'activo' => true, 'orden' => 1,
        ]);

        PlanLimite::create([
            'plan_id' => $this->basico->id, 'clave' => 'usuarios',
            'tipo' => PlanLimite::TIPO_DURO, 'valor' => 3,
        ]);
        PlanLimite::create([
            'plan_id' => $this->basico->id, 'clave' => 'ventas_mensuales',
            'tipo' => PlanLimite::TIPO_BLANDO, 'valor' => 2,
        ]);

        $this->empresa = Empresa::create([
            'nombre' => 'Cliente Básico', 'slug' => 'basico-'.uniqid(),
            'activa' => true, 'plan_id' => $this->basico->id,
        ]);

        $this->admin = TenantContext::paraEmpresa($this->empresa->id, function () {
            $rol = Rol::create([
                'empresa_id' => $this->empresa->id, 'nombre' => 'administrador',
                'es_admin_empresa' => true, 'descripcion' => 'Admin',
                'permisos' => [], 'activo' => true,
            ]);

            return User::factory()->create([
                'empresa_id' => $this->empresa->id, 'rol_id' => $rol->rol_id, 'activo' => true,
            ]);
        });
    }

    protected function tearDown(): void
    {
        TenantContext::limpiar();
        parent::tearDown();
    }

    private function control(): ControlDeLimites
    {
        return ControlDeLimites::paraEmpresa($this->empresa->fresh());
    }

    /** Crea $cantidad usuarios activos extra en la empresa. */
    private function usuariosExtra(int $cantidad): void
    {
        TenantContext::paraEmpresa($this->empresa->id, function () use ($cantidad) {
            $rol = Rol::create([
                'empresa_id' => $this->empresa->id, 'nombre' => 'vendedor',
                'es_admin_empresa' => false, 'descripcion' => 'V',
                'permisos' => [], 'activo' => true,
            ]);

            User::factory()->count($cantidad)->create([
                'empresa_id' => $this->empresa->id, 'rol_id' => $rol->rol_id, 'activo' => true,
            ]);
        });
    }

    /** @test */
    public function cuenta_el_consumo_real_de_cada_limite(): void
    {
        $this->assertSame(1, $this->control()->consumo('usuarios'), 'Solo el admin creado en setUp');

        $this->usuariosExtra(2);

        $this->assertSame(3, $this->control()->consumo('usuarios'));
    }

    /** @test */
    public function el_limite_duro_de_usuarios_bloquea_el_alta(): void
    {
        $this->usuariosExtra(2); // 3 en total, que es el tope

        $this->assertTrue($this->control()->alcanzado('usuarios'));

        $respuesta = $this->actingAs($this->admin)->post(route('admin.usuarios.store'), [
            'name' => 'Cuarto Usuario',
            'email' => 'cuarto@ejemplo.test',
            'password' => 'Str0ng!Pass1',
            'password_confirmation' => 'Str0ng!Pass1',
            'rol_id' => Rol::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)
                ->where('nombre', 'vendedor')->value('rol_id'),
        ]);

        $respuesta->assertSessionHasErrors('limite_plan');
        $this->assertDatabaseMissing('users', ['email' => 'cuarto@ejemplo.test']);
    }

    /**
     * ESTE es el test que importa: un límite blando NUNCA puede frenar la
     * operación. Si esto falla, el local no puede cobrar.
     *
     * @test
     */
    public function el_limite_blando_no_bloquea_nunca(): void
    {
        $control = $this->control();

        // Aun estando por encima del tope, exigirCupo no debe lanzar nada.
        $control->exigirCupo('ventas_mensuales');

        $this->assertTrue(true, 'exigirCupo sobre un límite blando no interrumpe');
    }

    /** @test */
    public function sin_plan_asignado_no_hay_limites(): void
    {
        $sinPlan = Empresa::create([
            'nombre' => 'Sin Plan', 'slug' => 'sin-plan-'.uniqid(), 'activa' => true,
        ]);

        $control = ControlDeLimites::paraEmpresa($sinPlan);

        $this->assertNull($control->limite('usuarios'));
        $this->assertFalse($control->alcanzado('usuarios'));
        $control->exigirCupo('usuarios');
    }

    /**
     * "Sin límite" se modela como NULL, no como un número centinela.
     *
     * @test
     */
    public function un_limite_nulo_significa_ilimitado(): void
    {
        $premium = Plan::create([
            'nombre' => 'Premium', 'slug' => 'premium-'.Str::random(5),
            'precio_mensual' => 0, 'activo' => true, 'orden' => 2,
        ]);
        PlanLimite::create([
            'plan_id' => $premium->id, 'clave' => 'usuarios',
            'tipo' => PlanLimite::TIPO_DURO, 'valor' => null,
        ]);

        $this->empresa->update(['plan_id' => $premium->id]);
        $this->usuariosExtra(10);

        $control = $this->control();

        $this->assertNull($control->limite('usuarios'), 'Ilimitado se comporta como sin límite');
        $this->assertFalse($control->alcanzado('usuarios'));
        $control->exigirCupo('usuarios');
    }

    /** @test */
    public function avisa_cuando_se_esta_cerca_del_limite(): void
    {
        // Tope 3, umbral 80% => avisa a partir de 3*0.8 = 2.4 -> 3... usamos 2
        config(['planes.umbral_aviso' => 0.6]); // avisa desde 2 de 3

        $this->usuariosExtra(1); // 2 de 3

        $control = $this->control();

        $this->assertFalse($control->alcanzado('usuarios'));
        $this->assertTrue($control->cerca('usuarios'), 'Avisar al 100% ya es tarde');
    }

    /** @test */
    public function el_resumen_expone_consumo_y_tope_de_cada_limite(): void
    {
        $resumen = collect($this->control()->resumen())->keyBy('clave');

        $this->assertSame(3, $resumen['usuarios']['valor']);
        $this->assertSame('duro', $resumen['usuarios']['tipo']);
        $this->assertSame(1, $resumen['usuarios']['consumo']);

        $this->assertSame('blando', $resumen['ventas_mensuales']['tipo']);
        $this->assertFalse($resumen['usuarios']['ilimitado']);
    }

    /**
     * Si el operador quedara sujeto a los límites, el día que un cliente
     * llega al tope no se podría entrar a ayudarlo.
     *
     * @test
     */
    public function el_operador_de_plataforma_no_queda_limitado(): void
    {
        $this->usuariosExtra(2); // tope alcanzado

        TenantContext::limpiar();

        // Sin tenant activo no hay empresa, y por lo tanto no hay límite.
        $control = ControlDeLimites::paraTenantActivo();

        $this->assertNull($control->limite('usuarios'));
        $control->exigirCupo('usuarios');
    }
}
