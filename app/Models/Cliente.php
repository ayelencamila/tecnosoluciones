<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo principal para gestionar los clientes del sistema
 * 
 * Esta clase representa a los clientes del negocio, tanto mayoristas como minoristas.
 * Centraliza toda la información personal, de contacto, ubicación y financiera.
 * Conecta con direcciones, tipos de cliente, estados y cuentas corrientes.
 * 
 * @package App\Models
 * @property int $clienteID Identificador único del cliente
 * @property string $nombre Nombre del cliente
 * @property string $apellido Apellido del cliente
 * @property string $DNI Documento Nacional de Identidad
 * @property string $mail Dirección de correo electrónico
 * @property string|null $whatsapp Número de WhatsApp para contacto
 * @property string|null $telefono Número de teléfono adicional
 * @property int $tipoClienteID Identificador del tipo de cliente (Mayorista/Minorista)
 * @property int $estadoClienteID Identificador del estado del cliente
 * @property int $direccionID Identificador de la dirección del cliente
 * @property int $cuentaCorrienteID Identificador de la cuenta corriente del cliente
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de última actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación (soft delete)
 * 
 * @property-read \App\Models\TipoCliente $tipoCliente Tipo de cliente (Mayorista/Minorista)
 * @property-read \App\Models\EstadoCliente $estadoCliente Estado actual del cliente
 * @property-read \App\Models\Direccion $direccion Dirección del cliente
 * @property-read \App\Models\CuentaCorriente $cuentaCorriente Cuenta corriente del cliente
 */
class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     * 
     * @var string
     */
    protected $table = 'clientes';
    
    /**
     * Clave primaria personalizada
     * 
     * @var string
     */
    protected $primaryKey = 'clienteID';
    
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

    /**
     * Obtiene el tipo de cliente (Mayorista o Minorista)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    /**
     * Obtiene el tipo de cliente (Mayorista o Minorista)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tipoCliente(): BelongsTo
    {
        return $this->belongsTo(TipoCliente::class, 'tipoClienteID', 'tipoClienteID');
    }

    /**
     * Obtiene el estado actual del cliente
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function estadoCliente(): BelongsTo
    {
        return $this->belongsTo(EstadoCliente::class, 'estadoClienteID', 'estadoClienteID');
    }

    /**
     * Obtiene la dirección del cliente
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function direccion(): BelongsTo
    {
        return $this->belongsTo(Direccion::class, 'direccionID', 'direccionID');
    }

    /**
     * Obtiene la cuenta corriente del cliente
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cuentaCorriente(): BelongsTo
    {
        return $this->belongsTo(CuentaCorriente::class, 'cuentaCorrienteID', 'cuentaCorrienteID');
    }

    /**
     * Obtiene el nombre completo del cliente
     * 
     * @return string Nombre y apellido concatenados
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    /**
     * Verifica si el cliente es mayorista
     * 
     * @return bool True si es mayorista
     */
    public function esMayorista(): bool
    {
        return $this->tipoCliente?->esMayorista() ?? false;
    }

    /**
     * Verifica si el cliente es minorista
     * 
     * @return bool True si es minorista
     */
    public function esMinorista(): bool
    {
        return $this->tipoCliente?->esMinorista() ?? false;
    }

    /**
     * Verifica si el cliente está activo
     * 
     * @return bool True si el estado es activo
     */
    public function estaActivo(): bool
    {
        return $this->estadoCliente?->nombreEstado === 'Activo';
    }

    /**
     * Obtiene el saldo de la cuenta corriente del cliente
     * 
     * @return float Saldo actual
     */
    public function getSaldoAttribute(): float
    {
        return $this->cuentaCorriente?->saldo ?? 0.0;
    }

    /**
     * Obtiene la dirección completa formateada del cliente
     * 
     * @return string Dirección completa con ubicación
     */
    public function getDireccionCompletaAttribute(): string
    {
        return $this->direccion?->direccion_completa_con_ubicacion ?? 'Sin dirección';
    }

    /**
     * Verifica si el cliente tiene deudas
     * 
     * @return bool True si el saldo es negativo
     */
    public function tieneDeudas(): bool
    {
        return $this->saldo < 0;
    }

    /**
     * Obtiene el límite de crédito disponible
     * 
     * @return float Crédito disponible
     */
    public function getCreditoDisponibleAttribute(): float
    {
        $limite = $this->cuentaCorriente?->limiteCredito ?? 0;
        $saldo = $this->saldo;
        
        // Si tiene saldo positivo, suma al límite
        if ($saldo > 0) {
            return $limite + $saldo;
        }
        
        // Si tiene deuda, resta del límite
        return $limite + $saldo; // saldo es negativo, así que resta
    }

    /**
     * Obtiene el historial de auditoría del cliente
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function historialAuditoria()
    {
        return Auditoria::historialCliente($this->clienteID);
    }

    /**
     * Verifica si el cliente puede ser dado de baja
     * 
     * @return bool True si puede ser dado de baja
     */
    public function puedeSerDadoDeBaja(): bool
    {
        // Lógica para verificar operaciones pendientes
        // Por ahora retorna true, se debe implementar según las reglas de negocio
        return true;
    }

    /**
     * Da de baja al cliente registrando la auditoría
     * 
     * @param string $motivo Motivo de la baja
     * @return bool True si la baja fue exitosa
     */
    public function darDeBaja(string $motivo): bool
    {
        if (!$this->puedeSerDadoDeBaja()) {
            throw new \Exception('El cliente tiene operaciones pendientes y no puede ser dado de baja.');
        }

        $datosAnteriores = $this->toArray();
        
        // Cambiar estado (asumiendo que tienes un estado "Inactivo")
        // Nota: Necesitarías crear este estado en tu seeder
        $estadoInactivo = EstadoCliente::where('nombreEstado', 'Inactivo')->first();
        if ($estadoInactivo) {
            $this->estadoClienteID = $estadoInactivo->estadoClienteID;
            $this->save();
        }

        // Registrar auditoría
        Auditoria::registrar(
            Auditoria::ACCION_BAJA_CLIENTE,
            'clientes',
            $this->clienteID,
            $datosAnteriores,
            $this->toArray(),
            $motivo,
            "Cliente dado de baja: {$this->nombre_completo}"
        );

        return true;
    }

    /**
     * Registra la creación del cliente en auditoría
     */
    protected static function boot()
    {
        parent::boot();

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

        static::updated(function ($cliente) {
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