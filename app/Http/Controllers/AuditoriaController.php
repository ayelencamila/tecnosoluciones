<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Consulta del Log de Auditoría del sistema (CU-Consultar Log de Auditoría).
 *
 * Acceso restringido a administradores (ver gating en routes/web.php).
 *
 * Scoping multi-tenant MANUAL (el proyecto no usa Global Scopes): la consulta
 * se filtra siempre por la empresa del usuario autenticado, de modo que un
 * administrador nunca ve el rastro de auditoría de otra empresa (RNF4).
 *
 * Salida (Kendall): pantalla de propósito único (explotar el log), con filtros
 * cuyos valores provienen de los datos REALES para evitar predisposición y
 * garantizar que siempre matcheen.
 */
class AuditoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $empresaId = $request->user()->empresa_id;

        $base = Auditoria::where('empresa_id', $empresaId);

        $auditorias = (clone $base)
            ->with(['usuario:id,name,rol_id', 'usuario.rol:rol_id,nombre'])
            ->when($request->filled('accion'), fn ($q) => $q->where('accion', $request->accion))
            ->when($request->filled('tabla'), fn ($q) => $q->where('tabla_afectada', $request->tabla))
            ->when($request->filled('usuario'), fn ($q) => $q->whereHas(
                'usuario',
                fn ($u) => $u->where('name', 'like', '%'.$request->usuario.'%')
            ))
            ->when($request->filled('fecha_desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->fecha_desde))
            ->when($request->filled('fecha_hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->fecha_hasta))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Auditorias/Index', [
            'auditorias' => $auditorias,
            'filtros' => $request->only(['accion', 'tabla', 'usuario', 'fecha_desde', 'fecha_hasta']),
            // Valores reales (scoped a la empresa) para poblar los selects de filtro.
            'accionesDisponibles' => (clone $base)->distinct()->orderBy('accion')->pluck('accion'),
            'tablasDisponibles' => (clone $base)->whereNotNull('tabla_afectada')
                ->distinct()->orderBy('tabla_afectada')->pluck('tabla_afectada'),
        ]);
    }
}
