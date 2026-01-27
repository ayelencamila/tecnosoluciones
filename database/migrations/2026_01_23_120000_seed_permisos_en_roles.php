<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para asignar permisos a los roles existentes
 * Sistema de permisos granular que permite configurar desde la UI
 */
return new class extends Migration
{
    /**
     * Permisos disponibles en el sistema, agrupados por módulo
     */
    private function getPermisosDisponibles(): array
    {
        return [
            // Módulo Dashboard
            'dashboard.ver' => 'Ver Dashboard',
            
            // Módulo Ventas
            'ventas.ver' => 'Ver Ventas',
            'ventas.crear' => 'Crear Ventas',
            'ventas.anular' => 'Anular Ventas',
            
            // Módulo Productos
            'productos.ver' => 'Ver Productos',
            'productos.crear' => 'Crear Productos',
            'productos.editar' => 'Editar Productos',
            'productos.eliminar' => 'Eliminar Productos',
            
            // Módulo Stock
            'stock.ver' => 'Ver Stock',
            'stock.ajustar' => 'Ajustar Stock',
            
            // Módulo Reparaciones
            'reparaciones.ver' => 'Ver Reparaciones',
            'reparaciones.crear' => 'Crear Reparaciones',
            'reparaciones.editar' => 'Editar Reparaciones',
            'reparaciones.cambiar_estado' => 'Cambiar Estado Reparaciones',
            
            // Módulo Proveedores
            'proveedores.ver' => 'Ver Proveedores',
            'proveedores.crear' => 'Crear Proveedores',
            'proveedores.editar' => 'Editar Proveedores',
            
            // Módulo Clientes
            'clientes.ver' => 'Ver Clientes',
            'clientes.crear' => 'Crear Clientes',
            'clientes.editar' => 'Editar Clientes',
            
            // Módulo Pagos
            'pagos.ver' => 'Ver Pagos',
            'pagos.crear' => 'Crear Pagos',
            'pagos.anular' => 'Anular Pagos',
            
            // Módulo Descuentos
            'descuentos.ver' => 'Ver Descuentos',
            'descuentos.crear' => 'Crear Descuentos',
            'descuentos.editar' => 'Editar Descuentos',
            
            // Módulo Comprobantes
            'comprobantes.ver' => 'Ver Comprobantes',
            'comprobantes.anular' => 'Anular Comprobantes',
            'comprobantes.reemitir' => 'Reemitir Comprobantes',
            
            // Módulo Auditoría
            'auditoria.ver' => 'Ver Auditoría',
            
            // Módulo Alertas SLA
            'alertas.ver' => 'Ver Alertas SLA',
            
            // Módulo Compras (submenú)
            'compras.ordenes.ver' => 'Ver Órdenes de Compra',
            'compras.ordenes.crear' => 'Crear Órdenes de Compra',
            'compras.recepciones.ver' => 'Ver Recepciones',
            'compras.recepciones.crear' => 'Crear Recepciones',
            'compras.cotizaciones.ver' => 'Ver Cotizaciones',
            'compras.cotizaciones.crear' => 'Crear Cotizaciones',
            'compras.monitoreo' => 'Monitoreo de Stock',
            
            // Módulo Configuración (Admin)
            'configuracion.ver' => 'Ver Configuración',
            'configuracion.editar' => 'Editar Configuración',
            'maestros.gestionar' => 'Gestionar Maestros',
            'usuarios.gestionar' => 'Gestionar Usuarios',
            'roles.gestionar' => 'Gestionar Roles',
            'plantillas.gestionar' => 'Gestionar Plantillas WhatsApp',
        ];
    }

    public function up(): void
    {
        // Permisos para Administrador (todos)
        $permisosAdmin = array_keys($this->getPermisosDisponibles());

        // Permisos para Vendedor
        $permisosVendedor = [
            'dashboard.ver',
            'ventas.ver', 'ventas.crear', 'ventas.anular',
            'productos.ver', 'productos.crear', 'productos.editar',
            'stock.ver', 'stock.ajustar',
            'reparaciones.ver', 'reparaciones.crear', 'reparaciones.editar',
            'proveedores.ver',
            'clientes.ver', 'clientes.crear', 'clientes.editar',
            'pagos.ver', 'pagos.crear',
            'descuentos.ver',
            'comprobantes.ver',
        ];

        // Permisos para Técnico
        $permisosTecnico = [
            'dashboard.ver',
            'reparaciones.ver', 'reparaciones.editar', 'reparaciones.cambiar_estado',
            'clientes.ver',
            'alertas.ver',
        ];

        // Actualizar roles
        DB::table('roles')->where('nombre', 'administrador')->update([
            'permisos' => json_encode($permisosAdmin),
            'updated_at' => now(),
        ]);

        DB::table('roles')->where('nombre', 'vendedor')->update([
            'permisos' => json_encode($permisosVendedor),
            'updated_at' => now(),
        ]);

        DB::table('roles')->where('nombre', 'tecnico')->update([
            'permisos' => json_encode($permisosTecnico),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->update([
            'permisos' => null,
            'updated_at' => now(),
        ]);
    }
};
