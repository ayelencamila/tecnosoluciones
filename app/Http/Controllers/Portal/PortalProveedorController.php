<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CotizacionProveedor;
use App\Services\Compras\SolicitudCotizacionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador: Portal Público de Proveedores (CU-20)
 * 
 * Maneja el acceso de proveedores externos al portal de cotización
 * usando Magic Links (tokens únicos). NO requiere autenticación.
 * 
 * Lineamientos aplicados:
 * - Kendall: Magic Link para acceso externo seguro
 * - Larman: Controller (maneja requests del portal)
 * 
 * Seguridad:
 * - Acceso solo por token único (UUID)
 * - Token expira cuando la solicitud vence
 * - No se expone información sensible
 */
class PortalProveedorController extends Controller
{
    protected SolicitudCotizacionService $solicitudService;

    public function __construct(SolicitudCotizacionService $solicitudService)
    {
        $this->solicitudService = $solicitudService;
    }

    /**
     * Muestra el formulario de cotización (Magic Link)
     * 
     * GET /portal/cotizacion/{token}
     */
    public function mostrarCotizacion(string $token): Response|RedirectResponse
    {
        $cotizacion = CotizacionProveedor::buscarPorToken($token);

        // Validar token
        if (!$cotizacion) {
            return Inertia::render('Portal/Error', [
                'titulo' => 'Enlace no válido',
                'mensaje' => 'El enlace de cotización no es válido o ha expirado.',
            ]);
        }

        // Cargar relaciones
        $cotizacion->load([
            'solicitud.detalles.producto.categoria',
            'solicitud.estado',
            'proveedor',
            'respuestas.producto',
        ]);

        $solicitud = $cotizacion->solicitud;

        // Verificar si puede responder
        if ($solicitud->haVencido()) {
            return Inertia::render('Portal/Error', [
                'titulo' => 'Solicitud vencida',
                'mensaje' => 'El plazo para cotizar ha finalizado el ' . $solicitud->fecha_vencimiento->format('d/m/Y') . '.',
            ]);
        }

        // Si ya respondió, mostrar agradecimiento
        if ($cotizacion->fecha_respuesta) {
            return Inertia::render('Portal/Agradecimiento', [
                'proveedor' => $cotizacion->proveedor->razon_social,
                'fechaRespuesta' => $cotizacion->fecha_respuesta->format('d/m/Y H:i'),
                'solicitud' => $solicitud->codigo_solicitud,
            ]);
        }

        // Preparar datos para el formulario
        return Inertia::render('Portal/Cotizacion', [
            'token' => $token,
            'proveedor' => [
                'razon_social' => $cotizacion->proveedor->razon_social,
            ],
            'solicitud' => [
                'codigo' => $solicitud->codigo_solicitud,
                'fecha_emision' => $solicitud->fecha_emision->format('d/m/Y'),
                'fecha_vencimiento' => $solicitud->fecha_vencimiento->format('d/m/Y'),
                'observaciones' => $solicitud->observaciones,
            ],
            'productos' => $solicitud->detalles->map(function ($detalle) {
                return [
                    'id' => $detalle->producto_id,
                    'nombre' => $detalle->producto->nombre,
                    'categoria' => $detalle->producto->categoria?->nombre ?? 'Sin categoría',
                    'cantidad_sugerida' => $detalle->cantidad_sugerida,
                    'observaciones' => $detalle->observaciones,
                ];
            }),
        ]);
    }

    /**
     * Procesa la respuesta del proveedor
     * 
     * POST /portal/cotizacion/{token}/responder
     */
    public function responderCotizacion(Request $request, string $token): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        $cotizacion = CotizacionProveedor::buscarPorToken($token);

        if (!$cotizacion) {
            return redirect()->route('portal.cotizacion', $token)
                ->with('error', 'Token no válido');
        }

        // Validar request
        $validated = $request->validate([
            'respuestas' => 'required|array|min:1',
            'respuestas.*.producto_id' => 'required|exists:productos,id',
            'respuestas.*.precio_unitario' => 'required|numeric|min:0',
            'respuestas.*.cantidad_disponible' => 'required|integer|min:1',
            'respuestas.*.plazo_entrega_dias' => 'required|integer|min:1',
            'respuestas.*.observaciones' => 'nullable|string|max:500',
        ], [
            'respuestas.required' => 'Debe cotizar al menos un producto',
            'respuestas.*.precio_unitario.required' => 'El precio es obligatorio',
            'respuestas.*.precio_unitario.min' => 'El precio no puede ser negativo',
            'respuestas.*.cantidad_disponible.required' => 'La cantidad es obligatoria',
            'respuestas.*.cantidad_disponible.min' => 'La cantidad debe ser al menos 1',
            'respuestas.*.plazo_entrega_dias.required' => 'El plazo de entrega es obligatorio',
            'respuestas.*.plazo_entrega_dias.min' => 'El plazo debe ser al menos 1 día',
        ]);

        try {
            $this->solicitudService->registrarRespuestaProveedor(
                $cotizacion,
                $validated['respuestas']
            );

            // Recargar para obtener fecha_respuesta actualizada
            $cotizacion->refresh();

            // Retornar directamente la vista de agradecimiento
            return Inertia::render('Portal/Agradecimiento', [
                'proveedor' => $cotizacion->proveedor->razon_social,
                'fechaRespuesta' => $cotizacion->fecha_respuesta->format('d/m/Y H:i'),
                'solicitud' => $cotizacion->solicitud->codigo_solicitud,
            ]);

        } catch (\Exception $e) {
            return redirect()->route('portal.cotizacion', $token)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * El proveedor rechaza cotizar
     * 
     * POST /portal/cotizacion/{token}/rechazar
     */
    public function rechazarCotizacion(Request $request, string $token): RedirectResponse
    {
        $cotizacion = CotizacionProveedor::buscarPorToken($token);

        if (!$cotizacion) {
            return redirect()->route('portal.cotizacion', $token)
                ->with('error', 'Token no válido');
        }

        $validated = $request->validate([
            'motivo' => 'required|string|max:500',
        ], [
            'motivo.required' => 'Por favor indique el motivo del rechazo',
        ]);

        try {
            $this->solicitudService->registrarRechazoProveedor(
                $cotizacion,
                $validated['motivo']
            );

            return Inertia::location(route('portal.agradecimiento.rechazo', $token));

        } catch (\Exception $e) {
            return redirect()->route('portal.cotizacion', $token)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Página de agradecimiento por rechazo
     */
    public function agradecimientoRechazo(string $token): Response
    {
        $cotizacion = CotizacionProveedor::buscarPorToken($token);

        return Inertia::render('Portal/AgradecimientoRechazo', [
            'proveedor' => $cotizacion?->proveedor?->razon_social ?? 'Proveedor',
        ]);
    }
}
