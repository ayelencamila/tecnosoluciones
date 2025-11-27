<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reparacion extends Model
{
    use HasFactory;

    protected $table = 'reparaciones';
    protected $primaryKey = 'reparacionID'; // Tu PK personalizada

    protected $fillable = [
        'clienteID',
        'tecnico_id',
        'estado_reparacion_id',
        'codigo_reparacion',
        'equipo_marca',
        'equipo_modelo',
        'numero_serie_imei',
        'clave_bloqueo',
        'accesorios_dejados',
        'falla_declarada',
        'diagnostico_tecnico',
        'observaciones',
        'fecha_ingreso',
        'fecha_promesa',
        'fecha_entrega_real',
        'costo_mano_obra',
        'total_final',
        'anulada',
        'marca_id',
        'modelo_id',
    ];

    protected $casts = [
        'fecha_ingreso' => 'datetime',
        'fecha_promesa' => 'datetime',
        'fecha_entrega_real' => 'datetime',
        'anulada' => 'boolean',
        'costo_mano_obra' => 'decimal:2',
        'total_final' => 'decimal:2',
    ];
    /**
     * El cliente dueño del equipo.
     * Se especifica 'clienteID' porque es la FK en esta tabla y la PK en clientes.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'clienteID', 'clienteID');
    }

    /**
     * El técnico (User) asignado.
     */
    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    /**
     * El estado actual de la reparación.
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoReparacion::class, 'estado_reparacion_id', 'estadoReparacionID');
    }

    /**
     * Los repuestos utilizados en esta reparación.
     */
    public function repuestos(): HasMany
    {
        return $this->hasMany(DetalleReparacion::class, 'reparacion_id');
    }

    /**
     * Las fotos del equipo (ingreso, proceso, salida).
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(ImagenReparacion::class, 'reparacion_id');
    }
    /**
     * Movimientos de stock generados por esta reparación.
     * Útil para auditoría: saber qué se descontó exactamente.
     */
    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'referenciaID', 'reparacionID')
                    ->where('referenciaTabla', 'reparaciones');
    }
    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }
}