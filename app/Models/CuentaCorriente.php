<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Configuracion; 

class CuentaCorriente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuentas_corriente';
    protected $primaryKey = 'cuentaCorrienteID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'saldo',
        'limiteCredito',
        'diasGracia',
        'estadoCuentaCorrienteID',
    ];

    // --- RELACIONES ---
    public function estadoCuentaCorriente(): BelongsTo
    {
        return $this->belongsTo(EstadoCuentaCorriente::class, 'estadoCuentaCorrienteID', 'estadoCuentaCorrienteID');
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'cuentaCorrienteID', 'cuentaCorrienteID');
    }

    public function movimientosCC(): HasMany
    {
        return $this->hasMany(MovimientoCuentaCorriente::class, 'cuentaCorrienteID', 'cuentaCorrienteID');
    }

    // --- MOVIMIENTOS ---
    public function registrarDebito(
        float $monto,
        string $descripcion,
        ?Carbon $fechaVencimiento = null,
        ?int $referenciaID = null,
        ?string $referenciaTabla = null,
        ?int $userID = null
    ): bool {
        $saldoAnterior = $this->saldo;
        $this->saldo += $monto;
        $guardado = $this->save();

        if ($guardado) {
            $this->movimientosCC()->create([
                'tipoMovimiento' => 'Debito',
                'descripcion' => $descripcion,
                'monto' => $monto,
                'fechaEmision' => now(),
                'fechaVencimiento' => $fechaVencimiento,
                'saldoAlMomento' => $this->saldo,
                'referenciaID' => $referenciaID,
                'referenciaTabla' => $referenciaTabla,
                'observaciones' => "Débito generado por {$descripcion}",
            ]);

        }
        return $guardado;
    }

    public function registrarCredito(
        float $monto,
        string $descripcion,
        ?int $referenciaID = null,
        ?string $referenciaTabla = null,
        ?int $userID = null
    ): bool {
        $saldoAnterior = $this->saldo;
        $this->saldo -= $monto;
        $guardado = $this->save();

        if ($guardado) {
            $this->movimientosCC()->create([
                'tipoMovimiento' => 'Credito',
                'descripcion' => $descripcion,
                'monto' => $monto,
                'fechaEmision' => now(),
                'fechaVencimiento' => null,
                'saldoAlMomento' => $this->saldo,
                'referenciaID' => $referenciaID,
                'referenciaTabla' => $referenciaTabla,
                'observaciones' => "Crédito generado por {$descripcion}",
            ]);
        }
        return $guardado;
    }

    // --- LÓGICA DE NEGOCIO CORREGIDA ---

    public function calcularSaldoVencido(): float
    {
        $diasGracia = $this->getDiasGraciaAplicables();
        $fechaLimiteVencimiento = Carbon::today()->subDays($diasGracia);

        $saldoVencido = $this->movimientosCC()
            ->where('tipoMovimiento', 'Debito')
            ->whereNotNull('fechaVencimiento')
            ->where('fechaVencimiento', '<=', $fechaLimiteVencimiento)
            ->sum('monto'); 

        $totalCreditos = $this->movimientosCC()
            ->where('tipoMovimiento', 'Credito')
            ->sum('monto');

        $saldoDeudor = max(0, $this->saldo); 
        $debitosVencidosPendientes = max(0, $saldoVencido - $totalCreditos);

        return min($saldoDeudor, $debitosVencidosPendientes);
    }

    /**
     * Obtiene el límite de crédito aplicable.
     * Busca en BD correctamente.
     */
    public function getLimiteCreditoAplicable(): float
    {
        if ($this->limiteCredito && $this->limiteCredito > 0) {
            return (float) $this->limiteCredito;
        }

        // FIX: Buscamos el valor en la tabla 'configuracion'
        // Asumiendo columnas 'clave' y 'valor'
        $global = Configuracion::where('clave', 'limite_credito_global')->value('valor');
        
        return (float) ($global ?? 0);
    }

    /**
     * Obtiene los días de gracia aplicables.
     * Busca en BD correctamente.
     */
    public function getDiasGraciaAplicables(): int
    {
        if ($this->diasGracia !== null) {
            return $this->diasGracia;
        }

        // FIX: Buscamos el valor en la tabla 'configuracion'
        $global = Configuracion::where('clave', 'dias_gracia_global')->value('valor');
        
        return (int) ($global ?? 0);
    }

    public function bloquear(string $motivo = 'Incumplimiento', ?int $userID = null): bool
    {
        $estadoBloqueada = EstadoCuentaCorriente::bloqueada();
        if ($estadoBloqueada && $this->estadoCuentaCorrienteID !== $estadoBloqueada->estadoCuentaCorrienteID) {
            $estadoAnterior = $this->estadoCuentaCorrienteID;
            $this->estadoCuentaCorrienteID = $estadoBloqueada->estadoCuentaCorrienteID;
            $guardado = $this->save();
            
            // Paso 8 CU-09: Registrar bloqueo en auditoría
            if ($guardado) {
                Auditoria::registrar(
                    Auditoria::ACCION_BLOQUEAR_CC,
                    'cuentas_corriente',
                    $this->cuentaCorrienteID,
                    ['estadoCuentaCorrienteID' => $estadoAnterior],
                    ['estadoCuentaCorrienteID' => $this->estadoCuentaCorrienteID],
                    $motivo,
                    "Cuenta Corriente {$this->cuentaCorrienteID} bloqueada.",
                    $userID
                );
            }
            
            return $guardado;
        }
        return false;
    }

    public function desbloquear(string $motivo = 'Normalizado', ?int $userID = null): bool
    {
        $estadoActiva = EstadoCuentaCorriente::activa();
        if ($estadoActiva && $this->estadoCuentaCorrienteID !== $estadoActiva->estadoCuentaCorrienteID) {
            $estadoAnterior = $this->estadoCuentaCorrienteID;
            $this->estadoCuentaCorrienteID = $estadoActiva->estadoCuentaCorrienteID;
            $guardado = $this->save();
            
            // Paso 8 CU-09: Registrar desbloqueo en auditoría
            if ($guardado) {
                Auditoria::registrar(
                    Auditoria::ACCION_DESBLOQUEAR_CC,
                    'cuentas_corriente',
                    $this->cuentaCorrienteID,
                    ['estadoCuentaCorrienteID' => $estadoAnterior],
                    ['estadoCuentaCorrienteID' => $this->estadoCuentaCorrienteID],
                    $motivo,
                    "Cuenta Corriente {$this->cuentaCorrienteID} desbloqueada.",
                    $userID
                );
            }
            
            return $guardado;
        }
        return false;
    }

    public function ponerEnRevision(string $motivo = 'Revisión', ?int $userID = null): bool
    {
        $estadoPendiente = EstadoCuentaCorriente::pendienteAprobacion();
        if ($estadoPendiente && $this->estadoCuentaCorrienteID !== $estadoPendiente->estadoCuentaCorrienteID) {
            $estadoAnterior = $this->estadoCuentaCorrienteID;
            $this->estadoCuentaCorrienteID = $estadoPendiente->estadoCuentaCorrienteID;
            $guardado = $this->save();
            
            // Paso 8 CU-09: Registrar cambio a pendiente de aprobación en auditoría
            if ($guardado) {
                Auditoria::registrar(
                    Auditoria::ACCION_PENDIENTE_APROBACION_CC,
                    'cuentas_corriente',
                    $this->cuentaCorrienteID,
                    ['estadoCuentaCorrienteID' => $estadoAnterior],
                    ['estadoCuentaCorrienteID' => $this->estadoCuentaCorrienteID],
                    $motivo,
                    "Cuenta Corriente {$this->cuentaCorrienteID} marcada como pendiente de aprobación.",
                    $userID
                );
            }
            
            return $guardado;
        }
        return false;
    }

    public function estaBloqueada(): bool
    {
        return $this->estadoCuentaCorriente?->nombreEstado === 'Bloqueada';
    }
}
