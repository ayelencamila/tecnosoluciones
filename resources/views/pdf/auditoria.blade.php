<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Log de Auditoría</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #1f2937; margin: 0; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { font-size: 16px; margin: 0 0 2px; color: #4f46e5; }
        .header .sub { font-size: 9px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #eef2ff; color: #3730a3; text-align: left;
            padding: 5px 6px; font-size: 8px; text-transform: uppercase;
            border-bottom: 1px solid #c7d2fe;
        }
        tbody td { padding: 4px 6px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-size: 8px; }
        .muted { color: #9ca3af; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 8px; color: #9ca3af; }
        .footer .pagenum:before { content: counter(page) " / " counter(pages); }
    </style>
</head>
<body>
    <div class="header">
        <h1>Log de Auditoría</h1>
        <div class="sub">
            Bitácora inmutable del sistema &middot;
            Generado el {{ $generado->format('d/m/Y H:i') }} &middot;
            {{ $registros->count() }} registro{{ $registros->count() === 1 ? '' : 's' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha / Hora</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Módulo</th>
                <th>Registro</th>
                <th>IP</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $r)
                <tr>
                    <td>{{ optional($r->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>
                        {{ $r->usuario->name ?? 'Sistema' }}
                        @if ($r->usuario?->rol?->nombre)
                            <span class="muted">({{ $r->usuario->rol->nombre }})</span>
                        @endif
                    </td>
                    <td>{{ $r->accion }}</td>
                    <td>{{ $r->tabla_afectada ?? '—' }}</td>
                    <td>{{ $r->registro_id ? '#'.$r->registro_id : '—' }}</td>
                    <td class="mono">{{ $r->ip ?? '—' }}</td>
                    <td>{{ $r->detalles ?? $r->motivo ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted" style="text-align:center; padding:20px;">Sin registros para los filtros aplicados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span class="pagenum"></span>
    </div>
</body>
</html>
