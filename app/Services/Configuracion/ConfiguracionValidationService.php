<?php

namespace App\Services\Configuracion;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Servicio para validar parámetros de configuración global (CU-31)
 * 
 * Implementa validaciones de tipo, rango y coherencia entre parámetros
 * según las reglas de negocio definidas
 */
class ConfiguracionValidationService
{
    /**
     * Reglas de validación por tipo de parámetro
     * CU-31 Excepción 6a: Validación de rangos y formatos
     */
    private const REGLAS_VALIDACION = [
        // === SLA y Reparaciones ===
        'sla_reparaciones_default' => [
            'tipo' => 'integer',
            'min' => 1,
            'max' => 365,
            'mensaje' => 'El SLA debe estar entre 1 y 365 días',
        ],
        
        // === Bonificaciones ===
        'bonificacion_1_3_dias' => [
            'tipo' => 'numeric',
            'min' => 0,
            'max' => 100,
            'mensaje' => 'El porcentaje debe estar entre 0 y 100',
        ],
        'bonificacion_4_7_dias' => [
            'tipo' => 'numeric',
            'min' => 0,
            'max' => 100,
            'mensaje' => 'El porcentaje debe estar entre 0 y 100',
        ],
        'bonificacion_mas_7_dias' => [
            'tipo' => 'numeric',
            'min' => 0,
            'max' => 100,
            'mensaje' => 'El porcentaje debe estar entre 0 y 100',
        ],
        'bonificacion_tope_maximo' => [
            'tipo' => 'numeric',
            'min' => 0,
            'max' => 100,
            'mensaje' => 'El tope máximo debe estar entre 0 y 100',
        ],
        'bonificacion_demora_habilitada' => [
            'tipo' => 'boolean',
            'mensaje' => 'Debe ser verdadero o falso',
        ],
        
        // === Cuentas Corrientes ===
        'dias_gracia_global' => [
            'tipo' => 'integer',
            'min' => 0,
            'max' => 365,
            'mensaje' => 'Los días de gracia deben estar entre 0 y 365',
        ],
        'limite_credito_global' => [
            'tipo' => 'numeric',
            'min' => 0,
            'mensaje' => 'El límite de crédito debe ser mayor o igual a 0',
        ],
        'politicaAutoBlock' => [
            'tipo' => 'boolean',
            'mensaje' => 'Debe ser verdadero o falso',
        ],
        
        // === Stock y Ventas ===
        'dias_maximos_anulacion_venta' => [
            'tipo' => 'integer',
            'min' => 0,
            'max' => 90,
            'mensaje' => 'Debe estar entre 0 y 90 días',
        ],
        'stock_minimo_global' => [
            'tipo' => 'integer',
            'min' => 0,
            'mensaje' => 'El stock mínimo debe ser mayor o igual a 0',
        ],
        'permitir_venta_sin_stock' => [
            'tipo' => 'boolean',
            'mensaje' => 'Debe ser verdadero o falso',
        ],
        'alerta_stock_bajo' => [
            'tipo' => 'boolean',
            'mensaje' => 'Debe ser verdadero o falso',
        ],
        
        // === WhatsApp ===
        'whatsapp_activo' => [
            'tipo' => 'boolean',
            'mensaje' => 'Debe ser verdadero o falso',
        ],
        'whatsapp_reintentos_maximos' => [
            'tipo' => 'integer',
            'min' => 0,
            'max' => 10,
            'mensaje' => 'Los reintentos deben estar entre 0 y 10',
        ],
        'whatsapp_horario_inicio' => [
            'tipo' => 'time',
            'mensaje' => 'Debe ser un horario válido (HH:MM)',
        ],
        'whatsapp_horario_fin' => [
            'tipo' => 'time',
            'mensaje' => 'Debe ser un horario válido (HH:MM)',
        ],
    ];

    /**
     * Valida un parámetro de configuración
     * 
     * @param string $clave Clave del parámetro
     * @param mixed $valor Valor a validar
     * @return array ['valido' => bool, 'errores' => array, 'valor_convertido' => mixed]
     */
    public function validar(string $clave, mixed $valor): array
    {
        // Si no hay regla específica, validación básica
        if (!isset(self::REGLAS_VALIDACION[$clave])) {
            return $this->validacionBasica($valor);
        }

        $regla = self::REGLAS_VALIDACION[$clave];
        
        // Validar según tipo
        return match ($regla['tipo']) {
            'integer' => $this->validarEntero($clave, $valor, $regla),
            'numeric' => $this->validarNumerico($clave, $valor, $regla),
            'boolean' => $this->validarBooleano($clave, $valor, $regla),
            'time' => $this->validarHorario($clave, $valor, $regla),
            'string' => $this->validarTexto($clave, $valor, $regla),
            default => $this->validacionBasica($valor),
        };
    }

    /**
     * Valida múltiples parámetros y sus relaciones
     * CU-31: Validación de coherencia entre parámetros
     */
    public function validarMultiples(array $configuraciones): array
    {
        $errores = [];
        $valoresValidados = [];

        // Validar cada parámetro individualmente
        foreach ($configuraciones as $clave => $valor) {
            $resultado = $this->validar($clave, $valor);
            
            if (!$resultado['valido']) {
                $errores[$clave] = $resultado['errores'];
            } else {
                $valoresValidados[$clave] = $resultado['valor_convertido'] ?? $valor;
            }
        }

        // Si hay errores individuales, retornar inmediatamente
        if (!empty($errores)) {
            return ['valido' => false, 'errores' => $errores];
        }

        // Validaciones de coherencia entre parámetros
        $erroresCoherencia = $this->validarCoherencia($valoresValidados);
        
        if (!empty($erroresCoherencia)) {
            return ['valido' => false, 'errores' => $erroresCoherencia];
        }

        return ['valido' => true, 'valores' => $valoresValidados];
    }

    /**
     * Valida coherencia entre parámetros relacionados
     */
    private function validarCoherencia(array $valores): array
    {
        $errores = [];

        // Validar que el tope de bonificación sea mayor que todos los porcentajes
        $porcentajes = [
            'bonificacion_1_3_dias' => $valores['bonificacion_1_3_dias'] ?? null,
            'bonificacion_4_7_dias' => $valores['bonificacion_4_7_dias'] ?? null,
            'bonificacion_mas_7_dias' => $valores['bonificacion_mas_7_dias'] ?? null,
        ];
        
        $tope = $valores['bonificacion_tope_maximo'] ?? null;
        
        if ($tope !== null) {
            foreach ($porcentajes as $clave => $porcentaje) {
                if ($porcentaje !== null && $porcentaje > $tope) {
                    $errores[$clave][] = "El porcentaje ({$porcentaje}%) no puede exceder el tope máximo ({$tope}%)";
                }
            }
        }

        // Validar que horario_fin sea posterior a horario_inicio
        $horaInicio = $valores['whatsapp_horario_inicio'] ?? null;
        $horaFin = $valores['whatsapp_horario_fin'] ?? null;
        
        if ($horaInicio && $horaFin && $horaFin <= $horaInicio) {
            $errores['whatsapp_horario_fin'][] = 'El horario de fin debe ser posterior al horario de inicio';
        }

        // Validar que los porcentajes sean progresivos
        $porc1_3 = $valores['bonificacion_1_3_dias'] ?? 0;
        $porc4_7 = $valores['bonificacion_4_7_dias'] ?? 0;
        $porcMas7 = $valores['bonificacion_mas_7_dias'] ?? 0;
        
        if ($porc4_7 < $porc1_3) {
            $errores['bonificacion_4_7_dias'][] = 'La bonificación para 4-7 días debe ser mayor o igual que para 1-3 días';
        }
        
        if ($porcMas7 < $porc4_7) {
            $errores['bonificacion_mas_7_dias'][] = 'La bonificación para más de 7 días debe ser mayor o igual que para 4-7 días';
        }

        return $errores;
    }

    private function validarEntero(string $clave, mixed $valor, array $regla): array
    {
        if (!is_numeric($valor)) {
            return ['valido' => false, 'errores' => ['El valor debe ser un número entero']];
        }

        $valorInt = (int) $valor;

        if (isset($regla['min']) && $valorInt < $regla['min']) {
            return ['valido' => false, 'errores' => [$regla['mensaje'] ?? 'Valor fuera de rango']];
        }

        if (isset($regla['max']) && $valorInt > $regla['max']) {
            return ['valido' => false, 'errores' => [$regla['mensaje'] ?? 'Valor fuera de rango']];
        }

        return ['valido' => true, 'valor_convertido' => $valorInt];
    }

    private function validarNumerico(string $clave, mixed $valor, array $regla): array
    {
        if (!is_numeric($valor)) {
            return ['valido' => false, 'errores' => ['El valor debe ser numérico']];
        }

        $valorFloat = (float) $valor;

        if (isset($regla['min']) && $valorFloat < $regla['min']) {
            return ['valido' => false, 'errores' => [$regla['mensaje'] ?? 'Valor fuera de rango']];
        }

        if (isset($regla['max']) && $valorFloat > $regla['max']) {
            return ['valido' => false, 'errores' => [$regla['mensaje'] ?? 'Valor fuera de rango']];
        }

        return ['valido' => true, 'valor_convertido' => $valorFloat];
    }

    private function validarBooleano(string $clave, mixed $valor, array $regla): array
    {
        if (is_bool($valor)) {
            return ['valido' => true, 'valor_convertido' => $valor];
        }

        // Convertir strings comunes
        if (in_array($valor, ['true', '1', 1, 'yes', 'si'], true)) {
            return ['valido' => true, 'valor_convertido' => true];
        }

        if (in_array($valor, ['false', '0', 0, 'no'], true)) {
            return ['valido' => true, 'valor_convertido' => false];
        }

        return ['valido' => false, 'errores' => [$regla['mensaje']]];
    }

    private function validarHorario(string $clave, mixed $valor, array $regla): array
    {
        // Validar formato HH:MM
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $valor)) {
            return ['valido' => false, 'errores' => [$regla['mensaje']]];
        }

        return ['valido' => true, 'valor_convertido' => $valor];
    }

    private function validarTexto(string $clave, mixed $valor, array $regla): array
    {
        if (!is_string($valor)) {
            return ['valido' => false, 'errores' => ['El valor debe ser texto']];
        }

        if (isset($regla['min_length']) && strlen($valor) < $regla['min_length']) {
            return ['valido' => false, 'errores' => [$regla['mensaje'] ?? 'Texto demasiado corto']];
        }

        if (isset($regla['max_length']) && strlen($valor) > $regla['max_length']) {
            return ['valido' => false, 'errores' => [$regla['mensaje'] ?? 'Texto demasiado largo']];
        }

        return ['valido' => true, 'valor_convertido' => $valor];
    }

    private function validacionBasica(mixed $valor): array
    {
        // Validación genérica: solo asegurar que no sea null
        if ($valor === null) {
            return ['valido' => false, 'errores' => ['El valor no puede ser nulo']];
        }

        return ['valido' => true];
    }
}
