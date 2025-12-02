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
        // Campos CU-14: Control de SLA
        'sla_excedido',
        'fecha_marcada_demorada',
        'decision_cliente',
        'fecha_decision_cliente',
    ];

    protected $casts = [
        'fecha_ingreso' => 'datetime',
        'fecha_promesa' => 'datetime',
        'fecha_entrega_real' => 'datetime',
        'anulada' => 'boolean',
        'costo_mano_obra' => 'decimal:2',
        'total_final' => 'decimal:2',
        // Campos CU-14
        'sla_excedido' => 'boolean',
        'fecha_marcada_demorada' => 'datetime',
        'fecha_decision_cliente' => 'datetime',
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
     * Historial de cambios de estado
     * Usado para cálculo preciso de días efectivos de SLA
     */
    public function historialEstados(): HasMany
    {
        return $this->hasMany(HistorialEstadoReparacion::class, 'reparacion_id', 'reparacionID')
                    ->orderBy('fecha_cambio');
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

    /**
     * Alertas de demora asociadas (CU-14)
     */
    public function alertasReparacion(): HasMany
    {
        return $this->hasMany(AlertaReparacion::class, 'reparacionID', 'reparacionID');
    }

    /**
     * Bonificaciones aplicadas por demora (CU-14)
     */
    public function bonificaciones(): HasMany
    {
        return $this->hasMany(BonificacionReparacion::class, 'reparacionID', 'reparacionID');
    }

    /**
     * Bonificación pendiente de aprobación
     */
    public function bonificacionPendiente()
    {
        return $this->hasOne(BonificacionReparacion::class, 'reparacionID', 'reparacionID')
                    ->where('estado', 'pendiente')
                    ->latest();
    }

    /**
     * Obtiene el SLA vigente desde configuración
     * @return int Días de SLA
     */
    public function getSLAVigente(): int
    {
        return (int) Configuracion::get('sla_reparaciones_default', 7);
    }

    /**
     * Calcula los días efectivos transcurridos desde el ingreso
     * Excluye períodos en estados que pausan el SLA
     * 
     * IMPLEMENTACIÓN CORRECTA: Usa historial de estados para calcular
     * con precisión los períodos activos vs pausados
     * 
     * @return int Días efectivos
     */
    public function calcularDiasEfectivos(): int
    {
        if (!$this->fecha_ingreso) {
            return 0;
        }

        // Obtener estados que pausan SLA desde configuración
        $estadosPausaSLA = $this->obtenerEstadosPausaSLA();
        
        // Si no hay estados que pausan SLA, calcular días corridos
        if (empty($estadosPausaSLA)) {
            $fechaFin = $this->fecha_entrega_real ?? now();
            return $this->fecha_ingreso->diffInDays($fechaFin);
        }

        // Obtener historial ordenado cronológicamente
        $historial = $this->historialEstados()->with('estadoNuevo')->get();
        
        // Si no hay historial, usar método simplificado
        if ($historial->isEmpty()) {
            return $this->calcularDiasEfectivosSinHistorial($estadosPausaSLA);
        }

        $fechaFin = $this->fecha_entrega_real ?? now();
        $diasEfectivos = 0;
        $ultimaFechaActiva = $this->fecha_ingreso;
        $enPausa = false;
        
        foreach ($historial as $cambio) {
            $nombreEstadoNuevo = $cambio->estadoNuevo?->nombreEstado;
            
            // Si estaba activo y no entró en pausa, sumar días
            if (!$enPausa) {
                $diasEfectivos += $ultimaFechaActiva->diffInDays($cambio->fecha_cambio);
            }
            
            // Actualizar estado de pausa
            $enPausa = in_array($nombreEstadoNuevo, $estadosPausaSLA);
            $ultimaFechaActiva = $cambio->fecha_cambio;
        }
        
        // Sumar días desde último cambio hasta ahora/entrega (si no está pausado)
        if (!$enPausa) {
            $diasEfectivos += $ultimaFechaActiva->diffInDays($fechaFin);
        }
        
        return (int) $diasEfectivos;
    }

    /**
     * Cálculo simplificado cuando no existe historial de estados
     * (Retrocompatibilidad para reparaciones antiguas)
     */
    private function calcularDiasEfectivosSinHistorial(array $estadosPausaSLA): int
    {
        $estadoActual = $this->estado?->nombreEstado;
        $fechaFin = $this->fecha_entrega_real ?? now();
        
        // Si el estado actual pausa SLA, asumir que siempre estuvo pausado
        // (aproximación conservadora para datos históricos)
        if ($estadoActual && in_array($estadoActual, $estadosPausaSLA)) {
            return 0;
        }
        
        return $this->fecha_ingreso->diffInDays($fechaFin);
    }

    /**
     * Obtiene la lista de estados que pausan el SLA desde configuración
     */
    private function obtenerEstadosPausaSLA(): array
    {
        $estadosPausaSLA = Configuracion::get('estados_pausa_sla', '');
        return array_filter(array_map('trim', explode(',', $estadosPausaSLA)));
    }

    /**
     * Determina si la reparación excede o incumple el SLA
     * 
     * @return array ['excede' => bool, 'incumple' => bool, 'dias_efectivos' => int, 'sla_vigente' => int]
     */
    public function excedeOIncumpleSLA(): array
    {
        $diasEfectivos = $this->calcularDiasEfectivos();
        $slaVigente = $this->getSLAVigente();

        return [
            'dias_efectivos' => $diasEfectivos,
            'sla_vigente' => $slaVigente,
            'excede' => $diasEfectivos > $slaVigente, // Pasó el SLA
            'incumple' => $diasEfectivos > ($slaVigente + 3), // Más de 3 días de exceso
            'dias_excedidos' => max(0, $diasEfectivos - $slaVigente),
        ];
    }

    /**
     * Verifica si la reparación está en un estado que pausa el SLA
     * 
     * @return bool
     */
    public function estaPausada(): bool
    {
        $estadosPausaSLA = Configuracion::get('estados_pausa_sla', '');
        $estadosArray = array_filter(array_map('trim', explode(',', $estadosPausaSLA)));

        if (empty($estadosArray)) {
            return false;
        }

        $estadoActual = $this->estado?->nombreEstado;
        return in_array($estadoActual, $estadosArray);
    }

    /**
     * Marca la reparación como demorada en BD
     */
    public function marcarComoDemorada(): void
    {
        if (!$this->sla_excedido) {
            $this->update([
                'sla_excedido' => true,
                'fecha_marcada_demorada' => now(),
            ]);
        }
    }

    /**
     * Scope: Reparaciones con SLA excedido
     */
    public function scopeConSLAExcedido($query)
    {
        return $query->where('sla_excedido', true);
    }

    /**
     * Scope: Reparaciones sin entregar (en proceso)
     */
    public function scopeSinEntregar($query)
    {
        return $query->whereNull('fecha_entrega_real');
    }

    /**
     * Scope: Reparaciones no anuladas
     */
    public function scopeActivas($query)
    {
        return $query->where('anulada', false);
    }
}