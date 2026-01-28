<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import TecnicoAlertBell from '@/Components/TecnicoAlertBell.vue';

const showingSidebar = ref(false);
const showingCompras = ref(false);
const showingReportes = ref(false);
const showingMaestros = ref(false);
const showingUsuariosRoles = ref(false);
const showingAdmin = ref(false);

const page = usePage(); 

// Helper para verificar permisos
const can = (permiso) => {
    const permisos = page.props.auth.permisos || [];
    if (page.props.auth.user?.role === 'administrador') return true;
    return permisos.includes(permiso);
};

// Helper para verificar si tiene alguno de los permisos
const canAny = (listaPermisos) => {
    return listaPermisos.some(p => can(p));
};

// =====================================================
// SECCIÓN 1: OPERACIONES DIARIAS
// =====================================================
const operacionesItems = computed(() => {
    const items = [];

    if (can('dashboard.ver')) {
        items.push({ 
            name: 'Dashboard', 
            route: 'dashboard', 
            icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        });
    }

    if (can('ventas.ver')) {
        items.push({ 
            name: 'Ventas', 
            route: 'ventas.index', 
            icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        });
    }

    if (can('pagos.ver')) {
        items.push({ 
            name: 'Pagos', 
            route: 'pagos.index', 
            icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        });
    }

    // Gastos y Pérdidas (acceso general, sin permiso específico por ahora)
    items.push({ 
        name: 'Gastos', 
        route: 'gastos.index', 
        icon: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z',
    });

    if (can('clientes.ver')) {
        items.push({ 
            name: 'Clientes', 
            route: 'clientes.index', 
            icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        });
    }

    return items;
});

// =====================================================
// SECCIÓN 2: SERVICIO TÉCNICO
// =====================================================
const servicioTecnicoItems = computed(() => {
    const items = [];

    if (can('reparaciones.ver')) {
        items.push({ 
            name: 'Reparaciones', 
            route: 'reparaciones.index', 
            icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        });
    }

    if (can('alertas.ver')) {
        items.push({ 
            name: 'Alertas SLA', 
            route: 'alertas.index', 
            icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        });
    }

    if (can('configuracion.editar')) {
        items.push({ 
            name: 'Bonificaciones', 
            route: 'bonificaciones.index', 
            icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        });
    }

    return items;
});

// =====================================================
// SECCIÓN 3: INVENTARIO
// =====================================================
const inventarioItems = computed(() => {
    const items = [];

    if (can('productos.ver')) {
        items.push({ 
            name: 'Productos', 
            route: 'productos.index', 
            icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        });
    }

    if (can('stock.ver')) {
        items.push({ 
            name: 'Stock', 
            route: 'productos.stock', 
            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        });
    }

    if (can('proveedores.ver')) {
        items.push({ 
            name: 'Proveedores', 
            route: 'proveedores.index', 
            icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 
        });
    }

    return items;
});

// =====================================================
// SECCIÓN 4: COMPRAS (Submenú)
// =====================================================
const comprasItems = computed(() => {
    const items = [];
    
    if (can('compras.monitoreo')) items.push({ name: 'Monitoreo de Stock', route: 'monitoreo-stock.index' });
    if (can('compras.cotizaciones.ver')) items.push({ name: 'Solicitudes de Cotización', route: 'solicitudes-cotizacion.index' });
    if (canAny(['compras.ordenes.ver', 'compras.ordenes.crear'])) items.push({ name: 'Ofertas de Compra', route: 'ofertas.index' });
    if (can('compras.ordenes.ver')) items.push({ name: 'Órdenes de Compra', route: 'ordenes.index' });
    if (can('compras.recepciones.ver')) items.push({ name: 'Recepción de Mercadería', route: 'recepciones.index' });
    
    return items;
});

// =====================================================
// SECCIÓN 5: REPORTES (Submenú)
// =====================================================
const reportesItems = computed(() => {
    if (!can('reportes.ver')) return [];

    const items = [];
    
    // Reporte Mensual consolidado siempre primero
    items.push({ name: 'Reporte Mensual', route: 'reportes.mensual' });
    
    if (can('reportes.stock')) items.push({ name: 'Stock y Precios', route: 'reportes.stock' });
    if (can('reportes.ventas')) items.push({ name: 'Ventas', route: 'reportes.ventas' });
    if (can('reportes.reparaciones')) items.push({ name: 'Reparaciones', route: 'reportes.reparaciones' });
    if (can('reportes.compras')) items.push({ name: 'Compras', route: 'reportes.compras' });
    
    return items;
});

// =====================================================
// SECCIÓN 6: ADMINISTRACIÓN
// =====================================================
const usuariosRolesItems = computed(() => {
    const items = [];
    
    if (can('usuarios.gestionar')) items.push({ name: 'Usuarios', route: 'admin.usuarios.index' });
    if (can('roles.gestionar')) items.push({ name: 'Roles y Permisos', route: 'admin.roles.index' });
    
    return items;
});

const maestrosItems = computed(() => {
    if (!can('maestros.gestionar')) return [];

    return [
        { name: 'Categorías Prod.', route: 'admin.categorias.index' },
        { name: 'Estados Prod.', route: 'admin.estados-producto.index' },
        { name: 'Marcas', route: 'admin.marcas.index' },
        { name: 'Modelos', route: 'admin.modelos.index' },
        { name: 'Unidades Medida', route: 'admin.unidades-medida.index' },
        { name: 'Depósitos', route: 'admin.depositos.index' },
        { name: 'Medios de Pago', route: 'admin.medios-pago.index' },
        { name: 'Estados Reparación', route: 'admin.estados-reparacion.index' },
        { name: 'Motivos de Demora', route: 'admin.motivos-demora.index' },
        { name: 'Provincias', route: 'admin.provincias.index' },
        { name: 'Localidades', route: 'admin.localidades.index' },
        { name: 'Tipos de Cliente', route: 'admin.tipos-cliente.index' },
        { name: 'Estados de Cliente', route: 'admin.estados-cliente.index' },
        { name: 'Categorías de Gasto', route: 'admin.categorias-gasto.index' },
    ];
});

const adminItems = computed(() => {
    const items = [];
    
    if (can('descuentos.ver')) items.push({ name: 'Descuentos', route: 'descuentos.index', icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' });
    if (can('comprobantes.ver')) items.push({ name: 'Comprobantes', route: 'comprobantes.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' });
    if (can('auditoria.ver')) items.push({ name: 'Auditoría', route: 'auditorias.index', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' });
    
    return items;
});

// Helper para verificar si el usuario puede ver la sección de administración
const canViewAdmin = computed(() => {
    return can('usuarios.gestionar') || can('roles.gestionar') || can('maestros.gestionar') || 
           can('configuracion.editar') || can('descuentos.ver') || can('comprobantes.ver') || can('auditoria.ver');
});

// Verifica si alguna ruta del submenú está activa para dejarlo abierto
const isMaestrosActive = computed(() => {
    return maestrosItems.value.some(item => route().current(item.route));
});

const isComprasActive = computed(() => {
    return comprasItems.value.some(item => route().current(item.route) || route().current(item.route.replace('.index', '*')));
});

const isUsuariosRolesActive = computed(() => {
    return usuariosRolesItems.value.some(item => route().current(item.route) || route().current(item.route.replace('.index', '*')));
});

const isReportesActive = computed(() => {
    return reportesItems.value.some(item => route().current(item.route));
});

const isAdminActive = computed(() => {
    return adminItems.value.some(item => route().current(item.route)) ||
           route().current('configuracion.*') || route().current('plantillas-whatsapp.*');
});

// Si hay una ruta activa adentro, abrimos el menú por defecto
if (isMaestrosActive.value) {
    showingMaestros.value = true;
}
if (isComprasActive.value) {
    showingCompras.value = true;
}
if (isUsuariosRolesActive.value) {
    showingUsuariosRoles.value = true;
}
if (isReportesActive.value) {
    showingReportes.value = true;
}
if (isAdminActive.value || isMaestrosActive.value || isUsuariosRolesActive.value) {
    showingAdmin.value = true;
}

const isActive = (routeName) => {
    return route().current(routeName) || route().current(routeName.replace('.index', '*'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex">
        
        <aside 
            class="bg-white w-64 min-h-screen flex flex-col border-r border-gray-200 fixed md:relative z-30 transition-transform duration-300 ease-in-out"
            :class="showingSidebar ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        >
            <div class="h-16 flex items-center justify-center border-b border-gray-200">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <div class="bg-indigo-600 p-1.5 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <span class="text-xl font-bold text-gray-800 tracking-tight">TecnoSoluciones</span>
                </Link>
            </div>

            <nav class="flex-1 py-4 px-3 overflow-y-auto custom-scrollbar">
                
                <!-- ============================================ -->
                <!-- SECCIÓN 1: OPERACIONES DIARIAS -->
                <!-- ============================================ -->
                <div v-if="operacionesItems.length > 0" class="mb-4">
                    <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Operaciones
                    </p>
                    <Link 
                        v-for="item in operacionesItems" 
                        :key="item.name"
                        :href="route(item.route)"
                        class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                        :class="isActive(item.route) 
                            ? 'bg-indigo-50 text-indigo-700' 
                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" 
                             :class="isActive(item.route) ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                        {{ item.name }}
                    </Link>
                </div>

                <!-- ============================================ -->
                <!-- SECCIÓN 2: SERVICIO TÉCNICO -->
                <!-- ============================================ -->
                <div v-if="servicioTecnicoItems.length > 0" class="mb-4">
                    <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Servicio Técnico
                    </p>
                    <Link 
                        v-for="item in servicioTecnicoItems" 
                        :key="item.name"
                        :href="route(item.route)"
                        class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                        :class="isActive(item.route) 
                            ? 'bg-orange-50 text-orange-700' 
                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" 
                             :class="isActive(item.route) ? 'text-orange-600' : 'text-gray-400 group-hover:text-gray-500'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                        {{ item.name }}
                    </Link>
                </div>

                <!-- ============================================ -->
                <!-- SECCIÓN 3: INVENTARIO -->
                <!-- ============================================ -->
                <div v-if="inventarioItems.length > 0" class="mb-4">
                    <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Inventario
                    </p>
                    <Link 
                        v-for="item in inventarioItems" 
                        :key="item.name"
                        :href="route(item.route)"
                        class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                        :class="isActive(item.route) 
                            ? 'bg-emerald-50 text-emerald-700' 
                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" 
                             :class="isActive(item.route) ? 'text-emerald-600' : 'text-gray-400 group-hover:text-gray-500'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                        {{ item.name }}
                    </Link>
                </div>

                <!-- ============================================ -->
                <!-- SECCIÓN 4: COMPRAS (Submenú desplegable) -->
                <!-- ============================================ -->
                <div v-if="comprasItems.length > 0" class="mb-4">
                    <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Compras
                    </p>
                    <button 
                        @click="showingCompras = !showingCompras"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 focus:outline-none transition-colors"
                        :class="{ 'bg-blue-50 text-blue-700': showingCompras || isComprasActive }"
                    >
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" :class="showingCompras || isComprasActive ? 'text-blue-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Gestión de Compras
                        </div>
                        <svg 
                            class="w-4 h-4 transform transition-transform duration-200" 
                            :class="[showingCompras ? 'rotate-180' : '', showingCompras || isComprasActive ? 'text-blue-600' : 'text-gray-400']"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-show="showingCompras" class="mt-1 space-y-1 pl-11">
                        <Link 
                            v-for="subitem in comprasItems" 
                            :key="subitem.name"
                            :href="route(subitem.route)"
                            class="block px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="isActive(subitem.route)
                                ? 'text-blue-700 bg-blue-50'
                                : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
                        >
                            {{ subitem.name }}
                        </Link>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- SECCIÓN 5: REPORTES (Submenú desplegable) -->
                <!-- ============================================ -->
                <div v-if="reportesItems.length > 0" class="mb-4">
                    <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Reportes
                    </p>
                    <button 
                        @click="showingReportes = !showingReportes"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 focus:outline-none transition-colors"
                        :class="{ 'bg-purple-50 text-purple-700': showingReportes || isReportesActive }"
                    >
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" :class="showingReportes || isReportesActive ? 'text-purple-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Ver Reportes
                        </div>
                        <svg 
                            class="w-4 h-4 transform transition-transform duration-200" 
                            :class="[showingReportes ? 'rotate-180' : '', showingReportes || isReportesActive ? 'text-purple-600' : 'text-gray-400']"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-show="showingReportes" class="mt-1 space-y-1 pl-11">
                        <Link 
                            v-for="subitem in reportesItems" 
                            :key="subitem.name"
                            :href="route(subitem.route)"
                            class="block px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="isActive(subitem.route)
                                ? 'text-purple-700 bg-purple-50'
                                : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
                        >
                            {{ subitem.name }}
                        </Link>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- SECCIÓN 6: ADMINISTRACIÓN -->
                <!-- ============================================ -->
                <div v-if="canViewAdmin" class="mb-4">
                    <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Administración
                    </p>
                    
                    <!-- Usuarios y Roles -->
                    <div v-if="usuariosRolesItems.length > 0">
                        <button 
                            @click="showingUsuariosRoles = !showingUsuariosRoles"
                            class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 focus:outline-none transition-colors"
                            :class="{ 'bg-gray-100': showingUsuariosRoles || isUsuariosRolesActive }"
                        >
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Usuarios y Roles
                            </div>
                            <svg 
                                class="w-4 h-4 text-gray-400 transform transition-transform duration-200" 
                                :class="{ 'rotate-180': showingUsuariosRoles }"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-show="showingUsuariosRoles" class="mt-1 space-y-1 pl-11">
                            <Link 
                                v-for="subitem in usuariosRolesItems" 
                                :key="subitem.name"
                                :href="route(subitem.route)"
                                class="block px-3 py-2 rounded-md text-sm font-medium transition-colors"
                                :class="isActive(subitem.route)
                                    ? 'text-indigo-700 bg-indigo-50'
                                    : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
                            >
                                {{ subitem.name }}
                            </Link>
                        </div>
                    </div>

                    <!-- Gestión de Maestros -->
                    <div v-if="maestrosItems.length > 0">
                        <button 
                            @click="showingMaestros = !showingMaestros"
                            class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 focus:outline-none transition-colors"
                            :class="{ 'bg-gray-100': showingMaestros || isMaestrosActive }"
                        >
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                                Gestión de Maestros
                            </div>
                            <svg 
                                class="w-4 h-4 text-gray-400 transform transition-transform duration-200" 
                                :class="{ 'rotate-180': showingMaestros }"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-show="showingMaestros" class="mt-1 space-y-1 pl-11">
                            <Link 
                                v-for="subitem in maestrosItems" 
                                :key="subitem.name"
                                :href="route(subitem.route)"
                                class="block px-3 py-2 rounded-md text-sm font-medium transition-colors"
                                :class="isActive(subitem.route)
                                    ? 'text-indigo-700 bg-indigo-50'
                                    : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
                            >
                                {{ subitem.name }}
                            </Link>
                        </div>
                    </div>

                    <!-- Parámetros del Sistema -->
                    <Link 
                        v-if="can('configuracion.editar')"
                        :href="route('configuracion.index')"
                        class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                        :class="isActive('configuracion.index') 
                            ? 'bg-gray-100 text-gray-900' 
                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" 
                             :class="isActive('configuracion.index') ? 'text-gray-600' : 'text-gray-400 group-hover:text-gray-500'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Parámetros
                    </Link>

                    <!-- Plantillas WhatsApp -->
                    <Link 
                        v-if="can('configuracion.editar')"
                        :href="route('plantillas-whatsapp.index')" 
                        class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                        :class="isActive('plantillas-whatsapp.index') || isActive('plantillas-whatsapp.edit')
                            ? 'bg-green-50 text-green-700' 
                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" 
                             :class="isActive('plantillas-whatsapp.index') || isActive('plantillas-whatsapp.edit') ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-500'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        Plantillas WhatsApp
                    </Link>

                    <!-- Items administrativos adicionales -->
                    <Link 
                        v-for="item in adminItems" 
                        :key="item.name"
                        :href="route(item.route)"
                        class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                        :class="isActive(item.route) 
                            ? 'bg-gray-100 text-gray-900' 
                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" 
                             :class="isActive(item.route) ? 'text-gray-600' : 'text-gray-400 group-hover:text-gray-500'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                        {{ item.name }}
                    </Link>
                </div>

            </nav>

            <div class="p-4 border-t border-gray-200">
                <p class="text-xs text-center text-gray-400">v1.0.0 - TecnoSoluciones</p>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex justify-between items-center px-4 sm:px-6 lg:px-8 z-20 relative">
                <button @click="showingSidebar = !showingSidebar" class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>

                <div class="flex-1 min-w-0">
                      <div v-if="$slots.header" class="text-lg font-bold text-gray-900 sm:truncate">
                        <slot name="header" />
                      </div>
                </div>

                <div class="ml-4 flex items-center gap-4">
                    <!-- Notificaciones generales para admin -->
                    <NotificationBell v-if="$page.props.auth.user.role === 'administrador'" />
                    <!-- Alertas SLA solo para técnico (el admin las ve en el menú Alertas) -->
                    <TecnicoAlertBell v-if="$page.props.auth.user.role === 'tecnico'" />
                    
                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="flex items-center max-w-xs bg-white rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 hover:bg-gray-50 p-1 pr-2">
                                    <img 
                                        v-if="$page.props.auth.user.foto_perfil"
                                        :src="`/storage/${$page.props.auth.user.foto_perfil}`"
                                        :alt="$page.props.auth.user.name"
                                        class="h-9 w-9 rounded-full object-cover border-2 border-white shadow-sm"
                                    />
                                    <div 
                                        v-else
                                        class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold border-2 border-white shadow-sm"
                                    >
                                        {{ $page.props.auth.user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) }}
                                    </div>
                                    <div class="ml-2 text-left hidden md:block">
                                        <div class="text-sm font-semibold text-gray-800 leading-none">
                                            {{ $page.props.auth.user.name }}
                                        </div>
                                        <div class="text-xs text-gray-500 font-medium mt-0.5 capitalize">
                                            {{ $page.props.auth.user.role || 'Administrador' }}
                                        </div>
                                    </div>
                                    <svg class="ml-2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <span class="text-xs text-gray-400">Gestionar Cuenta</span>
                                </div>
                                <DropdownLink :href="route('profile.edit')">Mi Perfil</DropdownLink>
                                <div class="border-t border-gray-100"></div>
                                <DropdownLink :href="route('logout')" method="post" as="button">Cerrar Sesión</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
                <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="mb-6 animate-fade-in-down">
                    <div v-if="$page.props.flash?.success" class="rounded-md bg-green-50 p-4 border-l-4 border-green-400 shadow-sm flex">
                        <svg class="h-5 w-5 text-green-400 mr-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <p class="text-sm font-medium text-green-800">{{ $page.props.flash?.success }}</p>
                    </div>
                    <div v-if="$page.props.flash?.error" class="rounded-md bg-red-50 p-4 border-l-4 border-red-400 shadow-sm flex">
                        <svg class="h-5 w-5 text-red-400 mr-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <p class="text-sm font-medium text-red-800">{{ $page.props.flash?.error }}</p>
                    </div>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>