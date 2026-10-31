<?php

namespace Tests\Feature\Reportes;

use App\Models\Cliente;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Reparacion;
use App\Models\Rol;
use App\Models\TipoCliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * El prefijo /reportes dejó de validarse por `role:administrador` y pasó a
 * validarse por permiso, igual que el resto del sistema.
 *
 * El punto delicado: al abrir los reportes por permiso, el reporte de
 * reparaciones se convierte en una puerta de atrás al módulo de
 * reparaciones, que SÍ restringe lo que ve cada técnico. Por eso el reporte
 * fuerza el filtro al propio usuario cuando no tiene `reparaciones.ver_todas`,
 * tanto en pantalla como en la exportación.
 */
class AccesoPorPermisoTest extends TestCase
{
    use RefreshDatabase;

    private User $tecnico;

    private User $otroTecnico;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake();

        DB::table('estados_reparacion')->insert([
            ['estadoReparacionID' => 1, 'nombreEstado' => 'Recibido', 'descripcion' => 'Ingresado', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('estados_cliente')->insert([
            ['estadoClienteID' => 1, 'nombreEstado' => 'Activo', 'descripcion' => 'Activo', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $rolTecnico = $this->rol('tecnico', [
            'reparaciones.ver', 'reportes.ver', 'reportes.reparaciones',
        ]);

        $this->tecnico = User::factory()->create(['rol_id' => $rolTecnico->rol_id, 'empresa_id' => 1]);
        $this->otroTecnico = User::factory()->create(['rol_id' => $rolTecnico->rol_id, 'empresa_id' => 1]);

        $marca = Marca::create(['nombre' => 'Samsung', 'activo' => true]);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'A54', 'activo' => true]);
        $tipo = TipoCliente::create(['nombreTipo' => 'Minorista', 'activo' => true]);

        $cliente = Cliente::create([
            'nombre' => 'Juana', 'apellido' => 'Pereyra', 'DNI' => '30123456',
            'tipoClienteID' => $tipo->tipoClienteID, 'estadoClienteID' => 1,
        ]);

        foreach ([[$this->tecnico, 'REP-MIA'], [$this->otroTecnico, 'REP-AJENA']] as [$t, $codigo]) {
            Reparacion::create([
                'clienteID' => $cliente->clienteID,
                'tecnico_id' => $t->id,
                'estado_reparacion_id' => 1,
                'codigo_reparacion' => $codigo,
                'modelo_id' => $modelo->id,
                'falla_declarada' => 'No carga',
                'fecha_ingreso' => now(),
                'total_final' => 10000,
            ]);
        }
    }

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

    /**
     * Antes TODO /reportes exigía el rol administrador, así que estos
     * permisos eran casillas que no hacían nada.
     *
     * @test
     */
    public function un_rol_no_administrador_con_el_permiso_entra_al_reporte(): void
    {
        $this->actingAs($this->tecnico)
            ->get(route('reportes.reparaciones'))
            ->assertOk();
    }

    /**
     * `reportes.ver` es solo la puerta de la sección: no alcanza para entrar
     * a un reporte que tiene su propio permiso.
     *
     * @test
     */
    public function el_permiso_general_no_abre_los_reportes_que_no_corresponden(): void
    {
        $this->actingAs($this->tecnico)->get(route('reportes.ventas'))->assertForbidden();
        $this->actingAs($this->tecnico)->get(route('reportes.mensual'))->assertForbidden();
        $this->actingAs($this->tecnico)->get(route('reportes.compras'))->assertForbidden();
        $this->actingAs($this->tecnico)->get(route('reportes.stock'))->assertForbidden();
    }

    /** @test */
    public function sin_reportes_ver_no_se_entra_a_la_seccion(): void
    {
        $sinReportes = User::factory()->create([
            'rol_id' => $this->rol('deposito', ['stock.ver'])->rol_id,
            'empresa_id' => 1,
        ]);

        $this->actingAs($sinReportes)->get(route('reportes.reparaciones'))->assertForbidden();
    }

    /**
     * ESTE es el test que importa: el reporte no puede ser la puerta de
     * atrás del módulo de reparaciones.
     *
     * @test
     */
    public function el_reporte_de_reparaciones_solo_muestra_las_del_propio_tecnico(): void
    {
        $respuesta = $this->actingAs($this->tecnico)->get(route('reportes.reparaciones'));

        $respuesta->assertOk();

        $props = $respuesta->viewData('page')['props'];

        $this->assertSame(1, $props['kpis']['total'], 'Solo la suya, no las dos');

        $codigos = collect($props['reparaciones']['data'])->pluck('codigo_reparacion')->all();
        $this->assertSame(['REP-MIA'], $codigos);

        $respuesta->assertDontSee('REP-AJENA');
    }

    /**
     * No alcanza con ignorar el tecnico_id recibido: hay que reemplazarlo,
     * porque si no, mandando el de otro se verían las ajenas.
     *
     * @test
     */
    public function el_tecnico_no_puede_pedir_el_reporte_de_un_companero(): void
    {
        $respuesta = $this->actingAs($this->tecnico)
            ->get(route('reportes.reparaciones', ['tecnico_id' => $this->otroTecnico->id]));

        $respuesta->assertOk();

        $props = $respuesta->viewData('page')['props'];

        $this->assertSame(1, $props['kpis']['total']);
        $this->assertSame(
            ['REP-MIA'],
            collect($props['reparaciones']['data'])->pluck('codigo_reparacion')->all(),
            'El filtro se fuerza al usuario, no se toma del request'
        );
    }

    /** @test */
    public function quien_ve_todas_las_reparaciones_ve_el_reporte_completo(): void
    {
        $supervisor = User::factory()->create([
            'rol_id' => $this->rol('supervisor', [
                'reparaciones.ver', 'reparaciones.ver_todas',
                'reportes.ver', 'reportes.reparaciones',
            ])->rol_id,
            'empresa_id' => 1,
        ]);

        $props = $this->actingAs($supervisor)
            ->get(route('reportes.reparaciones'))
            ->viewData('page')['props'];

        $this->assertSame(2, $props['kpis']['total']);
    }

    /**
     * Mirar la pantalla y llevarse el archivo son dos permisos distintos.
     *
     * @test
     */
    public function exportar_exige_su_propio_permiso(): void
    {
        $this->actingAs($this->tecnico)
            ->get(route('reportes.reparaciones.exportar'))
            ->assertForbidden();
    }

    /**
     * /reportes a secas no puede mandar a un 403: va al primer reporte que el
     * usuario efectivamente pueda ver.
     *
     * @test
     */
    public function la_ruta_base_lleva_al_primer_reporte_disponible(): void
    {
        $this->actingAs($this->tecnico)
            ->get('/reportes')
            ->assertRedirect(route('reportes.reparaciones'));
    }
}
