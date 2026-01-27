<script setup>
/**
 * CU-23: Historial de Recepciones de Mercadería
 * 
 * Muestra el registro completo de todas las recepciones realizadas,
 * permitiendo filtrar por número, tipo y rango de fechas.
 */
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    recepciones: Object,
    filters: Object,
});

const page = usePage();

// Estado local de filtros
const localFilters = ref({
    numero_recepcion: props.filters?.numero_recepcion || '',
    tipo: props.filters?.tipo || '',
    fecha_desde: props.filters?.fecha_desde || '',
    fecha_hasta: props.filters?.fecha_hasta || '',
});

// Aplicar filtros con debounce
let timeoutId = null;
const aplicarFiltros = () => {
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('recepciones.historial'), localFilters.value, {
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
        numero_recepcion: '',
        tipo: '',
        fecha_desde: '',
        fecha_hasta: '',
    };
};

// Helper para formatear fecha
const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Badge de tipo
const tipoBadge = (tipo) => {
    const badges = {
        'total': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
        'parcial': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return badges[tipo] || 'bg-gray-100 text-gray-800';
};

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
    <Head title="Historial de Recepciones" />

    <AppLayout>
        <!-- HEADER -->
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Gestión de Compras
                    </h2>
                    <p class="text-sm font-medium text-indigo-800 dark:text-indigo-300 tracking-wide mt-1">
                        Recepciones › Historial de Recepciones
                    </p>
                </div>
                <Link 
                    :href="route('recepciones.index')"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-md transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver a Recepcionar
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Título y descripción -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">
                        HISTORIAL DE RECEPCIONES DE MERCADERÍA
                    </h1>

                    <!-- Tarjeta informativa -->
                    <div class="bg-gradient-to-r from-indigo-50 to-emerald-50 dark:from-indigo-900/20 dark:to-emerald-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    Registro completo de todas las recepciones de mercadería realizadas.
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    Use los filtros para buscar recepciones específicas por número, tipo o rango de fechas.
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
                    
                    <!-- Barra de filtros -->
                    <div class="bg-indigo-700 px-6 py-3">
                        <span class="text-sm font-semibold text-white uppercase tracking-wide">
                            Filtros
                        </span>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-750 border-b border-gray-200 dark:border-gray-600">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                            <!-- Búsqueda por número -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Número Recepción
                                </label>
                                <input
                                    v-model="localFilters.numero_recepcion"
                                    type="text"
                                    placeholder="REC-..."
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm"
                                />
                            </div>

                            <!-- Tipo de recepción -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Tipo
                                </label>
                                <select
                                    v-model="localFilters.tipo"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm"
                                >
                                    <option value="">Todos</option>
                                    <option value="total">Total</option>
                                    <option value="parcial">Parcial</option>
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
                    </div>

                    <!-- Tabla de recepciones -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Nº Recepción
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Orden de Compra
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Proveedor
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Tipo
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
                                <tr v-for="recepcion in recepciones.data" :key="recepcion.id" class="hover:bg-indigo-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ recepcion.numero_recepcion }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <Link 
                                            :href="route('ordenes.show', recepcion.orden_compra_id)"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline"
                                        >
                                            {{ recepcion.orden_compra?.numero_oc }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ recepcion.orden_compra?.proveedor?.razon_social || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatearFecha(recepcion.fecha_recepcion) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            :class="tipoBadge(recepcion.tipo)"
                                            class="px-2 py-1 text-xs font-medium rounded-full capitalize"
                                        >
                                            {{ recepcion.tipo }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ recepcion.usuario?.name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link 
                                            :href="route('recepciones.show', recepcion.id)"
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
                                <tr v-if="recepciones.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="text-gray-500 dark:text-gray-400">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <p class="mt-2 text-sm">No se encontraron recepciones</p>
                                            <p class="text-xs text-gray-400 mt-1">Ajuste los filtros o realice una nueva recepción</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="recepciones.links && recepciones.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <nav class="flex items-center justify-between">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Mostrando 
                                <span class="font-medium">{{ recepciones.from }}</span>
                                a 
                                <span class="font-medium">{{ recepciones.to }}</span>
                                de 
                                <span class="font-medium">{{ recepciones.total }}</span>
                                resultados
                            </p>
                            <div class="flex gap-1">
                                <template v-for="link in recepciones.links" :key="link.label">
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
