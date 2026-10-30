<?php

namespace Tests\Feature\Reparaciones;

use App\Models\Cliente;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Reparacion;
use App\Models\Rol;
use App\Models\TipoCliente;
use App\Models\User;
use App\Services\Reparaciones\MonitoreoSLAReparacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Un técnico ve únicamente las reparaciones que tiene asignadas; quien tiene
 * `reparaciones.ver_todas` ve el tablero completo.
 *
 * El sistema ya trabajaba así en las alertas de SLA (filtradas por técnico,
 * con 403 en las ajenas) y en el tablero de control ("Mis Reparaciones"). El
 * listado del módulo era el único lugar que mostraba todo, y eso es lo que se
 * corrige acá.
 *
 * La restricción es un filtro EXPLÍCITO en el controlador, nunca un global
 * scope: el monitoreo de SLA y las notificaciones corren desde la consola sin
 * usuario autenticado y tienen que seguir viendo todas las reparaciones. El
 * último test de esta clase es el que custodia justamente eso.
 */
class VisibilidadPorTecnicoTest extends TestCase
{
    use RefreshDatabase;

    private User $tecnicoUno;

    private User $tecnicoDos;

    private User $vendedor;

    private Reparacion $reparacionDeUno;

    private Reparacion $reparacionDeDos;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake();

        DB::table('estados_reparacion')->insert([
            ['estadoReparacionID' => 1, 'nombreEstado' => 'Recibido', 'descripcion' => 'Ingresado', 'created_at' => now(), 'updated_at' => now()],
            ['estadoReparacionID' => 2, 'nombreEstado' => 'En Reparación', 'descripcion' => 'En curso', 'created_at' => now(), 'updated_at' => now()],
            ['estadoReparacionID' => 5, 'nombreEstado' => 'Entregado', 'descripcion' => 'Final', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('estados_cliente')->insert([
            ['estadoClienteID' => 1, 'nombreEstado' => 'Activo', 'descripcion' => 'Activo', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Técnicos: `reparaciones.ver` pero NO `ver_todas`.
        $rolTecnico = $this->rol('tecnico', ['reparaciones.ver', 'reparaciones.editar', 'dashboard.ver']);

        // Mostrador: atiende al público, así que necesita el tablero completo.
        $rolVendedor = $this->rol('vendedor', [
            'reparaciones.ver', 'reparaciones.ver_todas', 'reparaciones.crear', 'dashboard.ver',
        ]);

        $this->tecnicoUno = User::factory()->create(['rol_id' => $rolTecnico->rol_id, 'empresa_id' => 1]);
        $this->tecnicoDos = User::factory()->create(['rol_id' => $rolTecnico->rol_id, 'empresa_id' => 1]);
        $this->vendedor = User::factory()->create(['rol_id' => $rolVendedor->rol_id, 'empresa_id' => 1]);

        $marca = Marca::create(['nombre' => 'Samsung', 'activo' => true]);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'Galaxy S24', 'activo' => true]);
        $tipo = TipoCliente::create(['nombreTipo' => 'Minorista', 'descripcion' => 'General', 'activo' => true]);

        $cliente = Cliente::create([
            'nombre' => 'Juana',
            'apellido' => 'Pereyra',
            'DNI' => '30123456',
            'whatsapp' => '3764123456',
            'tipoClienteID' => $tipo->tipoClienteID,
            'estadoClienteID' => 1,
        ]);

        $this->reparacionDeUno = $this->reparacion($cliente, $modelo, $this->tecnicoUno, 'REP-UNO');
        $this->reparacionDeDos = $this->reparacion($cliente, $modelo, $this->tecnicoDos, 'REP-DOS');
    }

    /**
     * Las migraciones ya dejan creados los roles base de la empresa 1, así
     * que se los toma y se les fija el juego de permisos que este test
     * necesita, en vez de intentar crearlos de nuevo.
     */
    private function rol(string $nombre, array $permisos): Rol
    {
        return Rol::updateOrCreate(
            ['empresa_id' => 1, 'nombre' => $nombre],
            [
                'es_admin_empresa' => false,
                'descripcion' => $nombre,
                'permisos' => $permisos,
                'activo' => true,
            ]
        );
    }

    private function reparacion(Cliente $cliente, Modelo $modelo, User $tecnico, string $codigo): Reparacion
    {
        return Reparacion::create([
            'clienteID' => $cliente->clienteID,
            'tecnico_id' => $tecnico->id,
            'estado_reparacion_id' => 1,
            'codigo_reparacion' => $codigo,
            'modelo_id' => $modelo->id,
            'falla_declarada' => 'No carga',
            'fecha_ingreso' => now()->subDays(10),
            'total_final' => 10000,
        ]);
    }

    /** Códigos de reparación que el listado le devuelve a un usuario. */
    private function codigosVisiblesPara(User $usuario): array
    {
        $respuesta = $this->actingAs($usuario)->get(route('reparaciones.index'));
        $respuesta->assertOk();

        return collect($respuesta->viewData('page')['props']['reparaciones']['data'])
            ->pluck('codigo')
            ->all();
    }

    /** @test */
    public function el_tecnico_solo_ve_en_el_listado_las_reparaciones_que_tiene_asignadas(): void
    {
        $this->assertSame(['REP-UNO'], $this->codigosVisiblesPara($this->tecnicoUno));
        $this->assertSame(['REP-DOS'], $this->codigosVisiblesPara($this->tecnicoDos));
    }

    /** @test */
    public function quien_tiene_ver_todas_sigue_viendo_el_tablero_completo(): void
    {
        $this->assertEqualsCanonicalizing(
            ['REP-UNO', 'REP-DOS'],
            $this->codigosVisiblesPara($this->vendedor),
            'El mostrador tiene que poder responder por cualquier equipo'
        );
    }

    /**
     * De poco serviría ocultarla del listado si se llega escribiendo el id.
     *
     * @test
     */
    public function el_tecnico_no_puede_abrir_la_reparacion_de_un_companero_por_url(): void
    {
        $ajena = $this->reparacionDeDos->reparacionID;

        $this->actingAs($this->tecnicoUno)->get(route('reparaciones.show', $ajena))->assertNotFound();
        $this->actingAs($this->tecnicoUno)->get(route('reparaciones.edit', $ajena))->assertNotFound();
        $this->actingAs($this->tecnicoUno)
            ->get(route('reparaciones.imprimir-ingreso', $ajena))
            ->assertNotFound();

        // La propia sigue abriéndose con normalidad.
        $this->actingAs($this->tecnicoUno)
            ->get(route('reparaciones.show', $this->reparacionDeUno->reparacionID))
            ->assertOk();
    }

    /** @test */
    public function el_tecnico_no_puede_modificar_la_reparacion_de_un_companero(): void
    {
        $this->actingAs($this->tecnicoUno)
            ->put(route('reparaciones.update', $this->reparacionDeDos->reparacionID), [
                'estado_reparacion_id' => 2,
                'diagnostico_tecnico' => 'Intervención no autorizada',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('reparaciones', [
            'reparacionID' => $this->reparacionDeDos->reparacionID,
            'estado_reparacion_id' => 1,
            'diagnostico_tecnico' => null,
        ]);
    }

    /** @test */
    public function el_tablero_del_tecnico_solo_cuenta_sus_reparaciones(): void
    {
        $props = $this->actingAs($this->tecnicoUno)
            ->get(route('dashboard'))
            ->viewData('page')['props'];

        $this->assertSame(1, $props['kpis']['misReparaciones']);

        $this->assertSame(
            ['REP-UNO'],
            collect($props['tablas']['ultimasReparaciones'])->pluck('id')
                ->map(fn ($id) => Reparacion::withoutGlobalScopes()->find($id)->codigo_reparacion)
                ->all()
        );

        // Los contadores del taller son volúmenes de trabajo, no datos de una
        // persona, y se dejan a propósito con el total: al técnico le sirve
        // saber cuántos equipos hay esperando.
        $this->assertSame(2, $props['kpis']['reparacionesEnCurso']);
    }

    /**
     * ESTE es el test que protege los procesos automáticos.
     *
     * El monitoreo de SLA corre desde el scheduler, sin usuario autenticado.
     * Si la restricción por técnico se hubiera implementado como global scope
     * sobre Reparacion, acá se verificarían cero reparaciones y las alertas
     * dejarían de generarse en silencio.
     *
     * @test
     */
    public function el_monitoreo_automatico_de_sla_sigue_viendo_todas_las_reparaciones(): void
    {
        // Sin actingAs: así corre el scheduler en producción.
        $this->assertGuest();

        $stats = app(MonitoreoSLAReparacionService::class)->verificarYGenerarAlertas();

        $this->assertSame(2, $stats['total_verificadas'], 'El monitoreo tiene que ver las de todos los técnicos');
    }

    /**
     * Y tampoco debe depender de quién fue el último que inició sesión: el
     * comando se ejecuta por empresa, no por usuario.
     *
     * @test
     */
    public function el_monitoreo_no_se_recorta_por_el_usuario_autenticado(): void
    {
        $this->actingAs($this->tecnicoUno);

        $stats = app(MonitoreoSLAReparacionService::class)->verificarYGenerarAlertas();

        $this->assertSame(2, $stats['total_verificadas']);
    }
}
