<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { debounce } from 'lodash';

// Props que vienen del ReparacionController@index
const props = defineProps({
    reparaciones: Object, // Paginator
    estados: Array,       // Catálogo para filtro
    filters: Object,      // Estado actual de los filtros
});

// Estado reactivo para los filtros
const form = ref({
    search: props.filters.search || '',
    estado_id: props.filters.estado_id || '',
});

// Opciones para el Select de Estados
const estadosOptions = computed(() => [
    { value: '', label: 'Todos los Estados' },
    ...props.estados.map(e => ({ value: e.estadoReparacionID, label: e.nombreEstado }))
]);

// Watcher para búsqueda asíncrona (Inertia)
watch(form, debounce(() => {
    router.get(route('reparaciones.index'), form.value, { 
        preserveState: true, 
        replace: true,
        preserveScroll: true 
    });
}, 300), { deep: true });

// Limpiar filtros
const resetFilters = () => {
    form.value = { search: '', estado_id: '' };
};

// Lógica de colores para los Estados (UX Visual)
const getEstadoBadgeClass = (nombreEstado) => {
    const estado = nombreEstado?.toLowerCase() || '';
    if (estado.includes('recibido')) return 'bg-blue-100 text-blue-800 border-blue-200';
    if (estado.includes('diagn')) return 'bg-purple-100 text-purple-800 border-purple-200';
    if (estado.includes('reparacion')) return 'bg-yellow-100 text-yellow-800 border-yellow-200'; // En proceso
    if (estado.includes('listo')) return 'bg-green-100 text-green-800 border-green-200 font-bold'; // Acción requerida (Avisar cliente)
    if (estado.includes('entregado')) return 'bg-gray-100 text-gray-600 border-gray-200'; // Finalizado
    if (estado.includes('demorado')) return 'bg-red-100 text-red-800 border-red-200'; // Alerta
    if (estado.includes('cancel') || estado.includes('anul')) return 'bg-red-50 text-red-600 border-red-100';
    return 'bg-gray-50 text-gray-600 border-gray-200';
};

// Paginación (Tu lógica preferida)
const getPaginationLabel = (label, index, totalLinks) => {
    if (label.includes('Previous')) return '&laquo;'; 
    if (label.includes('Next')) return '&raquo;'; 
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Gestión de Reparaciones" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Servicio Técnico</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        
                        <div class="flex-1 w-full flex flex-col md:flex-row gap-4">
                            <div class="w-full md:w-1/2">
                                <TextInput 
                                    v-model="form.search" 
                                    placeholder="Buscar por Código, Cliente, Equipo o IMEI..." 
                                    class="w-full" 
                                />
                            </div>
                            <div class="w-full md:w-1/4">
                                <SelectInput 
                                    v-model="form.estado_id" 
                                    class="w-full" 
                                    :options="estadosOptions" 
                                />
                            </div>
                            <button 
                                v-if="form.search || form.estado_id"
                                @click="resetFilters" 
                                class="text-sm text-gray-500 hover:text-indigo-600 underline mt-2 md:mt-0"
                            >
                                Limpiar
                            </button>
                        </div>

                        <Link :href="route('reparaciones.create')">
                            <PrimaryButton class="whitespace-nowrap">
                                + Nueva Reparación
                            </PrimaryButton>
                        </Link>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código / Ingreso</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipo / Falla</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Técnico</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="reparacion in reparaciones.data" :key="reparacion.reparacionID" class="hover:bg-gray-50 transition duration-150 group">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-indigo-700 font-mono">
                                            {{ reparacion.codigo }}
                                        </div>
                                        <div class="text-xs text-gray-500" title="Fecha de Ingreso">
                                            {{ reparacion.fecha_ingreso }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ reparacion.cliente.nombre_completo }}
                                        </div>
                                        <div class="text-xs text-gray-500 flex items-center">
                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            {{ reparacion.cliente.telefono }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 font-medium">
                                            {{ reparacion.equipo }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs" :title="reparacion.falla">
                                            {{ reparacion.falla }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border" 
                                              :class="getEstadoBadgeClass(reparacion.estado.nombre)">
                                            {{ reparacion.estado.nombre }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ reparacion.tecnico }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-3">
                                            
                                            <Link :href="route('reparaciones.show', reparacion.reparacionID)" 
                                                  class="text-blue-600 hover:text-blue-900 bg-blue-50 p-2 rounded-full hover:bg-blue-100 transition" 
                                                  title="Ver Detalle">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Link>

                                            <Link :href="route('reparaciones.edit', reparacion.reparacionID)" 
                                                  class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-full hover:bg-indigo-100 transition" 
                                                  title="Gestionar Reparación">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr v-if="reparaciones.data.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                                        <div class="flex flex-col items-center">
                                            <svg class="h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-lg">No se encontraron reparaciones.</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="reparaciones.links.length > 3">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in reparaciones.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-medium transition-colors"
                                    :class="link.active 
                                        ? 'bg-indigo-600 text-white shadow-md' 
                                        : 'bg-white text-gray-600 border border-gray-300 hover:bg-indigo-50'"
                                >
                                    <span v-html="getPaginationLabel(link.label, k, reparaciones.links.length)"></span>
                                </Link>
                                <span 
                                    v-else 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm text-gray-300 border border-gray-200 cursor-not-allowed" 
                                    v-html="getPaginationLabel(link.label, k, reparaciones.links.length)"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>