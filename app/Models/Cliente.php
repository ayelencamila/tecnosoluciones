<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\EstadoCuentaCorriente;
use Illuminate\Support\Facades\DB;

/**
 * Modelo principal para gestionar los clientes del sistema
 */
class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';
    protected $primaryKey = 'clienteID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'apellido',
        'DNI',
        'mail',
        'whatsapp',
        'telefono',
        'tipoClienteID',
        'estadoClienteID',
        'direccionID',
        'cuentaCorrienteID',
    ];

    // --- RELACIONES ---

    public function tipoCliente(): BelongsTo
    {
        return $this->belongsTo(TipoCliente::class, 'tipoClienteID', 'tipoClienteID');
    }

    public function estadoCliente(): BelongsTo
    {
        return $this->belongsTo(EstadoCliente::class, 'estadoClienteID', 'estadoClienteID');
    }

    public function direccion(): BelongsTo
    {
        return $this->belongsTo(Direccion::class, 'direccionID', 'direccionID');
    }

    public function cuentaCorriente(): BelongsTo
    {
        return $this->belongsTo(CuentaCorriente::class, 'cuentaCorrienteID', 'cuentaCorrienteID');
    }

    /**
     * Historial de reparaciones del cliente.
     */
    public function reparaciones(): HasMany
    {
        return $this->hasMany(Reparacion::class, 'clienteID', 'clienteID');
    }

    /**
     * Ventas del cliente.
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'clienteID', 'clienteID');
    }

    // --- ACCESORS (get...Attribute) ---

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    public function getSaldoAttribute(): float
    {
        return $this->cuentaCorriente?->saldo ?? 0.0;
    }

    public function getDireccionCompletaAttribute(): string
    {
        return $this->direccion?->direccion_completa_con_ubicacion ?? 'Sin dirección';
    }

    public function getCreditoDisponibleAttribute(): float
    {
        $limite = $this->cuentaCorriente?->limiteCredito ?? 0;
        $saldo = $this->saldo;
        return $limite + $saldo;
    }

    // --- MÉTODOS DE LÓGICA (Helpers) ---

    public function esMayorista(): bool
    {
        return $this->tipoCliente?->esMayorista() ?? false;
    }

    public function esMinorista(): bool
    {
        return $this->tipoCliente?->esMinorista() ?? false;
    }

    public function estaActivo(): bool
    {
        return $this->estadoCliente?->nombreEstado === 'Activo';
    }

    public function tieneDeudas(): bool
    {
        return $this->saldo < 0;
    }

    public function historialAuditoria()
    {
        return Auditoria::historialCliente($this->clienteID);
    }

    public function puedeSerDadoDeBaja(): bool
    {
        // CU-04 Excepción 4a: No puede darse de baja si tiene operaciones activas pendientes
        
        // 1. Verificar deudas
        if ($this->tieneDeudas()) {
            return false;
        }

        // 2. Verificar ventas pendientes de pago
        $tieneVentasPendientes = $this->ventas()
            ->whereHas('estado', function($q) {
                $q->where('nombre', 'Pendiente');
            })
            ->exists();
        if ($tieneVentasPendientes) {
            return false;
        }

        // 3. Verificar reparaciones en curso (CU-04 Paso 4)
        $tieneReparacionesPendientes = $this->reparaciones()
            ->whereHas('estadoReparacion', function($q) {
                $q->whereNotIn('nombre', ['Cancelada', 'Entregada']);
            })
            ->exists();
        if ($tieneReparacionesPendientes) {
            return false;
        }

        return true;
    }

    // --- MÉTODOS DE NEGOCIO (Larman's Expert Principle) ---

    /**
     * Da de baja al cliente de forma atómica.
     * (CU-04 Lógica Centralizada con Transacción)
     */
    public function darDeBaja(string $motivo): bool
    {
        if (!$this->puedeSerDadoDeBaja()) {
            throw new \Exception('El cliente tiene operaciones pendientes y no puede ser dado de baja.');
        }

        return DB::transaction(function () use ($motivo) {
            $datosAnteriores = $this->toArray();

            $estadoInactivo = EstadoCliente::inactivo();
            
            $this->estadoClienteID = $estadoInactivo->estadoClienteID;
            $this->save();

            // Deshabilitar su Cuenta Corriente (CU-04 Paso 9)
            if ($this->cuentaCorriente) {
                $this->cuentaCorriente()->update([
                    'estadoCuentaCorrienteID' => EstadoCuentaCorriente::inactiva()->estadoCuentaCorrienteID
                ]);
            }

            // Registrar auditoría manualmente (para incluir el motivo)
            Auditoria::registrar(
                Auditoria::ACCION_BAJA_CLIENTE,
                'clientes',
                $this->clienteID,
                $datosAnteriores,
                $this->fresh()->toArray(),
                $motivo,
                "Cliente dado de baja: {$this->nombre_completo}"
            );

            return true;
        });
    }

    /**
     * Reactiva un cliente que estaba Inactivo de forma atómica.
     */
    public function reactivar(string $motivo = 'Cliente reactivado'): bool
    {
        return DB::transaction(function () use ($motivo) {
            $datosAnteriores = $this->toArray();
            
            $estadoActivo = EstadoCliente::activo();
            
            $this->estadoClienteID = $estadoActivo->estadoClienteID;
            $this->save();

            // Habilitar su Cuenta Corriente (si existe)
            if ($this->cuentaCorriente) {
                $this->cuentaCorriente()->update([
                    'estadoCuentaCorrienteID' => EstadoCuentaCorriente::activa()->estadoCuentaCorrienteID
                ]);
            }

            // Registrar auditoría manualmente (para incluir el motivo)
            Auditoria::registrar(
                Auditoria::ACCION_ALTA_CLIENTE,
                'clientes',
                $this->clienteID,
                $datosAnteriores,
                $this->fresh()->toArray(),
                $motivo,
                "Cliente reactivado: {$this->nombre_completo}"
            );
            return true;
        });
    }

    /**
     * Registra eventos de auditoría (CU-01, CU-02)
     */
    protected static function boot()
    {
        parent::boot();

        // CU-01: Registrar Cliente
        static::created(function ($cliente) {
            Auditoria::registrar(
                Auditoria::ACCION_CREAR_CLIENTE,
                'clientes',
                $cliente->clienteID,
                null,
                $cliente->toArray(),
                null,
                "Cliente registrado: {$cliente->nombre_completo}"
            );
        });

        // CU-02: Modificar Cliente
        static::updated(function ($cliente) {
            // INTELIGENCIA: Si el único cambio fue el estado...
            if ($cliente->wasChanged('estadoClienteID') && count($cliente->getChanges()) === 1) {
                 // ...lo ignoramos aquí, porque 'darDeBaja' o 'reactivar'
                 // ya manejaron la auditoría (que SÍ tiene el 'motivo').
                 return;
            }

            // Si fue una modificación normal (ej: cambió el teléfono), auditamos.
            $original = $cliente->getOriginal();
            Auditoria::registrar(
                Auditoria::ACCION_MODIFICAR_CLIENTE,
                'clientes',
                $cliente->clienteID,
                $original,
                $cliente->toArray(),
                null, 
                "Cliente modificado: {$cliente->nombre_completo}"
            );
        });
    }
}
