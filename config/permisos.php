<?php

/**
 * Configuración de Permisos del Sistema
 * 
 * Este archivo define todos los permisos disponibles.
 * Los permisos se agrupan por módulo para facilitar la UI.
 */

return [
    'modulos' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'permisos' => [
                'dashboard.ver' => 'Ver Dashboard',
            ],
        ],
        
        'ventas' => [
            'label' => 'Ventas',
            'permisos' => [
                'ventas.ver' => 'Ver listado de ventas',
                'ventas.crear' => 'Registrar nuevas ventas',
                'ventas.anular' => 'Anular ventas',
            ],
        ],
        
        'productos' => [
            'label' => 'Productos',
            'permisos' => [
                'productos.ver' => 'Ver catálogo de productos',
                'productos.crear' => 'Crear nuevos productos',
                'productos.editar' => 'Modificar productos',
                'productos.eliminar' => 'Dar de baja productos',
            ],
        ],
        
        'stock' => [
            'label' => 'Stock',
            'permisos' => [
                'stock.ver' => 'Consultar stock',
                'stock.ajustar' => 'Realizar ajustes de stock',
            ],
        ],
        
        'reparaciones' => [
            'label' => 'Reparaciones',
            'permisos' => [
                'reparaciones.ver' => 'Ver listado de reparaciones',
                'reparaciones.crear' => 'Registrar ingreso de equipos',
                'reparaciones.editar' => 'Modificar datos de reparaciones',
                'reparaciones.cambiar_estado' => 'Cambiar estado de reparaciones',
            ],
        ],
        
        'proveedores' => [
            'label' => 'Proveedores',
            'permisos' => [
                'proveedores.ver' => 'Ver listado de proveedores',
                'proveedores.crear' => 'Registrar nuevos proveedores',
                'proveedores.editar' => 'Modificar proveedores',
            ],
        ],
        
        'clientes' => [
            'label' => 'Clientes',
            'permisos' => [
                'clientes.ver' => 'Ver listado de clientes',
                'clientes.crear' => 'Registrar nuevos clientes',
                'clientes.editar' => 'Modificar datos de clientes',
            ],
        ],
        
        'pagos' => [
            'label' => 'Pagos',
            'permisos' => [
                'pagos.ver' => 'Ver listado de pagos',
                'pagos.crear' => 'Registrar pagos',
                'pagos.anular' => 'Anular pagos',
            ],
        ],
        
        'descuentos' => [
            'label' => 'Descuentos',
            'permisos' => [
                'descuentos.ver' => 'Ver descuentos',
                'descuentos.crear' => 'Crear descuentos',
                'descuentos.editar' => 'Modificar descuentos',
            ],
        ],
        
        'comprobantes' => [
            'label' => 'Comprobantes',
            'permisos' => [
                'comprobantes.ver' => 'Ver comprobantes',
                'comprobantes.anular' => 'Anular comprobantes',
                'comprobantes.reemitir' => 'Reemitir comprobantes',
            ],
        ],
        
        'auditoria' => [
            'label' => 'Auditoría',
            'permisos' => [
                'auditoria.ver' => 'Ver registro de auditoría',
            ],
        ],
        
        'reportes' => [
            'label' => 'Reportes',
            'permisos' => [
                'reportes.ver' => 'Ver reportes',
                'reportes.ventas' => 'Reportes de ventas',
                'reportes.stock' => 'Reportes de stock',
                'reportes.reparaciones' => 'Reportes de reparaciones',
                'reportes.compras' => 'Reportes de compras',
                'reportes.exportar' => 'Exportar reportes',
            ],
        ],
        
        'alertas' => [
            'label' => 'Alertas SLA',
            'permisos' => [
                'alertas.ver' => 'Ver alertas de SLA',
            ],
        ],
        
        'compras' => [
            'label' => 'Compras',
            'permisos' => [
                'compras.ordenes.ver' => 'Ver órdenes de compra',
                'compras.ordenes.crear' => 'Crear órdenes de compra',
                'compras.recepciones.ver' => 'Ver recepciones',
                'compras.recepciones.crear' => 'Registrar recepciones',
                'compras.cotizaciones.ver' => 'Ver cotizaciones',
                'compras.cotizaciones.crear' => 'Solicitar cotizaciones',
                'compras.monitoreo' => 'Monitoreo de stock',
            ],
        ],
        
        'administracion' => [
            'label' => 'Administración',
            'permisos' => [
                'configuracion.ver' => 'Ver configuración',
                'configuracion.editar' => 'Editar configuración',
                'maestros.gestionar' => 'Gestionar tablas maestras',
                'usuarios.gestionar' => 'Gestionar usuarios',
                'roles.gestionar' => 'Gestionar roles y permisos',
                'plantillas.gestionar' => 'Gestionar plantillas WhatsApp',
            ],
        ],
    ],
];
