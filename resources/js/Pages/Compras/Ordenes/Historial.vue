<script setup>
/**
 * CU-24: Consultar Órdenes de Compra
 * 
 * Contexto: El administrador consulta el historial de todas las OC generadas
 * para hacer seguimiento de las compras realizadas.
 * 
 * Principio K&K (Salida de Navegación): Lista con filtros, búsqueda y
 * enlaces a detalles de cada OC.
 */
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    ordenes: Object,
    proveedores: Array,
    estados: Array,
    filters: Object,
});

const page = usePage();

// Estado local de filtros
const localFilters = ref({
    busqueda: props.filters?.busqueda || '',
    proveedor_id: props.filters?.proveedor_id || '',
    estado_id: props.filters?.estado_id || '',
    fecha_desde: props.filters?.fecha_desde || '',
    fecha_hasta: props.filters?.fecha_hasta || '',
});

// Aplicar filtros con debounce
let timeoutId = null;
const aplicarFiltros = () => {
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('ordenes.historial'), localFilters.value, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

// Observar cambios en filtros
watch(localFilters, aplicarFiltros, { deep: true });

// Limpiar filtros
const limpiarFiltros = () => {
    localFilters.value = {
        busqueda: '',
        proveedor_id: '',
        estado_id: '',
        fecha_desde: '',
        fecha_hasta: '',
    };
};

// Formatear moneda
const formatCurrency = (value, moneda = 'ARS') => {
    const symbol = moneda === 'USD' ? 'USD ' : '$ ';
    return symbol + Number(value || 0).toLocaleString('es-AR', { minimumFractionDigits: 2 });
};

// Formatear fecha
const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

// Badge de estado con colores por estado
const estadoBadge = (estado) => {
    const badges = {
        'Pendiente': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'Enviada': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'Confirmada': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
        'Recibida Parcial': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        'Recibida': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
        'Cancelada': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    return badges[estado] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
};

// Traducir paginación
const traducirPaginacion = (label) => {
    const traducciones = {
        'Previous': 'Anterior',
        'Next': 'Siguiente',
        '&laquo; Previous': 'Anterior',
        'Next &raquo;': 'Siguiente',
    };
    return traducciones[label] || label;
};
</script>

<template>
    <Head title="Consultar Órdenes de Compra" />

    <AppLayout>
        <!-- HEADER -->
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Gestión de Compras
                    </h2>
                    <p class="text-sm font-medium text-indigo-800 dark:text-indigo-300 tracking-wide mt-1">
                        Órdenes de Compra › Historial
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Título y descripción -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">
                        CONSULTAR ÓRDENES DE COMPRA
                    </h1>

                    <!-- Tarjeta informativa -->
                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    Historial completo de todas las Órdenes de Compra generadas.
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    Use los filtros para buscar por proveedor, estado o rango de fechas. Haga clic en "Ver detalle" para consultar la información completa de cada OC.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de éxito -->
                <div v-if="page.props.flash?.success" class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border-l-4 border-emerald-400 text-emerald-700 dark:text-emerald-300 rounded-r">
                    {{ page.props.flash.success }}
                </div>

                <!-- Panel principal -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    
                    <!-- Filtros -->
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                            <!-- Búsqueda -->
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar</label>
                                <input
                                    v-model="localFilters.busqueda"
                                    type="text"
                                    placeholder="Número de OC o proveedor..."
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm"
                                />
                            </div>

                            <!-- Proveedor -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Proveedor</label>
                                <select
                                    v-model="localFilters.proveedor_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm"
                                >
                                    <option value="">Todos</option>
                                    <option v-for="proveedor in proveedores" :key="proveedor.id" :value="proveedor.id">
                                        {{ proveedor.razon_social }}
                                    </option>
                                </select>
                            </div>

                            <!-- Estado -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estado</label>
                                <select
                                    v-model="localFilters.estado_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm"
                                >
                                    <option value="">Todos</option>
                                    <option v-for="estado in estados" :key="estado.id" :value="estado.id">
                                        {{ estado.nombre }}
                                    </option>
                                </select>
                            </div>

                            <!-- Fecha desde -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha desde</label>
                                <input
                                    v-model="localFilters.fecha_desde"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm"
                                />
                            </div>

                            <!-- Fecha hasta -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha hasta</label>
                                <input
                                    v-model="localFilters.fecha_hasta"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm"
                                />
                            </div>
                        </div>

                        <!-- Botón limpiar -->
                        <div class="mt-4 flex justify-end">
                            <button
                                @click="limpiarFiltros"
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            >
                                Limpiar filtros
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de órdenes de compra -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Nº OC
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Proveedor
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Fecha Emisión
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">
                                        Total
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Usuario
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="orden in ordenes.data" :key="orden.id" class="hover:bg-indigo-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                            {{ orden.numero_oc }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ orden.proveedor?.razon_social }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            CUIT: {{ orden.proveedor?.cuit }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatearFecha(orden.fecha_emision) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(orden.total_final) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span 
                                            :class="estadoBadge(orden.estado?.nombre)"
                                            class="px-2 py-1 text-xs font-medium rounded-full"
                                        >
                                            {{ orden.estado?.nombre }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ orden.usuario?.name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link 
                                            :href="route('ordenes.show', orden.id)"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-100 hover:bg-indigo-200 rounded-md transition-colors dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-800/50"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Ver detalle
                                        </Link>
                                    </td>
                                </tr>
                                
                                <!-- Sin resultados -->
                                <tr v-if="ordenes.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="text-gray-500 dark:text-gray-400">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="mt-2 text-sm">No se encontraron órdenes de compra</p>
                                            <p class="text-xs text-gray-400 mt-1">Pruebe ajustando los filtros de búsqueda</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="ordenes.links && ordenes.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <nav class="flex items-center justify-between">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Mostrando 
                                <span class="font-medium">{{ ordenes.from }}</span>
                                a 
                                <span class="font-medium">{{ ordenes.to }}</span>
                                de 
                                <span class="font-medium">{{ ordenes.total }}</span>
                                resultados
                            </p>
                            <div class="flex gap-1">
                                <template v-for="link in ordenes.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        :class="[
                                            'px-3 py-2 text-sm rounded-md transition-colors',
                                            link.active 
                                                ? 'bg-indigo-600 text-white' 
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                        ]"
                                        v-html="traducirPaginacion(link.label)"
                                    />
                                    <span
                                        v-else
                                        class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500"
                                        v-html="traducirPaginacion(link.label)"
                                    />
                                </template>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
