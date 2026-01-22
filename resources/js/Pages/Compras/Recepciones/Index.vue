<script setup>
/**
 * CU-23 Pantalla 1: Seleccionar Orden de Compra Pendiente
 * 
 * Objetivo: Que el usuario de depósito localice rápidamente la OC digital
 * que coincide con los papeles o la mercadería física que tiene enfrente.
 * 
 * Principio K&K (Diseño de Salida/Navegación): Utilizar un formato tabular
 * para listar las opciones. Filtrar predeterminadamente solo aquellas órdenes
 * que están en un estado válido para recibir ("Enviada", "Recibida Parcial").
 */
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash';

const props = defineProps({
    ordenes: {
        type: Object,
        default: () => ({ data: [] })
    },
    proveedores: {
        type: Array,
        default: () => []
    },
    estadosPermitidos: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
});

const page = usePage();

// Estado local de filtros
const localFilters = ref({
    busqueda: props.filters?.busqueda || '',
    filtro_estado: props.filters?.filtro_estado || 'pendientes',
});

// Aplicar filtros con debounce
const aplicarFiltros = debounce(() => {
    router.get(route('recepciones.index'), {
        busqueda: localFilters.value.busqueda || undefined,
        filtro_estado: localFilters.value.filtro_estado || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

// Watch para búsqueda
watch(() => localFilters.value.busqueda, () => {
    aplicarFiltros();
});

// Filtrar por estado inmediatamente
const filtrarPorEstado = () => {
    router.get(route('recepciones.index'), {
        busqueda: localFilters.value.busqueda || undefined,
        filtro_estado: localFilters.value.filtro_estado || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Buscar manualmente
const buscar = () => {
    aplicarFiltros();
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

// Badge de estado con colores
const estadoBadge = (estado) => {
    const badges = {
        'Enviada': {
            dot: 'bg-yellow-400',
            text: 'text-yellow-700 dark:text-yellow-400',
            label: 'Enviada (Pend.)'
        },
        'Recibida Parcial': {
            dot: 'bg-orange-400',
            text: 'text-orange-700 dark:text-orange-400',
            label: 'Recibida Parcial'
        },
    };
    return badges[estado] || { dot: 'bg-gray-400', text: 'text-gray-600', label: estado };
};

// Total de resultados
const totalResultados = computed(() => props.ordenes?.total || props.ordenes?.data?.length || 0);

// Traducir paginación
const traducirPaginacion = (label) => {
    const traducciones = {
        'Previous': '« Anterior',
        'Next': 'Siguiente »',
        '&laquo; Previous': '« Anterior',
        'Next &raquo;': 'Siguiente »',
    };
    return traducciones[label] || label;
};
</script>

<template>
    <Head title="Recepcionar Mercadería" />

    <AppLayout>
        <!-- HEADER -->
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Gestión de Compras
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- ============================================== -->
                <!-- TÍTULO PRINCIPAL (CU-23 P1: Punto de Partida)  -->
                <!-- ============================================== -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-1">
                        RECEPCIÓN DE MERCADERÍA
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">›</span> 
                        SELECCIONAR ORDEN DE COMPRA PENDIENTE
                    </p>
                </div>

                <!-- Mensaje de éxito -->
                <div v-if="page.props.flash?.success" class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border-l-4 border-emerald-400 text-emerald-700 dark:text-emerald-300 rounded-r">
                    {{ page.props.flash.success }}
                </div>

                <!-- ============================================== -->
                <!-- SECCIÓN PRINCIPAL: FILTROS Y TABLA             -->
                <!-- ============================================== -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    
                    <!-- BARRA DE FILTROS RÁPIDOS -->
                    <div class="bg-indigo-700 px-6 py-3">
                        <span class="text-sm font-semibold text-white uppercase tracking-wide">
                            Filtros Rápidos
                        </span>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-750 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex flex-col lg:flex-row gap-4 items-end">
                            
                            <!-- Búsqueda por Nº OC / Proveedor -->
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Buscar (Nº OC / Proveedor):
                                </label>
                                <div class="relative">
                                    <input 
                                        v-model="localFilters.busqueda"
                                        type="text"
                                        placeholder="Ej: OC-2026-45, Tech Supplies..."
                                        class="w-full pl-4 pr-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-white transition-colors"
                                        @keyup.enter="buscar"
                                    />
                                </div>
                            </div>

                            <!-- Filtro por Estado -->
                            <div class="w-full lg:w-72">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Estado:
                                </label>
                                <select 
                                    v-model="localFilters.filtro_estado"
                                    @change="filtrarPorEstado"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-white transition-colors"
                                >
                                    <option value="pendientes">Solo Pendientes de Recepción</option>
                                    <option v-for="estado in estadosPermitidos" :key="estado.id" :value="estado.id">
                                        {{ estado.nombre }}
                                    </option>
                                    <option value="todos">Todas las OC</option>
                                </select>
                            </div>

                            <!-- Botón Buscar -->
                            <button 
                                @click="buscar"
                                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors flex items-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                BUSCAR
                            </button>
                        </div>
                    </div>

                    <!-- RESULTADOS -->
                    <div class="px-6 py-3 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                            RESULTADOS
                            <span class="font-normal text-gray-500 dark:text-gray-400 ml-2">
                                (Mostrando órdenes listas para recibir)
                            </span>
                        </h3>
                    </div>

                    <!-- TABLA DE ÓRDENES DE COMPRA PENDIENTES -->
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
                                    <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                        Cant. Ítems Pend.
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                        Estado Actual
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="orden in ordenes.data" :key="orden.id" class="hover:bg-indigo-50 dark:hover:bg-gray-700/50">
                                    <!-- Nº OC -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ orden.numero_oc }}
                                        </span>
                                    </td>
                                    
                                    <!-- Proveedor (link) -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link 
                                            :href="route('ordenes.show', orden.id)"
                                            class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 hover:underline"
                                        >
                                            {{ orden.proveedor?.razon_social }}
                                        </Link>
                                    </td>
                                    
                                    <!-- Fecha Emisión -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatearFecha(orden.fecha_emision) }}
                                    </td>
                                    
                                    <!-- Cantidad Ítems Pendientes -->
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ orden.items_pendientes }} Unid.
                                        </span>
                                    </td>
                                    
                                    <!-- Estado Actual (con badge) -->
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <span 
                                                :class="estadoBadge(orden.estado?.nombre).dot"
                                                class="w-3 h-3 rounded-full"
                                            ></span>
                                            <span 
                                                :class="estadoBadge(orden.estado?.nombre).text"
                                                class="text-sm font-medium"
                                            >
                                                {{ estadoBadge(orden.estado?.nombre).label }}
                                            </span>
                                        </div>
                                    </td>
                                    
                                    <!-- Acción -->
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <Link 
                                            :href="route('recepciones.create', { orden_compra_id: orden.id })"
                                            :class="[
                                                'inline-flex items-center px-4 py-2 text-xs font-bold rounded-md shadow-sm transition-colors',
                                                orden.estado?.nombre === 'Recibida Parcial'
                                                    ? 'bg-orange-500 hover:bg-orange-600 text-white'
                                                    : 'bg-emerald-500 hover:bg-emerald-600 text-white'
                                            ]"
                                        >
                                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ orden.estado?.nombre === 'Recibida Parcial' ? 'CONTINUAR REP.' : 'INICIAR RECEP.' }}
                                        </Link>
                                    </td>
                                </tr>
                                
                                <!-- Sin resultados -->
                                <tr v-if="ordenes.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="text-gray-500 dark:text-gray-400">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <p class="mt-2 text-sm font-medium">No se encontraron órdenes de compra pendientes</p>
                                            <p class="text-xs text-gray-400 mt-1">Pruebe ajustando los filtros de búsqueda</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- NOTA AL PIE (Excepción 3a del CU) -->
                    <div class="px-6 py-3 bg-amber-50 dark:bg-amber-900/20 border-t border-amber-200 dark:border-amber-700">
                        <p class="text-sm text-amber-700 dark:text-amber-400 flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>
                                <strong>Si no encuentra la OC</strong>, verifique con Compras si el estado es correcto.
                            </span>
                        </p>
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

                <!-- ============================================== -->
                <!-- ENLACE AL HISTORIAL DE RECEPCIONES             -->
                <!-- ============================================== -->
                <div class="mt-6 text-center">
                    <Link 
                        :href="route('recepciones.historial')"
                        class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Ver historial de recepciones anteriores
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
