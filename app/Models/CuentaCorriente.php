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
            // Obtener tipo_movimiento_cc_id para Débito
            $tipoDebito = \DB::table('tipos_movimiento_cuenta_corriente')
                ->where('nombre', 'Debito')
                ->value('tipo_movimiento_cc_id');

            $this->movimientosCC()->create([
                'tipo_movimiento_cc_id' => $tipoDebito,
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
            // Obtener tipo_movimiento_cc_id para Crédito
            $tipoCredito = \DB::table('tipos_movimiento_cuenta_corriente')
                ->where('nombre', 'Credito')
                ->value('tipo_movimiento_cc_id');

            $this->movimientosCC()->create([
                'tipo_movimiento_cc_id' => $tipoCredito,
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

        // Obtener tipo_movimiento_cc_id para Débito y Crédito
        $tipoDebito = \DB::table('tipos_movimiento_cuenta_corriente')
            ->where('nombre', 'Debito')
            ->value('tipo_movimiento_cc_id');
        $tipoCredito = \DB::table('tipos_movimiento_cuenta_corriente')
            ->where('nombre', 'Credito')
            ->value('tipo_movimiento_cc_id');

        $saldoVencido = $this->movimientosCC()
            ->where('tipo_movimiento_cc_id', $tipoDebito)
            ->whereNotNull('fechaVencimiento')
            ->where('fechaVencimiento', '<=', $fechaLimiteVencimiento)
            ->sum('monto'); 

        $totalCreditos = $this->movimientosCC()
            ->where('tipo_movimiento_cc_id', $tipoCredito)
            ->sum('monto');

        $saldoDeudor = max(0, $this->saldo); 
        $debitosVencidosPendientes = max(0, $saldoVencido - $totalCreditos);

        return min($saldoDeudor, $debitosVencidosPendientes);
    }

    /**
     * Calcula los recargos por mora sobre saldos vencidos.
     * 
     * @return array ['total' => float, 'detalle' => array]
     */
    public function calcularRecargosMora(): array
    {
        $habilitado = Configuracion::getBool('recargo_mora_habilitado', false);
        
        if (!$habilitado) {
            return ['total' => 0, 'detalle' => []];
        }

        $porcentajeMensual = (float) Configuracion::get('recargo_mora_porcentaje', 2.0);
        $diasMinimos = (int) Configuracion::get('recargo_mora_minimo_dias', 30);
        $diasGracia = $this->getDiasGraciaAplicables();
        
        $fechaLimite = Carbon::today()->subDays($diasGracia + $diasMinimos);
        
        // Obtener tipo_movimiento_cc_id para Débito
        $tipoDebito = \DB::table('tipos_movimiento_cuenta_corriente')
            ->where('nombre', 'Debito')
            ->value('tipo_movimiento_cc_id');
        
        // Obtener débitos vencidos que califican para recargo
        $debitosVencidos = $this->movimientosCC()
            ->where('tipo_movimiento_cc_id', $tipoDebito)
            ->whereNotNull('fechaVencimiento')
            ->where('fechaVencimiento', '<=', $fechaLimite)
            ->get();

        $totalRecargo = 0;
        $detalle = [];

        foreach ($debitosVencidos as $movimiento) {
            $diasAtraso = Carbon::parse($movimiento->fechaVencimiento)
                ->addDays($diasGracia)
                ->diffInDays(Carbon::today());
            
            if ($diasAtraso >= $diasMinimos) {
                // Calcular recargo proporcional: (monto * porcentaje * meses)
                $mesesAtraso = $diasAtraso / 30;
                $recargo = $movimiento->monto * ($porcentajeMensual / 100) * $mesesAtraso;
                
                $totalRecargo += $recargo;
                $detalle[] = [
                    'movimientoID' => $movimiento->movimientoCCID,
                    'monto_original' => $movimiento->monto,
                    'dias_atraso' => $diasAtraso,
                    'meses_atraso' => round($mesesAtraso, 2),
                    'recargo' => $recargo,
                    'descripcion' => $movimiento->descripcion,
                ];
            }
        }

        return [
            'total' => round($totalRecargo, 2),
            'detalle' => $detalle,
        ];
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
            
            // IMPORTANTE: También bloquear al cliente asociado (Estado "Moroso")
            if ($guardado && $this->cliente) {
                $estadoClienteMoroso = \App\Models\EstadoCliente::where('nombreEstado', 'Moroso')->first();
                if ($estadoClienteMoroso && $this->cliente->estadoClienteID !== $estadoClienteMoroso->estadoClienteID) {
                    $this->cliente->estadoClienteID = $estadoClienteMoroso->estadoClienteID;
                    $this->cliente->save();
                }
            }
            
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
            
            // IMPORTANTE: También desbloquear al cliente asociado
            if ($guardado && $this->cliente) {
                $estadoClienteActivo = \App\Models\EstadoCliente::where('nombreEstado', 'Activo')->first();
                if ($estadoClienteActivo && $this->cliente->estadoClienteID !== $estadoClienteActivo->estadoClienteID) {
                    $this->cliente->estadoClienteID = $estadoClienteActivo->estadoClienteID;
                    $this->cliente->save();
                }
            }
            
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
