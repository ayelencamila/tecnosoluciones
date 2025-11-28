<script setup>
import { ref, watch, computed } from 'vue'; 
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { debounce } from 'lodash';

// Props que vienen del VentaController@index
const props = defineProps({
    ventas: Object,         // Paginador de Laravel (data, links, etc.)
    filters: Object,        // Filtros activos
});

// Estado reactivo de los filtros
const form = ref({
    search: props.filters.search || '',
    estado: props.filters.estado || '', // 'activa' o 'anulada'
});

// Watcher con Debounce
watch(form, debounce(() => {
    router.get(route('ventas.index'), form.value, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 300), { deep: true });

const resetFilters = () => {
    form.value = { search: '', estado: '' };
};

// --- HELPERS VISUALES ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

const getEstadoBadgeClass = (nombreEstado) => {
    const estado = nombreEstado?.toLowerCase() || '';
    if (estado === 'completada') return 'bg-green-100 text-green-800 border-green-200 font-bold';
    if (estado === 'pendiente') return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    if (estado === 'anulada') return 'bg-red-100 text-red-800 border-red-200';
    return 'bg-gray-100 text-gray-600 border-gray-200';
};

// Paginación Estilizada (Helper)
const getPaginationLabel = (label, index, totalLinks) => {
    if (label.includes('Previous')) return '&laquo;'; 
    if (label.includes('Next')) return '&raquo;'; 
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Listado de Ventas" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Ventas
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        
                        <div class="flex-1 w-full flex flex-col md:flex-row gap-4">
                            <div class="w-full md:w-1/2">
                                <TextInput 
                                    v-model="form.search" 
                                    placeholder="Buscar N° Comprobante, Cliente..." 
                                    class="w-full"
                                />
                            </div>
                            <div class="w-full md:w-40">
                                <select 
                                    v-model="form.estado"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                >
                                    <option value="">Todos</option>
                                    <option value="activa">Activas</option>
                                    <option value="anulada">Anuladas</option>
                                </select>
                            </div>
                            <button 
                                v-if="form.search || form.estado"
                                @click="resetFilters" 
                                class="text-sm text-gray-500 hover:text-indigo-600 underline mt-2 md:mt-0"
                            >
                                Limpiar
                            </button>
                        </div>

                        <div>
                            <Link :href="route('ventas.create')">
                                <PrimaryButton class="whitespace-nowrap shadow-md">
                                    + Nueva Venta
                                </PrimaryButton>
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comprobante</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pago</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="venta in ventas.data" :key="venta.venta_id" class="hover:bg-gray-50 transition duration-150 group">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ venta.fecha }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-indigo-700 font-mono">
                                            {{ venta.comprobante }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ venta.cliente }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold text-right">
                                        {{ formatCurrency(venta.total) }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-600">
                                        {{ venta.medio_pago }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span 
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border"
                                            :class="getEstadoBadgeClass(venta.estado)"
                                        >
                                            {{ venta.estado }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link 
                                            :href="route('ventas.show', venta.venta_id)" 
                                            class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-full hover:bg-indigo-100 transition inline-flex items-center"
                                            title="Ver Detalle"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>
                                    </td>
                                </tr>
                                
                                <tr v-if="ventas.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center">
                                            <svg class="h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-lg font-medium">No se encontraron ventas.</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="ventas.links && ventas.links.length > 3">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in ventas.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-medium transition-colors"
                                    :class="link.active 
                                        ? 'bg-indigo-600 text-white shadow-md' 
                                        : 'bg-white text-gray-600 border border-gray-300 hover:bg-indigo-50'"
                                >
                                    <span v-html="getPaginationLabel(link.label, k, ventas.links.length)"></span>
                                </Link>
                                <span 
                                    v-else 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm text-gray-300 border border-gray-200 cursor-not-allowed" 
                                    v-html="getPaginationLabel(link.label, k, ventas.links.length)"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>