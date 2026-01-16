<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BonificacionReparacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClienteBonificacionController extends Controller
{
    /**
     * Mostrar página de respuesta del cliente (vista pública)
     */
    public function mostrar($token)
    {
        // Decodificar el token para obtener el bonificacionID
        try {
            $bonificacionID = $this->decodificarToken($token);
            $bonificacion = BonificacionReparacion::with(['reparacion.cliente', 'reparacion.modelo.marca', 'motivodemora', 'estadoDecision'])
                ->findOrFail($bonificacionID);

            // Verificar que la bonificación esté aprobada y pendiente de decisión del cliente
            // estado_decision_id = 1 es 'pendiente' para contexto 'bonificacion'
            $estadoPendiente = $bonificacion->estadoDecision?->nombre === 'pendiente';
            if ($bonificacion->estado !== 'aprobada' || !$estadoPendiente) {
                return view('bonificacion-cliente-error', [
                    'mensaje' => 'Esta bonificación ya no está disponible para respuesta.'
                ]);
            }

            $totalPagar = $bonificacion->monto_original - $bonificacion->monto_bonificado;

            return view('bonificacion-cliente', [
                'token' => $token,
                'bonificacion' => $bonificacion,
                'totalPagar' => $totalPagar,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar bonificación al cliente', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return view('bonificacion-cliente-error', [
                'mensaje' => 'No se pudo cargar la bonificación.'
            ]);
        }
    }

    /**
     * Registrar la decisión del cliente
     */
    public function responder(Request $request, $token)
    {
        $request->validate([
            'decision' => 'required|in:aceptar,cancelar',
            'observaciones' => 'nullable|string|max:500',
        ]);

        try {
            $bonificacionID = $this->decodificarToken($token);
            $bonificacion = BonificacionReparacion::with('reparacion')->findOrFail($bonificacionID);

            // Verificar que la bonificación esté aprobada y pendiente
            if ($bonificacion->estado !== 'aprobada' || $bonificacion->decision_cliente !== 'pendiente') {
                return response()->json([
                    'error' => 'Esta bonificación ya no está disponible para respuesta.'
                ], 400);
            }

            // Registrar decisión
            $bonificacion->registrarDecisionCliente(
                $request->decision,
                $request->observaciones
            );

            // Notificar a todos los administradores
            $admins = \App\Models\User::whereHas('rol', function($query) {
                $query->where('nombre', 'admin');
            })->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\ClienteRespondioBoificacion($bonificacion));
            }

            Log::info('Cliente respondió a bonificación', [
                'bonificacion_id' => $bonificacion->bonificacionID,
                'decision' => $request->decision,
                'cliente_id' => $bonificacion->reparacion->clienteID,
            ]);

            return response()->json([
                'mensaje' => $request->decision === 'aceptar' 
                    ? 'Gracias por aceptar. Continuaremos con la reparación de su equipo.'
                    : 'Su solicitud de cancelación ha sido registrada. En breve nos contactaremos con usted.',
                'decision' => $request->decision,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al registrar decisión del cliente', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'No se pudo registrar su decisión. Por favor, intente nuevamente.'
            ], 500);
        }
    }

    /**
     * Generar token simple para bonificación
     */
    public static function generarToken($bonificacionID): string
    {
        return base64_encode("bonif_{$bonificacionID}_" . config('app.key'));
    }

    /**
     * Decodificar token para obtener bonificacionID
     */
    private function decodificarToken(string $token): int
    {
        $decoded = base64_decode($token);
        preg_match('/bonif_(\d+)_/', $decoded, $matches);
        
        if (!isset($matches[1])) {
            throw new \Exception('Token inválido');
        }

        return (int) $matches[1];
    }
}
