<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 10px;
        line-height: 1.4;
        color: #333;
        padding: 20px;
        position: relative;
    }

    /* Marca de agua */
    .watermark {
        position: fixed;
        top: 45%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-35deg);
        font-size: 38px;
        font-weight: bold;
        color: rgba(200, 200, 200, 0.20);
        white-space: nowrap;
        z-index: -1;
        pointer-events: none;
        letter-spacing: 3px;
    }

    .documento-no-fiscal {
        position: fixed;
        bottom: 10px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 8px;
        color: #9ca3af;
        border-top: 1px dashed #d1d5db;
        padding-top: 6px;
        margin: 0 20px;
    }

    /* Header */
    .report-header {
        border-bottom: 3px solid {{ $headerColor ?? '#2563eb' }};
        padding-bottom: 12px;
        margin-bottom: 15px;
    }

    .header-top {
        display: table;
        width: 100%;
    }

    .logo-section {
        display: table-cell;
        width: 55%;
        vertical-align: top;
    }

    .company-name {
        font-size: 20px;
        font-weight: bold;
        color: {{ $headerColor ?? '#2563eb' }};
    }

    .company-tagline {
        font-size: 9px;
        color: #6b7280;
    }

    .report-title-section {
        display: table-cell;
        width: 45%;
        text-align: right;
        vertical-align: top;
    }

    .report-title {
        font-size: 16px;
        font-weight: bold;
        color: #1f2937;
    }

    .report-period {
        font-size: 11px;
        color: {{ $headerColor ?? '#2563eb' }};
        font-weight: bold;
        margin-top: 3px;
    }

    .report-date {
        font-size: 9px;
        color: #9ca3af;
        margin-top: 2px;
    }

    /* KPIs */
    .kpi-row {
        display: table;
        width: 100%;
        margin-bottom: 15px;
    }

    .kpi-card {
        display: table-cell;
        text-align: center;
        padding: 10px 8px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        vertical-align: top;
    }

    .kpi-card:first-child {
        border-radius: 5px 0 0 5px;
    }

    .kpi-card:last-child {
        border-radius: 0 5px 5px 0;
    }

    .kpi-label {
        font-size: 8px;
        font-weight: bold;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .kpi-value {
        font-size: 16px;
        font-weight: bold;
        color: #1f2937;
        margin-top: 2px;
    }

    .kpi-value.positive { color: #059669; }
    .kpi-value.negative { color: #dc2626; }
    .kpi-value.accent { color: {{ $headerColor ?? '#2563eb' }}; }

    /* Tabla de datos */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }

    .data-table thead {
        background: {{ $headerColor ?? '#2563eb' }};
    }

    .data-table th {
        color: white;
        font-weight: bold;
        padding: 8px 6px;
        text-align: left;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .data-table th.text-right { text-align: right; }
    .data-table th.text-center { text-align: center; }

    .data-table td {
        padding: 6px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 9px;
    }

    .data-table td.text-right { text-align: right; }
    .data-table td.text-center { text-align: center; }

    .data-table tbody tr:nth-child(even) {
        background: #f9fafb;
    }

    .data-table tfoot td {
        font-weight: bold;
        padding: 8px 6px;
        border-top: 2px solid {{ $headerColor ?? '#2563eb' }};
        background: #f3f4f6;
    }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 8px;
        font-weight: bold;
    }

    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-info { background: #dbeafe; color: #1e40af; }
    .badge-gray { background: #f3f4f6; color: #374151; }

    /* Utilidades */
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .font-bold { font-weight: bold; }
    .text-muted { color: #9ca3af; }
    .mb-10 { margin-bottom: 10px; }
    .mt-10 { margin-top: 10px; }

    .section-title {
        font-size: 11px;
        font-weight: bold;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 4px;
        margin-bottom: 8px;
        margin-top: 15px;
    }

    /* Page break */
    .page-break { page-break-before: always; }

    /* Resumen footer de tabla */
    .table-summary {
        font-size: 8px;
        color: #9ca3af;
        text-align: right;
        margin-top: 4px;
    }
</style>
