<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo PROVEEDORES (CU-16)
 * 
 * Representa a los proveedores del sistema de compras.
 * 
 * Lineamientos aplicados:
 * - Elmasri: Integridad de Dominio (CUIT CHAR 11, razon_social UNIQUE)
 * - Kendall: Campo calificacion para ranking (CU-21)
 * - Elmasri: Relación 1:N No Identificada con DIRECCIONES
 * - Elmasri: ON DELETE RESTRICT en productos (no borrar si tiene productos asociados)
 * 
 * @property int $id
 * @property string $razon_social
 * @property string $cuit
 * @property string|null $telefono
 * @property string|null $email
 * @property string|null $forma_pago_preferida
 * @property int|null $plazo_entrega_estimado
 * @property float|null $calificacion
 * @property int|null $direccion_id
 * @property bool $activo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Direccion|null $direccion
 * @property-read \Illuminate\Database\Eloquent\Collection<Producto> $productosHabituales
 * @property-read \Illuminate\Database\Eloquent\Collection<SolicitudCotizacion> $solicitudes
 * @property-read \Illuminate\Database\Eloquent\Collection<OfertaCompra> $ofertas
 * @property-read \Illuminate\Database\Eloquent\Collection<OrdenCompra> $ordenesCompra
 */
class Proveedor extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'proveedores';

    /**
     * Get the route key name for Laravel route model binding
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    protected $fillable = [
        'razon_social',
        'cuit',
        'telefono',
        'whatsapp',
        'canal_preferido',
        'email',
        'forma_pago_preferida',
        'plazo_entrega_estimado',
        'calificacion',
        'direccion_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'calificacion' => 'decimal:1',
        'plazo_entrega_estimado' => 'integer',
    ];

    // --- RELACIONES (Elmasri) ---

    /**
     * Relación con DIRECCIONES (1:N No Identificada)
     */
    public function direccion(): BelongsTo
    {
        return $this->belongsTo(Direccion::class, 'direccion_id', 'direccionID');
    }

    /**
     * Productos que tienen a este proveedor como habitual
     * Elmasri: ON DELETE RESTRICT fuerza baja lógica (Kendall CU-19)
     */
    public function productosHabituales(): HasMany
    {
        return $this->hasMany(Producto::class, 'proveedor_habitual_id');
    }

    /**
     * Solicitudes de cotización enviadas a este proveedor
     */
    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudCotizacion::class);
    }

    /**
     * Ofertas presentadas por este proveedor
     */
    public function ofertas(): HasMany
    {
        return $this->hasMany(OfertaCompra::class);
    }

    /**
     * Órdenes de compra generadas a este proveedor
     */
    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(OrdenCompra::class);
    }

    // --- MÉTODOS DE NEGOCIO (Larman: Patrón Experto) ---

    /**
     * Dar de baja lógica (Kendall CU-19)
     * Verifica que no tenga productos asociados
     * 
     * NOTA: Auditoría acoplada por simplicidad del proyecto.
     * En sistemas Enterprise, usar Observers para desacoplar.
     */
    public function darDeBaja(string $motivo): void
    {
        \DB::transaction(function () use ($motivo) {
            // Bloquear el registro para evitar race conditions
            $proveedor = self::lockForUpdate()->find($this->id);
            
            // Verificar excepción de Kendall: productos asociados
            if ($proveedor->productosHabituales()->count() > 0) {
                throw new \Exception(
                    'No se puede dar de baja un proveedor con productos asociados. ' .
                    'Reasigne los productos primero.'
                );
            }

            $datosAnteriores = ['activo' => $proveedor->activo];
            $proveedor->activo = false;
            $proveedor->save();

            // Registrar en auditoría (Kendall: motivo obligatorio)
            Auditoria::create([
                'usuarioID' => auth()->id(),
                'accion' => 'Baja de Proveedor',
                'tabla_afectada' => 'proveedores',
                'registro_id' => $proveedor->id,
                'motivo' => $motivo,
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => ['activo' => false],
            ]);
        });
    }

    /**
     * Actualizar calificación para ranking (CU-21)
     */
    public function actualizarCalificacion(): void
    {
        $estadoElegidaId = \App\Models\EstadoOferta::idPorNombre('Elegida');
        
        // Obtener ofertas elegidas de los últimos 6 meses
        $ofertas = $this->ofertas()
            ->where('estado_id', $estadoElegidaId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->get();

        if ($ofertas->isEmpty()) {
            return;
        }

        // Calcular promedio de scores
        $promedioScore = $ofertas->avg(fn($oferta) => $oferta->calcularScore());
        
        // Normalizar a escala 0-5
        $calificacion = min(5, $promedioScore * 10);
        
        $this->update(['calificacion' => round($calificacion, 1)]);
    }

    /**
     * Verifica si el proveedor está activo
     */
    public function estaActivo(): bool
    {
        return $this->activo === true;
    }

    /**
     * Verifica si el proveedor tiene WhatsApp configurado
     */
    public function tieneWhatsApp(): bool
    {
        return !empty($this->whatsapp) || !empty($this->telefono);
    }

    /**
     * Verifica si el proveedor tiene Email configurado
     */
    public function tieneEmail(): bool
    {
        return !empty($this->email);
    }

    /**
     * Obtiene el número de WhatsApp a usar (prioriza campo whatsapp, luego telefono)
     */
    public function getNumeroWhatsApp(): ?string
    {
        return $this->whatsapp ?: $this->telefono;
    }

    /**
     * Determina los canales disponibles para comunicación
     * @return array ['whatsapp' => bool, 'email' => bool]
     */
    public function canalesDisponibles(): array
    {
        return [
            'whatsapp' => $this->tieneWhatsApp(),
            'email' => $this->tieneEmail(),
        ];
    }
}