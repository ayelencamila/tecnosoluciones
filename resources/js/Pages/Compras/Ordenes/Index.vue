<script setup>
/**
 * CU-22 y CU-24: Listado de Órdenes de Compra con filtros
 * 
 * Vista índice que muestra todas las OC generadas con opciones de filtrado.
 */
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AlertMessage from '@/Components/AlertMessage.vue';

const props = defineProps({
    ordenes: Object,
    estados: Array,
    filters: Object,
});

// Estado local de filtros
const localFilters = ref({
    numero_oc: props.filters?.numero_oc || '',
    estado_id: props.filters?.estado_id || '',
    fecha_desde: props.filters?.fecha_desde || '',
    fecha_hasta: props.filters?.fecha_hasta || '',
});

// Aplicar filtros con debounce
let timeoutId = null;
const aplicarFiltros = () => {
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('ordenes.index'), localFilters.value, {
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
        numero_oc: '',
        estado_id: '',
        fecha_desde: '',
        fecha_hasta: '',
    };
};

// Helper para clases de estado
const estadoClass = (estado) => {
    if (!estado) return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    switch (estado.nombre) {
        case 'Borrador': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'Enviada': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'Envío Fallido': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        case 'Confirmada': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'Recibida Parcial': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'Recibida Total': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'Cancelada': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

// Formatear fecha
const formatFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Formatear moneda
const formatMoneda = (valor) => {
    return Number(valor).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2,
    });
};

// Contadores para estadísticas
const stats = computed(() => {
    const data = props.ordenes?.data || [];
    return {
        total: props.ordenes?.total || 0,
        enviadas: data.filter(o => o.estado?.nombre === 'Enviada').length,
        confirmadas: data.filter(o => o.estado?.nombre === 'Confirmada').length,
        fallidas: data.filter(o => o.estado?.nombre === 'Envío Fallido').length,
    };
});
</script>

<template>
    <Head title="Órdenes de Compra" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Órdenes de Compra
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Consulta y gestión de órdenes de compra emitidas
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <AlertMessage v-if="$page.props.flash.success" :message="$page.props.flash.success" type="success" />
                <AlertMessage v-if="$page.props.flash.error" :message="$page.props.flash.error" type="error" />

                <!-- Estadísticas rápidas -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-indigo-500">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total OC</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-blue-500">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Enviadas</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.enviadas }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-green-500">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Confirmadas</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.confirmadas }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-red-500">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Envío Fallido</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.fallidas }}</p>
                    </div>
                </div>

                <!-- Panel principal con filtros -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <!-- CU-24: Barra de filtros -->
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                            <!-- Búsqueda por número de OC -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Número OC
                                </label>
                                <input
                                    v-model="localFilters.numero_oc"
                                    type="text"
                                    placeholder="OC-..."
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm"
                                />
                            </div>

                            <!-- Filtro por estado -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Estado
                                </label>
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
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Desde
                                </label>
                                <input
                                    v-model="localFilters.fecha_desde"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm"
                                />
                            </div>

                            <!-- Fecha hasta -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Hasta
                                </label>
                                <input
                                    v-model="localFilters.fecha_hasta"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm"
                                />
                            </div>

                            <!-- Botón limpiar -->
                            <div>
                                <button
                                    @click="limpiarFiltros"
                                    type="button"
                                    class="w-full px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                >
                                    Limpiar filtros
                                </button>
                            </div>
                        </div>

                        <!-- Link a ofertas -->
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Las órdenes de compra se generan al elegir una oferta de proveedor.
                            </p>
                            <Link :href="route('ofertas.index')">
                                <PrimaryButton>Ver Ofertas de Compra</PrimaryButton>
                            </Link>
                        </div>
                    </div>

                    <!-- Tabla de órdenes -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        N° OC
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Proveedor
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Fecha Emisión
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Total
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="orden in ordenes.data" :key="orden.id" 
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('ordenes.show', orden.id)" 
                                              class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium">
                                            {{ orden.numero_oc }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ orden.proveedor?.razon_social || orden.proveedor?.nombre || 'Sin proveedor' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ orden.proveedor?.cuit }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatFecha(orden.fecha_emision) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white text-right">
                                        {{ formatMoneda(orden.total_final) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 inline-flex items-center text-xs font-semibold rounded-full"
                                              :class="estadoClass(orden.estado)">
                                            {{ orden.estado?.nombre || 'Sin estado' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                        <div class="flex justify-center space-x-2">
                                            <Link :href="route('ordenes.show', orden.id)"
                                                  class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                                Ver
                                            </Link>
                                            <a v-if="orden.archivo_pdf"
                                               :href="route('ordenes.descargar-pdf', orden.id)"
                                               class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300">
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Sin resultados -->
                                <tr v-if="ordenes.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="text-gray-500 dark:text-gray-400">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="mt-2 text-sm">No se encontraron órdenes de compra</p>
                                            <p class="text-xs text-gray-400 mt-1">Las órdenes se generan al elegir una oferta</p>
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
                            <div class="flex space-x-1">
                                <Link
                                    v-for="link in ordenes.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-2 text-sm rounded-md',
                                        link.active 
                                            ? 'bg-indigo-600 text-white' 
                                            : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600',
                                        !link.url && 'opacity-50 cursor-not-allowed'
                                    ]"
                                    :disabled="!link.url"
                                />
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
