<?php

namespace App\Services\Plataforma;

use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\PlantillaWhatsapp;
use App\Models\Rol;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Alta de un nuevo cliente del sistema (tenant), como operacion atomica.
 *
 * Una empresa sin rol administrador y sin usuario administrador es
 * inutilizable: nadie puede entrar a configurarla. Eso es exactamente lo
 * que paso con `ZZ_TEST_EMPRESA_2`, creada a mano y abandonada sin roles ni
 * usuarios. Por eso las tres cosas se crean dentro de una unica transaccion:
 * o queda una empresa operativa, o no queda nada.
 *
 * Todo el proceso corre con TenantContext apuntando a la empresa nueva, de
 * modo que el Global Scope y el autocompletado de `empresa_id` del trait
 * atribuyan correctamente cada fila al tenant que se esta creando, aunque
 * quien ejecute la operacion sea el super usuario (que no tiene empresa) o
 * la consola (que no tiene usuario).
 */
class AltaEmpresaService
{
    /**
     * Parametros operativos con los que arranca una empresa nueva.
     *
     * No se reutiliza ConfiguracionSeeder porque ese seeder carga datos de
     * demostracion (nombre "TecnoSoluciones", CUIT y telefono de ejemplo)
     * que un cliente real tendria que limpiar a mano. Aca van solo los
     * parametros de operacion, con valores neutros y conservadores: la
     * identidad de la empresa vive en las columnas de `empresas`.
     */
    private const PARAMETROS_INICIALES = [
        // Ventas
        'dias_maximos_anulacion_venta'    => ['7', 'Días máximos para anular una venta.'],
        'permitir_venta_sin_stock'        => ['false', 'Permitir ventas con stock cero.'],

        // Stock
        'stock_minimo_global'             => ['5', 'Alerta de stock bajo por defecto.'],
        'alerta_stock_bajo'               => ['true', 'Activar alertas visuales de stock.'],

        // Cuentas corrientes
        'dias_gracia_global'              => ['30', 'Días de gracia por defecto.'],
        'limite_credito_global'           => ['100000', 'Límite de crédito por defecto (ARS).'],
        'politicaAutoBlock'               => ['true', 'Bloqueo automático ante incumplimientos.'],

        // Compras. La generacion automatica arranca APAGADA: una empresa
        // nueva no deberia empezar a mandarle solicitudes a proveedores que
        // todavia no cargo.
        'compras_generacion_automatica'   => ['false', 'Generar solicitudes de cotización automáticamente ante stock bajo.'],
        'compras_dias_vencimiento'        => ['7', 'Días de vencimiento por defecto para solicitudes de cotización.'],
        'compras_dias_recordatorio'       => ['2', 'Días desde el envío para enviar recordatorio a proveedores.'],
        'compras_max_recordatorios'       => ['3', 'Cantidad máxima de recordatorios por solicitud.'],

        // Reparaciones y SLA
        'sla_reparaciones_default'        => ['5', 'Días de SLA por defecto para las reparaciones.'],
        'estados_pausa_sla'               => ['Espera de Repuesto,Reparado', 'Estados que pausan el conteo de días de SLA.'],
        'bonificacion_demora_habilitada'  => ['true', 'Habilitar bonificaciones automáticas por demora.'],
        'bonificacion_1_3_dias'           => ['10', 'Bonificación por demora de 1 a 3 días (%).'],
        'bonificacion_4_7_dias'           => ['20', 'Bonificación por demora de 4 a 7 días (%).'],
        'bonificacion_mas_7_dias'         => ['30', 'Bonificación por demora de más de 7 días (%).'],
        'bonificacion_tope_maximo'        => ['40', 'Tope máximo de bonificación (%).'],

        // WhatsApp. Arranca APAGADO hasta que la empresa cargue su numero.
        'whatsapp_activo'                 => ['false', 'Activar envío de WhatsApp.'],
        'whatsapp_horario_inicio'         => ['09:00', 'Hora de inicio para notificaciones.'],
        'whatsapp_horario_fin'            => ['20:00', 'Hora de fin para notificaciones.'],
        'whatsapp_reintentos_maximos'     => ['3', 'Intentos de reenvío.'],
        'whatsapp_admin_notificaciones'   => ['', 'WhatsApp del administrador para alertas críticas.'],
    ];

    /**
     * Crea la empresa con su rol administrador y su usuario administrador.
     *
     * @param  array{nombre:string,cuit?:?string,telefono?:?string,email?:?string,direccion?:?string}  $datosEmpresa
     * @param  array{name:string,email:string,password:string,telefono?:?string}  $datosAdmin
     */
    public function ejecutar(array $datosEmpresa, array $datosAdmin): Empresa
    {
        return DB::transaction(function () use ($datosEmpresa, $datosAdmin) {
            $empresa = Empresa::create([
                'nombre'    => $datosEmpresa['nombre'],
                'slug'      => $this->slugUnico($datosEmpresa['nombre']),
                'cuit'      => isset($datosEmpresa['cuit']) ? preg_replace('/\D/', '', $datosEmpresa['cuit']) : null,
                'telefono'  => $datosEmpresa['telefono']  ?? null,
                'email'     => $datosEmpresa['email']     ?? null,
                'direccion' => $datosEmpresa['direccion'] ?? null,
                'activa'    => true,
            ]);

            // A partir de aca, todo se crea dentro del tenant nuevo.
            TenantContext::paraEmpresa($empresa->id, function () use ($empresa, $datosAdmin) {
                $rolAdmin = Rol::create([
                    'empresa_id'       => $empresa->id,
                    'nombre'           => 'administrador',
                    'es_admin_empresa' => true,
                    'descripcion'      => 'Administra la empresa: usuarios, roles y configuración.',
                    'permisos'         => $this->todosLosPermisos(),
                    'activo'           => true,
                ]);

                User::create([
                    'empresa_id' => $empresa->id,
                    'rol_id'     => $rolAdmin->rol_id,
                    'name'       => $datosAdmin['name'],
                    'email'      => $datosAdmin['email'],
                    'telefono'   => $datosAdmin['telefono'] ?? null,
                    'password'   => Hash::make($datosAdmin['password']),
                    'activo'     => true,
                ]);

                $this->crearParametrosIniciales($empresa);
                $this->copiarPlantillasBase();

                // La auditoria se registra DENTRO del contexto de la empresa
                // nueva para que el asiento quede atribuido a ella y no al
                // contexto del super usuario, que no tiene empresa propia.
                Auditoria::registrar(
                    Auditoria::ACCION_CREAR_EMPRESA,
                    'empresas',
                    $empresa->id,
                    null,
                    $empresa->only(['nombre', 'slug', 'cuit', 'email', 'activa']),
                    "Alta de empresa '{$empresa->nombre}' desde el panel de plataforma"
                );
            });

            return $empresa;
        });
    }

    /**
     * Catalogo completo de permisos, leido de config/permisos.php para que el
     * rol administrador no quede desactualizado cuando se agreguen modulos.
     */
    private function todosLosPermisos(): array
    {
        $permisos = [];

        foreach (config('permisos.modulos', []) as $modulo) {
            $permisos = array_merge($permisos, array_keys($modulo['permisos'] ?? []));
        }

        return array_values(array_unique($permisos));
    }

    private function crearParametrosIniciales(Empresa $empresa): void
    {
        // Insercion directa para no depender de Configuracion::set(), que
        // ademas de guardar invalida cache y escribe auditoria por cada clave.
        $ahora = now();
        $filas = [];

        foreach (self::PARAMETROS_INICIALES as $clave => [$valor, $descripcion]) {
            $filas[] = [
                'empresa_id'  => $empresa->id,
                'clave'       => $clave,
                'valor'       => $valor,
                'descripcion' => $descripcion,
                'created_at'  => $ahora,
                'updated_at'  => $ahora,
            ];
        }

        // Identidad: se copia de las columnas tipadas de `empresas` para que
        // los comprobantes y notificaciones salgan con los datos correctos.
        foreach ([
            'nombre_empresa'    => $empresa->nombre,
            'cuit_empresa'      => $empresa->cuit,
            'email_contacto'    => $empresa->email,
            'direccion_empresa' => $empresa->direccion,
        ] as $clave => $valor) {
            $filas[] = [
                'empresa_id'  => $empresa->id,
                'clave'       => $clave,
                'valor'       => (string) ($valor ?? ''),
                'descripcion' => 'Dato de identidad de la empresa.',
                'created_at'  => $ahora,
                'updated_at'  => $ahora,
            ];
        }

        DB::table('configuracion')->insert($filas);
    }

    /**
     * Replica las plantillas de WhatsApp base para la empresa nueva.
     *
     * A diferencia de los parametros, las plantillas son texto generico con
     * marcadores ({{cliente}}, {{equipo}}), por lo que el punto de partida es
     * legitimamente el mismo para todas las empresas. Se toman de la empresa
     * default; si no hubiera ninguna, la empresa arranca sin plantillas y las
     * carga desde su propio panel.
     */
    private function copiarPlantillasBase(): void
    {
        $plantillasBase = TenantContext::paraEmpresa(
            1,
            fn () => PlantillaWhatsapp::all()
        );

        foreach ($plantillasBase as $base) {
            // `usuario_modificacion` se excluye porque apunta a un usuario de
            // la empresa default: copiarlo dejaria una FK cruzada entre
            // tenants. `empresa_id` lo completa el trait BelongsToEmpresa.
            $copia = $base->replicate(['empresa_id', 'usuario_modificacion', 'motivo_modificacion']);
            $copia->save();
        }
    }

    /**
     * Garantiza unicidad del slug, que es el identificador url-safe del tenant.
     */
    private function slugUnico(string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'empresa';
        $slug = $base;
        $sufijo = 2;

        while (Empresa::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$sufijo}";
            $sufijo++;
        }

        return $slug;
    }
}
