<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para gestionar las cuentas corrientes del sistema
 *
 * Esta clase representa las cuentas corrientes de los clientes,
 * manejando saldos, límites de crédito, días de gracia y estados financieros.
 *
 * @property int $cuentaCorrienteID Identificador único de la cuenta corriente
 * @property float $saldo Saldo actual de la cuenta corriente
 * @property float $limiteCredito Límite de crédito otorgado al cliente
 * @property int $diasGracia Días de gracia para pagos después del vencimiento
 * @property int $estadoCuentaCorrienteID Identificador del estado de la cuenta corriente
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de última actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación (soft delete)
 * @property-read \App\Models\EstadoCuentaCorriente $estadoCuentaCorriente Estado actual de la cuenta corriente
 * @property-read \App\Models\Cliente|null $cliente Cliente propietario de esta cuenta corriente
 */
class CuentaCorriente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     *
     * @var string
     */
    protected $table = 'cuentas_corriente';

    /**
     * Clave primaria personalizada
     *
     * @var string
     */
    protected $primaryKey = 'cuentaCorrienteID';

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
        'saldo',
        'limiteCredito',
        'diasGracia',
        'estadoCuentaCorrienteID',
    ];

    /**
     * Obtiene el estado actual de esta cuenta corriente
     */
    public function estadoCuentaCorriente(): BelongsTo
    {
        return $this->belongsTo(EstadoCuentaCorriente::class, 'estadoCuentaCorrienteID', 'estadoCuentaCorrienteID');
    }

    /**
     * Obtiene el cliente propietario de esta cuenta corriente
     */
    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'cuentaCorrienteID', 'cuentaCorrienteID');

    }

    /**
     * Obtiene todos los movimientos asociados a esta cuenta corriente.
     */
    public function movimientosCC(): HasMany
    {
        return $this->hasMany(MovimientoCuentaCorriente::class, 'cuentaCorrienteID', 'cuentaCorrienteID');
    }

    /**
     * Registra un DÉBITO en la cuenta corriente (aumenta la deuda).
     *
     * @param  float  $monto  El monto a debitar (positivo)
     * @param  string  $descripcion  La descripción del movimiento (ej: "Venta N° 123")
     * @param  Carbon|null  $fechaVencimiento  La fecha de vencimiento del débito
     * @param  int|null  $referenciaID  El ID de la venta/origen que origina el débito
     * @param  string|null  $referenciaTabla  La tabla de origen (ej: 'ventas')
     * @param  int|null  $userID  El ID del usuario que registra la operación (si no es el logueado)
     */
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
            // Registramos el movimiento detallado en movimientos_cuenta_corriente
            $this->movimientosCC()->create([
                'tipoMovimiento' => 'Debito',
                'descripcion' => $descripcion,
                'monto' => $monto,
                'fechaEmision' => now(), // La fecha actual del registro
                'fechaVencimiento' => $fechaVencimiento,
                'saldoAlMomento' => $this->saldo, // El saldo de la CC después de este débito
                'referenciaID' => $referenciaID,
                'referenciaTabla' => $referenciaTabla,
                'observaciones' => "Débito generado por {$descripcion}",
            ]);

            Auditoria::registrar(
                Auditoria::ACCION_MODIFICAR_CUENTA_CORRIENTE,
                $this->table,
                $this->cuentaCorrienteID,
                ['saldo' => $saldoAnterior],
                ['saldo' => $this->saldo],
                $descripcion,
                "Débito de: $monto (Ref ID: {$referenciaID}, Tabla: {$referenciaTabla})",
                $userID ?? auth()->id()
            );
        }

        return $guardado;
    }

    /**
     * Registra un CRÉDITO en la cuenta corriente (disminuye la deuda / pago).
     *
     * @param  float  $monto  El monto a acreditar (positivo)
     * @param  string  $descripcion  La descripción del movimiento (ej: "Pago Venta N° 123")
     * @param  int|null  $referenciaID  El ID del pago/origen que origina el crédito
     * @param  string|null  $referenciaTabla  La tabla de origen (ej: 'pagos')
     * @param  int|null  $userID  El ID del usuario que registra la operación (si no es el logueado)
     */
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
            // Registramos el movimiento detallado en movimientos_cuenta_corriente
            $this->movimientosCC()->create([
                'tipoMovimiento' => 'Credito',
                'descripcion' => $descripcion,
                'monto' => $monto,
                'fechaEmision' => now(), // La fecha actual del registro
                'fechaVencimiento' => null, // Los créditos no suelen tener fecha de vencimiento
                'saldoAlMomento' => $this->saldo, // El saldo de la CC después de este crédito
                'referenciaID' => $referenciaID,
                'referenciaTabla' => $referenciaTabla,
                'observaciones' => "Crédito generado por {$descripcion}",
            ]);

            Auditoria::registrar(
                Auditoria::ACCION_MODIFICAR_CUENTA_CORRIENTE,
                $this->table,
                $this->cuentaCorrienteID,
                ['saldo' => $saldoAnterior],
                ['saldo' => $this->saldo],
                $descripcion,
                "Crédito de: $monto (Ref ID: {$referenciaID}, Tabla: {$referenciaTabla})",
                $userID ?? auth()->id()
            );
        }

        return $guardado;
    }
    // --- NUEVOS MÉTODOS PARA CU-09 ---

    /**
     * Calcula el saldo vencido de la cuenta corriente basándose en los movimientos.
     * Considera los días de gracia.
     */
    public function calcularSaldoVencido(): float
    {
        $diasGracia = $this->getDiasGraciaAplicables();
        $fechaLimiteVencimiento = Carbon::today()->subDays($diasGracia);

        // Suma todos los débitos (ventas/recargos) cuyo vencimiento es anterior a la fecha límite
        // y que no han sido compensados por créditos.
        // Esto es un cálculo simplificado. En un sistema real, un débito se "paga" con un crédito,
        // y se debería rastrear qué créditos pagan qué débitos (imputación de pagos).
        // Para este nivel, sumaremos todos los débitos "vencidos" que aún contribuyen al saldo actual.

        $saldoVencido = $this->movimientosCC()
            ->where('tipoMovimiento', 'Debito')
            ->whereNotNull('fechaVencimiento')
            ->where('fechaVencimiento', '<=', $fechaLimiteVencimiento)
            // Aquí la complejidad: ¿Cuánto de estos débitos vencidos sigue sin pagar?
            // Si el saldo de la CC es positivo (deudor), entonces hay deuda.
            // La suma directa de "monto" de movimientos vencidos no es precisa
            // si no rastreamos qué créditos compensan qué débitos.
            // Para simplificar, si el saldo total es deudor y hay movimientos vencidos,
            // asumimos que el saldo deudor actual es el que está vencido,
            // hasta el monto total de los débitos vencidos.

            // Una forma más simple pero efectiva para la mayoría de los casos:
            // Sumar todos los débitos que deberían haber sido pagados
            // y luego restar todos los créditos (pagos) sin imputar específicamente.
            // Esto es si no tienes un sistema de imputación de pagos.

            ->sum('monto'); // Sumamos todos los montos de débitos vencidos

        // Ahora, necesitamos restar los créditos para ver cuánto de ese vencido se pagó.
        // Esto es rudimentario, pero es un paso adelante del placeholder.
        $totalCreditos = $this->movimientosCC()
            ->where('tipoMovimiento', 'Credito')
            ->sum('monto');

        // El saldo vencido real es el mínimo entre el saldo actual deudor
        // y la suma de los débitos que han vencido.
        // Si el saldo actual es negativo (a favor del cliente), no hay vencido.
        // Si el saldo actual es positivo, es lo que debemos evaluar.

        $saldoDeudor = max(0, $this->saldo); // Solo la parte deudora del saldo

        // El saldo vencido es lo que aún se debe de los débitos que ya vencieron.
        // Esta lógica es para un sistema sin imputación de pagos.
        $debitosVencidosPendientes = max(0, $saldoVencido - $totalCreditos);

        // El saldo vencido final no puede ser mayor que el saldo deudor total.
        return min($saldoDeudor, $debitosVencidosPendientes);
    }

    /**
     * Obtiene el límite de crédito aplicable a esta cuenta
     * Prioriza el límite del cliente, si no, usa el global.
     * (CU-09 Excepción 3a)
     */
    public function getLimiteCreditoAplicable(): float
    {
        // 1. Si el cliente tiene un límite específico (atributo 'limiteCredito' del modelo)
        // Nota: tu fillable usa 'limiteCredito' (camelCase)
        if ($this->limiteCredito && $this->limiteCredito > 0) {
            return (float) $this->limiteCredito;
        }

        // 2. Si no, usamos el Parámetro Global (CU-31)
        // ¡CORREGIDO! Usamos 'limite_credito_global' para coincidir con tu Seeder.
        return (float) Configuracion::get('limite_credito_global', 0);
    }

    /**
     * Obtiene los días de gracia aplicables a esta cuenta.
     * Prioriza los días de gracia del cliente, si no, usa el global.
     */
    public function getDiasGraciaAplicables(): int
    {
        return $this->diasGracia ?? Configuracion::getInt('dias_gracia_global');
    }

    /**
     * Bloquea la cuenta corriente.
     */
    public function bloquear(string $motivo = 'Incumplimiento de condiciones', ?int $userID = null): bool
    {
        $estadoBloqueada = EstadoCuentaCorriente::where('nombreEstado', 'Bloqueada')->first();
        if ($estadoBloqueada && $this->estadoCuentaCorrienteID !== $estadoBloqueada->estadoCuentaCorrienteID) {
            $estadoAnterior = $this->estadoCuentaCorriente?->nombreEstado;
            $this->estadoCuentaCorrienteID = $estadoBloqueada->estadoCuentaCorrienteID;
            $guardado = $this->save();

            if ($guardado) {
                Auditoria::registrar(
                    Auditoria::ACCION_MODIFICAR_ESTADO_CC,
                    $this->table,
                    $this->cuentaCorrienteID,
                    ['estado' => $estadoAnterior],
                    ['estado' => $estadoBloqueada->nombreEstado],
                    $motivo,
                    "Cuenta Corriente {$this->cuentaCorrienteID} bloqueada.",
                    $userID ?? auth()->id()
                );
            }

            return $guardado;
        }

        return false;
    }

    /**
     * Desbloquea la cuenta corriente.
     */
    public function desbloquear(string $motivo = 'Condiciones normalizadas', ?int $userID = null): bool
    {
        $estadoActiva = EstadoCuentaCorriente::where('nombreEstado', 'Activa')->first();
        if ($estadoActiva && $this->estadoCuentaCorrienteID !== $estadoActiva->estadoCuentaCorrienteID) {
            $estadoAnterior = $this->estadoCuentaCorriente?->nombreEstado;
            $this->estadoCuentaCorrienteID = $estadoActiva->estadoCuentaCorrienteID;
            $guardado = $this->save();

            if ($guardado) {
                Auditoria::registrar(
                    Auditoria::ACCION_MODIFICAR_ESTADO_CC,
                    $this->table,
                    $this->cuentaCorrienteID,
                    ['estado' => $estadoAnterior],
                    ['estado' => $estadoActiva->nombreEstado],
                    $motivo,
                    "Cuenta Corriente {$this->cuentaCorrienteID} desbloqueada.",
                    $userID ?? auth()->id()
                );
            }

            return $guardado;
        }

        return false;
    }

    /**
     * Pone la cuenta corriente en estado "Pendiente de Aprobación".
     */
    public function ponerEnRevision(string $motivo = 'Requiere aprobación manual', ?int $userID = null): bool
    {
        $estadoPendiente = EstadoCuentaCorriente::where('nombreEstado', 'Pendiente de Aprobación')->first();

        if ($estadoPendiente && $this->estadoCuentaCorrienteID !== $estadoPendiente->estadoCuentaCorrienteID) {
            $estadoAnterior = $this->estadoCuentaCorriente?->nombreEstado;
            $this->estadoCuentaCorrienteID = $estadoPendiente->estadoCuentaCorrienteID;
            $guardado = $this->save();

            if ($guardado) {
                Auditoria::registrar(
                    Auditoria::ACCION_MODIFICAR_ESTADO_CC,
                    $this->table,
                    $this->cuentaCorrienteID,
                    ['estado' => $estadoAnterior],
                    ['estado' => $estadoPendiente->nombreEstado],
                    $motivo,
                    "Cuenta Corriente {$this->cuentaCorrienteID} puesta en revisión.",
                    $userID ?? auth()->id()
                );
            }

            return $guardado;
        }

        return false;
    }

    /**
     * Verifica si la cuenta corriente está bloqueada.
     */
    public function estaBloqueada(): bool
    {
        return $this->estadoCuentaCorriente?->nombreEstado === 'Bloqueada';
    }
}
