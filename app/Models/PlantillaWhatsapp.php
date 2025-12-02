<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Modelo para gestión de plantillas WhatsApp (CU-30)
 * 
 * Permite configurar mensajes parametrizables con:
 * - Variables dinámicas ({variable})
 * - Horarios de envío configurables
 * - Auditoría de cambios con motivos
 */
class PlantillaWhatsapp extends Model
{
    protected $table = 'plantillas_whatsapp';
    protected $primaryKey = 'plantilla_id';

    protected $fillable = [
        'tipo_evento',
        'nombre',
        'contenido_plantilla',
        'variables_disponibles',
        'horario_inicio',
        'horario_fin',
        'activo',
        'motivo_modificacion',
        'usuario_modificacion',
    ];

    protected $casts = [
        'variables_disponibles' => 'array',
        'activo' => 'boolean',
        'horario_inicio' => 'datetime:H:i',
        'horario_fin' => 'datetime:H:i',
    ];

    /**
     * Relaciones
     */
    public function usuarioModificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_modificacion');
    }

    /**
     * Scopes
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTipo($query, string $tipoEvento)
    {
        return $query->where('tipo_evento', $tipoEvento);
    }

    /**
     * CU-30: Obtener plantilla activa por tipo de evento
     * 
     * @param string $tipoEvento Identificador del tipo de evento
     * @return PlantillaWhatsapp|null
     */
    public static function obtenerPorTipo(string $tipoEvento): ?self
    {
        return self::where('tipo_evento', $tipoEvento)
            ->where('activo', true)
            ->first();
    }

    /**
     * CU-30: Reemplazar variables en el contenido de la plantilla
     * 
     * @param array $datos Array asociativo con valores de variables
     * @return string Mensaje con variables reemplazadas
     */
    public function compilar(array $datos): string
    {
        $mensaje = $this->contenido_plantilla;

        // Reemplazar cada variable disponible
        foreach ($this->variables_disponibles as $variable) {
            $valor = $datos[$variable] ?? '[NO_DEFINIDO]';
            $mensaje = str_replace("{{$variable}}", $valor, $mensaje);
        }

        // Log de advertencia si quedan variables sin reemplazar
        if (preg_match_all('/\{([^}]+)\}/', $mensaje, $matches)) {
            Log::warning('Plantilla con variables no reemplazadas', [
                'plantilla_id' => $this->plantilla_id,
                'tipo_evento' => $this->tipo_evento,
                'variables_faltantes' => $matches[1],
            ]);
        }

        return $mensaje;
    }

    /**
     * CU-30: Verificar si el mensaje puede enviarse en el horario actual
     * 
     * @return bool
     */
    public function estaEnHorarioPermitido(): bool
    {
        $ahora = Carbon::now();
        $inicio = Carbon::createFromTimeString($this->horario_inicio);
        $fin = Carbon::createFromTimeString($this->horario_fin);

        return $ahora->between($inicio, $fin);
    }

    /**
     * CU-30: Calcular segundos hasta el próximo horario permitido
     * 
     * @return int Segundos a esperar
     */
    public function segundosHastaProximoEnvio(): int
    {
        $ahora = Carbon::now();
        $inicio = Carbon::createFromTimeString($this->horario_inicio);

        // Si ya pasó el horario de inicio hoy, esperar hasta mañana
        if ($ahora->gt($inicio)) {
            return $ahora->diffInSeconds($inicio->addDay());
        }

        return $ahora->diffInSeconds($inicio);
    }

    /**
     * CU-30: Validar sintaxis de variables en el contenido
     * 
     * @return array Array con errores si existen
     */
    public function validarVariables(): array
    {
        $errores = [];

        // Extraer variables del contenido
        preg_match_all('/\{([^}]+)\}/', $this->contenido_plantilla, $matches);
        $variablesEnContenido = $matches[1];

        // Verificar que todas las variables existan en variables_disponibles
        foreach ($variablesEnContenido as $variable) {
            if (!in_array($variable, $this->variables_disponibles)) {
                $errores[] = "Variable '{$variable}' no está en la lista de variables disponibles";
            }
        }

        return $errores;
    }

    /**
     * CU-30 Paso 7-12: Registrar modificación con motivo
     * 
     * @param string $motivo Razón del cambio
     * @param int|null $usuarioId ID del usuario que realiza el cambio
     * @return bool
     */
    public function registrarCambio(string $motivo, ?int $usuarioId = null): bool
    {
        $datosAnteriores = $this->getOriginal();
        
        $this->motivo_modificacion = $motivo;
        $this->usuario_modificacion = $usuarioId ?? auth()->id();
        $guardado = $this->save();

        if ($guardado) {
            // Registrar en auditoría
            Auditoria::registrar(
                'MODIFICAR_PLANTILLA_WHATSAPP',
                'plantillas_whatsapp',
                $this->plantilla_id,
                $datosAnteriores,
                $this->toArray(),
                "Plantilla WhatsApp modificada: {$this->nombre}",
                "Motivo: {$motivo}",
                $this->usuario_modificacion
            );

            Log::info('Plantilla WhatsApp modificada', [
                'plantilla_id' => $this->plantilla_id,
                'tipo_evento' => $this->tipo_evento,
                'motivo' => $motivo,
                'usuario_id' => $this->usuario_modificacion,
            ]);
        }

        return $guardado;
    }
}
