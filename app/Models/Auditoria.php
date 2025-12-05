<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para registrar el historial de operaciones de todo el sistema
 *
 * Esta clase registra todas las operaciones críticas del sistema completo:
 * - Módulo Clientes: altas, modificaciones, bajas
 * - Módulo Reparaciones: crear, modificar estado, finalizar
 * - Módulo Ventas: crear, anular, modificar precios
 * Es fundamental para auditoría y trazabilidad de operaciones transversales.
 *
 * @property int $auditoriaID Identificador único de la auditoría
 * @property int|null $usuarioID Identificador del usuario que realizó la acción (null para sistema)
 * @property string $accion Tipo de acción realizada
 * @property string|null $tabla_afectada Tabla que fue modificada
 * @property int|null $registro_id ID del registro afectado
 * @property array|null $datos_anteriores Datos antes del cambio (JSON)
 * @property array|null $datos_nuevos Datos después del cambio (JSON)
 * @property string|null $motivo Motivo de la operación
 * @property string|null $detalles Detalles adicionales de la operación
 * @property \Carbon\Carbon $created_at Fecha y hora de la operación
 * @property \Carbon\Carbon $updated_at Fecha de última actualización
 * @property-read \App\Models\User|null $usuario Usuario que realizó la operación
 */
class Auditoria extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos
     *
     * @var string
     */
    protected $table = 'auditorias';

    /**
     * Clave primaria personalizada
     *
     * @var string
     */
    protected $primaryKey = 'auditoriaID';

    /**
     * Indica que la clave primaria es auto-incremental
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Tipo de la clave primaria
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Atributos que se pueden asignar de forma masiva
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usuarioID',
        'accion',
        'tabla_afectada',
        'registro_id',
        'datos_anteriores',
        'datos_nuevos',
        'motivo',
        'detalles',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Constantes para tipos de acciones de auditoría del sistema completo
     */

    // Módulo Clientes
    public const ACCION_CREAR_CLIENTE = 'CREAR_CLIENTE';

    public const ACCION_MODIFICAR_CLIENTE = 'MODIFICAR_CLIENTE';

    public const ACCION_BAJA_CLIENTE = 'BAJA_CLIENTE';

    public const ACCION_ALTA_CLIENTE = 'ALTA_CLIENTE';

    public const ACCION_CAMBIAR_TIPO_CLIENTE = 'CAMBIAR_TIPO_CLIENTE';

    public const ACCION_MODIFICAR_CUENTA_CORRIENTE = 'MODIFICAR_CUENTA_CORRIENTE';

    public const ACCION_MODIFICAR_ESTADO_CC = 'MODIFICAR_ESTADO_CC';

    public const ACCION_RESTAURAR_ESTADO_CC = 'RESTAURAR_ESTADO_CC';

    public const ACCION_BLOQUEAR_CC = 'BLOQUEAR_CC';

    public const ACCION_DESBLOQUEAR_CC = 'DESBLOQUEAR_CC';

    public const ACCION_PENDIENTE_APROBACION_CC = 'PENDIENTE_APROBACION_CC';

    public const ACCION_ALERTA_CC = 'ALERTA_CC';

    public const ACCION_MODIFICAR_PARAMETRO_GLOBAL = 'MODIFICAR_PARAMETRO_GLOBAL';

    // Módulo Plantillas WhatsApp (CU-30)
    public const ACCION_MODIFICAR_PLANTILLA_WHATSAPP = 'MODIFICAR_PLANTILLA_WHATSAPP';

    public const ACCION_CREAR_PLANTILLA_WHATSAPP = 'CREAR_PLANTILLA_WHATSAPP';

    public const ACCION_DESHABILITAR_PLANTILLA_WHATSAPP = 'DESHABILITAR_PLANTILLA_WHATSAPP';

    // Módulo Reparaciones (preparado para futuro)
    public const ACCION_CREAR_REPARACION = 'CREAR_REPARACION';

    public const ACCION_MODIFICAR_REPARACION = 'MODIFICAR_REPARACION';

    public const ACCION_CAMBIAR_ESTADO_REPARACION = 'CAMBIAR_ESTADO_REPARACION';

    public const ACCION_FINALIZAR_REPARACION = 'FINALIZAR_REPARACION';

    // Módulo Ventas (preparado para futuro)
    public const ACCION_CREAR_VENTA = 'CREAR_VENTA';

    public const ACCION_MODIFICAR_VENTA = 'MODIFICAR_VENTA';

    public const ACCION_ANULAR_VENTA = 'ANULAR_VENTA';

    public const ACCION_APLICAR_DESCUENTO = 'APLICAR_DESCUENTO';

    // Acciones del sistema
    public const ACCION_LOGIN = 'LOGIN';

    public const ACCION_LOGOUT = 'LOGOUT';

    public const ACCION_ACCESO_DENEGADO = 'ACCESO_DENEGADO';

    /**
     * Obtiene el usuario que realizó la operación auditada
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuarioID', 'id');
    }

    /**
     * Registra una operación de auditoría
     *
     * @param  string  $accion  Tipo de acción realizada
     * @param  string|null  $tabla  Tabla afectada
     * @param  int|null  $registroId  ID del registro afectado
     * @param  array|null  $datosAnteriores  Datos antes del cambio
     * @param  array|null  $datosNuevos  Datos después del cambio
     * @param  string|null  $motivo  Motivo de la operación
     * @param  string|null  $detalles  Detalles adicionales
     * @param  int|null  $usuarioId  ID del usuario (null para operaciones del sistema)
     */
    public static function registrar(
        string $accion,
        ?string $tabla = null,
        ?int $registroId = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?string $motivo = null,
        ?string $detalles = null,
        ?int $usuarioId = null
    ): self {
        return self::create([
            'usuarioID' => $usuarioId ?? auth()->id(),
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'registro_id' => $registroId,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'motivo' => $motivo,
            'detalles' => $detalles,
        ]);
    }

    /**
     * Obtiene el historial de un registro específico
     *
     * @param  string  $tabla  Nombre de la tabla
     * @param  int  $registroId  ID del registro
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function historialRegistro(string $tabla, int $registroId)
    {
        return self::where('tabla_afectada', $tabla)
            ->where('registro_id', $registroId)
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene el historial de un cliente específico
     *
     * @param  int  $clienteId  ID del cliente
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function historialCliente(int $clienteId)
    {
        return self::historialRegistro('clientes', $clienteId);
    }

    /**
     * Obtiene el historial completo del sistema filtrado por criterios
     *
     * @param  array  $filtros  Filtros opcionales (accion, tabla_afectada, fecha_desde, fecha_hasta)
     * @param  int  $limite  Límite de registros a devolver
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function historialSistema(array $filtros = [], int $limite = 100)
    {
        $query = self::with('usuario')->orderBy('created_at', 'desc');

        if (isset($filtros['accion'])) {
            $query->where('accion', $filtros['accion']);
        }

        if (isset($filtros['tabla_afectada'])) {
            $query->where('tabla_afectada', $filtros['tabla_afectada']);
        }

        if (isset($filtros['fecha_desde'])) {
            $query->where('created_at', '>=', $filtros['fecha_desde']);
        }

        if (isset($filtros['fecha_hasta'])) {
            $query->where('created_at', '<=', $filtros['fecha_hasta']);
        }

        return $query->limit($limite)->get();
    }
}
