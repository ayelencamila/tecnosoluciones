<script setup>
import { ref, computed, watch } from 'vue';
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
        router.get(route('recepciones.index'), localFilters.value, {
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
        'total': 'bg-green-100 text-green-800',
        'parcial': 'bg-yellow-100 text-yellow-800',
    };
    return badges[tipo] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Recepción de Mercadería" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Recepción de Mercadería
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Historial de recepciones realizadas
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Mensaje de éxito -->
                <div v-if="page.props.flash?.success" class="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border-l-4 border-green-400 text-green-700 dark:text-green-300 rounded-r">
                    {{ page.props.flash.success }}
                </div>

                <!-- Panel principal -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <!-- Barra de filtros -->
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
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
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Nº Recepción
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Orden de Compra
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Proveedor
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Tipo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Usuario
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="recepcion in recepciones.data" :key="recepcion.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
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
                                        {{ recepcion.orden_compra?.proveedor?.nombre || '-' }}
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
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                        >
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
                                            <p class="text-xs text-gray-400 mt-1">Ingrese a una Orden de Compra aprobada para recepcionar mercadería</p>
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
                            <div class="flex space-x-1">
                                <Link
                                    v-for="link in recepciones.links"
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
