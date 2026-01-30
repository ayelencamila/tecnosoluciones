<script setup>
/**
 * CU-22 Pantalla 1: Punto de Partida - Listado de Cotizaciones Elegidas
 * 
 * MODELO SIMPLIFICADO (sin tabla ofertas_compra):
 * Muestra cotizaciones que han sido seleccionadas como ganadoras y están
 * listas para convertirse en Órdenes de Compra.
 * 
 * Principio K&K (Salida de Navegación): Uso de lista filtrada "To-do list"
 */
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash';

const props = defineProps({
    cotizaciones: {
        type: Object,
        default: () => ({ data: [] })
    },
    proveedores: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
});

// Filtros locales
const localFilters = ref({
    proveedor_id: props.filters?.proveedor_id || '',
    busqueda: props.filters?.busqueda || '',
});

// Aplicar filtros con debounce
const aplicarFiltros = debounce(() => {
    router.get(route('ordenes.index'), {
        proveedor_id: localFilters.value.proveedor_id || undefined,
        busqueda: localFilters.value.busqueda || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

// Watch para filtros
watch(() => localFilters.value.proveedor_id, () => aplicarFiltros());
watch(() => localFilters.value.busqueda, () => aplicarFiltros());

// Limpiar filtros
const limpiarFiltros = () => {
    localFilters.value = { proveedor_id: '', busqueda: '' };
};

// Formatear moneda
const formatCurrency = (value) => {
    return '$ ' + Number(value || 0).toLocaleString('es-AR', { minimumFractionDigits: 2 });
};

// Formatear fecha
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

// Obtener productos de la cotización
const getProductos = (cotizacion) => {
    if (cotizacion.respuestas && cotizacion.respuestas.length > 0) {
        const primera = cotizacion.respuestas[0];
        const nombre = primera.producto?.nombre || 'Producto';
        const cantidad = primera.cantidad_disponible || 1;
        const mas = cotizacion.respuestas.length > 1 ? ` (+${cotizacion.respuestas.length - 1} más)` : '';
        return `${nombre} (${cantidad}u)${mas}`;
    }
    return '-';
};

// Total de resultados
const totalResultados = computed(() => props.cotizaciones?.total || props.cotizaciones?.data?.length || 0);

// Verificar si hay filtros activos
const hayFiltrosActivos = computed(() => localFilters.value.proveedor_id || localFilters.value.busqueda);
</script>

<template>
    <Head title="Cotizaciones Elegidas - Generar OC" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Gestión de Compras
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- TÍTULO Y STATS -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                            COTIZACIONES ELEGIDAS - LISTAS PARA GENERAR OC
                        </h1>
                        <Link :href="route('ordenes.historial')" 
                              class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Ver Historial de OC
                        </Link>
                    </div>

                    <!-- Tarjeta informativa -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    Estas cotizaciones han sido marcadas como <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded font-semibold">"Ganadora"</span> 
                                    y están listas para generar la Orden de Compra.
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    Seleccione "GENERAR OC" para crear la orden de compra correspondiente.
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="text-center px-4 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ totalResultados }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                                        Pendientes
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL PRINCIPAL -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    
                    <!-- BARRA DE FILTROS -->
                    <div class="px-6 py-4 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex flex-col lg:flex-row gap-4 items-end">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wide">
                                    Filtrar por Proveedor:
                                </label>
                                <select v-model="localFilters.proveedor_id"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-white">
                                    <option value="">Todos los Proveedores</option>
                                    <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">
                                        {{ prov.razon_social }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wide">
                                    Buscar:
                                </label>
                                <input v-model="localFilters.busqueda" type="text" placeholder="Código solicitud o proveedor..."
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 dark:bg-gray-800 dark:text-white" />
                            </div>
                            <button v-if="hayFiltrosActivos" @click="limpiarFiltros"
                                class="px-5 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <!-- TABLA -->
                    <div class="p-6 overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-indigo-600">
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase">Solicitud</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase">Proveedor</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase">Productos</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase">Total</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase">Fecha Respuesta</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase w-40">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="cot in cotizaciones.data" :key="cot.id"
                                    class="hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('solicitudes-cotizacion.show', cot.solicitud?.id)"
                                            class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ cot.solicitud?.codigo_solicitud || '-' }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ cot.proveedor?.razon_social || '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ getProductos(cot) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ formatCurrency(cot.total_estimado) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ formatDate(cot.fecha_respuesta) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <Link :href="route('ordenes.create', { cotizacion_id: cot.id })"
                                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-xs font-bold uppercase rounded-lg transition-all shadow-sm hover:shadow-md">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            GENERAR OC
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!cotizaciones.data || cotizaciones.data.length === 0">
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                No hay cotizaciones pendientes de generar OC
                                            </p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                                                Las cotizaciones aparecen aquí cuando son seleccionadas como ganadoras en una solicitud.
                                            </p>
                                            <Link :href="route('solicitudes-cotizacion.index')" 
                                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                                                Ver Solicitudes de Cotización
                                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINACIÓN -->
                    <div v-if="cotizaciones.links && cotizaciones.links.length > 3" 
                         class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Mostrando {{ cotizaciones.from || 0 }} a {{ cotizaciones.to || 0 }} de {{ totalResultados }} resultados
                            </p>
                            <nav class="flex items-center space-x-1">
                                <template v-for="(link, index) in cotizaciones.links" :key="index">
                                    <Link v-if="link.url" :href="link.url" preserve-scroll
                                        :class="['px-3 py-2 text-sm font-medium rounded-lg transition-all',
                                            link.active ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 border border-gray-300 dark:border-gray-600']"
                                        v-html="link.label" />
                                    <span v-else class="px-3 py-2 text-sm font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed"
                                        v-html="link.label" />
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
