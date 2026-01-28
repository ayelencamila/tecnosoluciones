<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    kpis: Object,
    tablas: Object,
    userRole: String,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value || 0);
};

const isVendedor = computed(() => props.userRole === 'vendedor' || props.userRole === 'administrador');
const isTecnico = computed(() => props.userRole === 'tecnico' || props.userRole === 'administrador');
const isAdmin = computed(() => props.userRole === 'administrador');
// Vendedor y técnico pueden ver/ingresar reparaciones
const canAccessReparaciones = computed(() => ['vendedor', 'tecnico', 'administrador'].includes(props.userRole));

// Mapeo de estados de reparación a colores (usando nombres reales de la BD)
const estadoReparacion = {
    'Recibido': { color: 'bg-yellow-100 text-yellow-800' },
    'Diagnóstico': { color: 'bg-blue-100 text-blue-800' },
    'Presupuestado': { color: 'bg-indigo-100 text-indigo-800' },
    'En Reparación': { color: 'bg-purple-100 text-purple-800' },
    'Espera de Repuesto': { color: 'bg-orange-100 text-orange-800' },
    'Reparado': { color: 'bg-green-100 text-green-800' },
    'Entregado': { color: 'bg-gray-100 text-gray-800' },
    'Anulado': { color: 'bg-red-100 text-red-800' },
};

const getEstadoClass = (estado) => estadoReparacion[estado]?.color || 'bg-gray-100 text-gray-800';
const getEstadoLabel = (estado) => estado; // Ya viene con el nombre correcto
</script>

<template>
    <Head title="Tablero de Control" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tablero de Control
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- ============================================ -->
                <!-- KPIs PARA VENDEDOR Y ADMIN                   -->
                <!-- ============================================ -->
                <div v-if="isVendedor" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    
                    <!-- Ventas de Hoy -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-green-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-green-100 text-green-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Ventas de Hoy</p>
                                <p class="text-2xl font-bold text-gray-900 mb-2">{{ formatCurrency(kpis.ventasHoy) }}</p>
                                <p class="text-xs text-gray-500">
                                    Mes: {{ formatCurrency(kpis.ventasMes) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Por Cobrar -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-indigo-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Por Cobrar</p>
                                <p class="text-2xl font-bold text-gray-900 mb-2">{{ formatCurrency(kpis.deudaTotal) }}</p>
                                <div class="flex flex-col gap-1 text-xs">
                                    <span class="text-gray-500">{{ kpis.clientesConDeuda }} clientes</span>
                                    <span v-if="kpis.saldoVencido > 0" class="text-red-600 font-medium">
                                        {{ formatCurrency(kpis.saldoVencido) }} vencido
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Crítico -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-red-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-red-100 text-red-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Stock Crítico</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.stockCritico }}</p>
                                <p class="text-xs text-gray-500 mt-2">productos bajo mínimo</p>
                            </div>
                        </div>
                    </div>

                    <!-- Productos Activos -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-blue-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Productos Activos</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.totalProductos }}</p>
                                <p class="text-xs text-gray-500 mt-2">en catálogo</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- KPIs PARA TÉCNICO                            -->
                <!-- ============================================ -->
                <div v-if="isTecnico && !isVendedor" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    
                    <!-- Mis Reparaciones (solo técnico) -->
                    <div v-if="userRole === 'tecnico'" class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-indigo-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Mis Reparaciones</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.misReparaciones }}</p>
                                <p class="text-xs text-gray-500 mt-2">asignadas a mí</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pendientes de Diagnóstico -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-yellow-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Pend. Diagnóstico</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.reparacionesPendientes }}</p>
                                <p class="text-xs text-gray-500 mt-2">sin diagnosticar</p>
                            </div>
                        </div>
                    </div>

                    <!-- Listas para Entregar -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-green-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-green-100 text-green-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Listas para Entregar</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.reparacionesListas }}</p>
                                <p class="text-xs text-gray-500 mt-2">esperando retiro</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Crítico -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-red-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-red-100 text-red-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Stock Crítico</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.stockCritico }}</p>
                                <p class="text-xs text-gray-500 mt-2">productos bajo mínimo</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- KPIs ADICIONALES PARA ADMIN (Reparaciones)   -->
                <!-- ============================================ -->
                <div v-if="isAdmin" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    
                    <!-- Reparaciones en Curso -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-purple-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Reparaciones Activas</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.reparacionesEnCurso }}</p>
                                <p class="text-xs text-gray-500 mt-2">en proceso</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pendientes de Diagnóstico -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-yellow-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Pend. Diagnóstico</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.reparacionesPendientes }}</p>
                                <p class="text-xs text-gray-500 mt-2">sin diagnosticar</p>
                            </div>
                        </div>
                    </div>

                    <!-- Listas para Entregar -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-teal-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-teal-100 text-teal-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Listas para Entregar</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.reparacionesListas }}</p>
                                <p class="text-xs text-gray-500 mt-2">esperando retiro</p>
                            </div>
                        </div>
                    </div>

                    <!-- Clientes Activos -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-cyan-500 p-6 transition hover:shadow-lg min-h-[140px]">
                        <div class="flex flex-col h-full">
                            <div class="flex items-start justify-between mb-3">
                                <div class="p-3 rounded-full bg-cyan-100 text-cyan-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 mb-2">Clientes Activos</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.totalClientes }}</p>
                                <p class="text-xs text-gray-500 mt-2">en cartera</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- ACCESOS RÁPIDOS SEGÚN ROL                    -->
                <!-- ============================================ -->
                <div class="mb-8 flex flex-col sm:flex-row gap-4">
                    <!-- Vendedor y Admin: Nueva Venta -->
                    <Link v-if="isVendedor" :href="route('ventas.create')" class="flex-1">
                        <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-lg shadow-md transition flex justify-center items-center text-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Nueva Venta
                        </button>
                    </Link>

                    <!-- Vendedor y Admin: Registrar Cliente -->
                    <Link v-if="isVendedor" :href="route('clientes.create')" class="flex-1">
                        <button class="w-full bg-white hover:bg-gray-50 text-gray-700 font-bold py-4 px-6 rounded-lg shadow-md border border-gray-300 transition flex justify-center items-center text-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            Registrar Cliente
                        </button>
                    </Link>

                    <!-- Vendedor y Admin: Registrar Pago -->
                    <Link v-if="isVendedor" :href="route('pagos.create')" class="flex-1">
                        <button class="w-full bg-white hover:bg-gray-50 text-gray-700 font-bold py-4 px-6 rounded-lg shadow-md border border-gray-300 transition flex justify-center items-center text-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Registrar Pago
                        </button>
                    </Link>

                    <!-- Vendedor, Técnico y Admin: Nueva Reparación -->
                    <Link v-if="canAccessReparaciones" :href="route('reparaciones.create')" class="flex-1">
                        <button class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 px-6 rounded-lg shadow-md transition flex justify-center items-center text-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Nueva Reparación
                        </button>
                    </Link>
                </div>

                <!-- Segunda fila de accesos rápidos para técnico -->
                <div v-if="userRole === 'tecnico'" class="mb-8 flex flex-col sm:flex-row gap-4">
                    <!-- Técnico: Ver Mis Reparaciones -->
                    <Link :href="route('reparaciones.index')" class="flex-1">
                        <button class="w-full bg-white hover:bg-gray-50 text-gray-700 font-bold py-4 px-6 rounded-lg shadow-md border border-gray-300 transition flex justify-center items-center text-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Ver Mis Reparaciones
                        </button>
                    </Link>

                    <!-- Técnico: Consultar Stock -->
                    <Link :href="route('productos.stock')" class="flex-1">
                        <button class="w-full bg-white hover:bg-gray-50 text-gray-700 font-bold py-4 px-6 rounded-lg shadow-md border border-gray-300 transition flex justify-center items-center text-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            Consultar Stock
                        </button>
                    </Link>
                </div>

                <!-- ============================================ -->
                <!-- TABLAS DE RESUMEN                            -->
                <!-- ============================================ -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Últimas Ventas (Vendedor y Admin) -->
                    <div v-if="isVendedor && tablas.ultimasVentas" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Últimas Ventas</h3>
                            <Link :href="route('ventas.index')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">Ver todas</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="venta in tablas.ultimasVentas" :key="venta.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            {{ venta.cliente }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            {{ venta.fecha }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold text-right">
                                            {{ formatCurrency(venta.total) }}
                                        </td>
                                    </tr>
                                    <tr v-if="tablas.ultimasVentas.length === 0">
                                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No hay ventas recientes.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Últimas Reparaciones (Vendedor, Técnico y Admin) -->
                    <div v-if="canAccessReparaciones && tablas.ultimasReparaciones" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ userRole === 'tecnico' ? 'Mis Reparaciones' : 'Últimas Reparaciones' }}
                            </h3>
                            <Link :href="route('reparaciones.index')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">Ver todas</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="rep in tablas.ultimasReparaciones" :key="rep.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ rep.cliente }}</div>
                                            <div class="text-xs text-gray-500">{{ rep.equipo }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span :class="['px-2 py-1 text-xs font-medium rounded-full', getEstadoClass(rep.estado)]">
                                                {{ getEstadoLabel(rep.estado) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            {{ rep.fecha }}
                                        </td>
                                    </tr>
                                    <tr v-if="tablas.ultimasReparaciones.length === 0">
                                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No hay reparaciones.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Clientes Morosos (Vendedor y Admin) -->
                    <div v-if="isVendedor && tablas.clientesMorosos" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-orange-700 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Clientes con Mayor Deuda
                            </h3>
                            <Link :href="route('clientes.index')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">Ver todos</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(moroso, idx) in tablas.clientesMorosos" :key="idx" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            {{ moroso.cliente }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            <span v-if="moroso.diasVencido > 0" class="text-red-600 text-xs">
                                                {{ moroso.diasVencido }} días vencido
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600 font-bold text-right">
                                            {{ formatCurrency(moroso.saldo) }}
                                        </td>
                                    </tr>
                                    <tr v-if="tablas.clientesMorosos.length === 0">
                                        <td colspan="3" class="px-6 py-6 text-center text-sm text-green-600 font-medium bg-green-50">
                                            ¡Excelente! No hay clientes con deuda.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Stock Crítico (Todos) -->
                    <div v-if="tablas.productosCriticos" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-red-700 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Reposición Urgente
                            </h3>
                            <Link :href="route('productos.stock')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">Gestionar Stock</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                        <th class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Disponible</th>
                                        <th class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Mínimo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(stock, idx) in tablas.productosCriticos" :key="idx" class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm text-gray-900">
                                            {{ stock.producto }}
                                            <div class="text-xs text-gray-400">{{ stock.deposito }}</div>
                                        </td>
                                        <td class="px-6 py-3 text-center text-sm font-bold text-red-600">
                                            {{ stock.cantidad }}
                                        </td>
                                        <td class="px-6 py-3 text-center text-sm text-gray-500">
                                            {{ stock.minimo }}
                                        </td>
                                    </tr>
                                    <tr v-if="tablas.productosCriticos.length === 0">
                                        <td colspan="3" class="px-6 py-6 text-center text-sm text-green-600 font-medium bg-green-50">
                                            ¡Todo en orden! No hay stock crítico.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>