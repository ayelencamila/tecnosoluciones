<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Doughnut, Bar } from 'vue-chartjs'; 
import { 
    Chart as ChartJS, 
    Title, 
    Tooltip, 
    Legend, 
    ArcElement,
    BarElement,
    CategoryScale,
    LinearScale
} from 'chart.js';
import axios from 'axios';
import { debounce } from 'lodash';

// Registrar componentes Chart.js
ChartJS.register(Title, Tooltip, Legend, ArcElement, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    reparaciones: Object,
    kpis: Object,
    graficos: Object,
    filters: Object,
    estados: Array,
    tecnicoSeleccionado: Object // Técnico preseleccionado si existe
});

// Estado reactivo para filtros
const form = ref({
    fecha_desde: props.filters.fecha_desde || '',
    fecha_hasta: props.filters.fecha_hasta || '',
    tecnico_id: props.filters.tecnico_id || '',
    estado_id: props.filters.estado_id || '',
});

// Estado para el buscador de técnicos
const searchTecnico = ref('');
const tecnicosResultados = ref([]);
const showTecnicoDropdown = ref(false);
const buscandoTecnico = ref(false);
const tecnicoActual = ref(props.tecnicoSeleccionado || null);

// Inicializar el campo de búsqueda si hay técnico seleccionado
onMounted(() => {
    if (props.tecnicoSeleccionado) {
        searchTecnico.value = props.tecnicoSeleccionado.name;
    }
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (e) => {
    if (!e.target.closest('.tecnico-search-container')) {
        showTecnicoDropdown.value = false;
    }
};

// Buscar técnicos en la API
const buscarTecnicos = debounce(async () => {
    if (searchTecnico.value.length < 2) {
        tecnicosResultados.value = [];
        showTecnicoDropdown.value = false;
        return;
    }
    buscandoTecnico.value = true;
    try {
        const response = await axios.get(route('api.usuarios.buscar'), { 
            params: { q: searchTecnico.value, tecnicos: true } 
        });
        tecnicosResultados.value = response.data;
        showTecnicoDropdown.value = true;
    } catch (error) {
        console.error('Error buscando técnicos:', error);
    } finally {
        buscandoTecnico.value = false;
    }
}, 300);

// Seleccionar un técnico
const seleccionarTecnico = (tecnico) => {
    tecnicoActual.value = tecnico;
    searchTecnico.value = tecnico.name;
    form.value.tecnico_id = tecnico.id;
    showTecnicoDropdown.value = false;
};

// Limpiar el técnico seleccionado
const limpiarTecnico = () => {
    tecnicoActual.value = null;
    searchTecnico.value = '';
    form.value.tecnico_id = '';
    tecnicosResultados.value = [];
};

// Función Exportar
const exportar = () => {
    const params = new URLSearchParams(form.value).toString();
    window.location.href = route('reportes.reparaciones.exportar') + '?' + params;
};

// Observar cambios en los filtros
watch(() => [form.value.fecha_desde, form.value.fecha_hasta, form.value.tecnico_id, form.value.estado_id], () => {
    router.get(route('reportes.reparaciones'), form.value, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};
</script>

<template>
    <AppLayout>
        <Head title="Reporte de Reparaciones" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Reporte de Servicio Técnico
                </h2>
                <span class="text-sm text-gray-500">
                    Periodo: {{ new Date(form.fecha_desde).toLocaleDateString() }} - {{ new Date(form.fecha_hasta).toLocaleDateString() }}
                </span>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                        <div class="text-gray-500 text-xs font-bold uppercase">Total Ingresos</div>
                        <div class="text-3xl font-bold text-gray-800 mt-2">{{ kpis.total }}</div>
                        <div class="text-xs text-blue-600 mt-1">Equipos recibidos</div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
                        <div class="text-gray-500 text-xs font-bold uppercase">Finalizadas</div>
                        <div class="text-3xl font-bold text-gray-800 mt-2">{{ kpis.finalizadas }}</div>
                        <div class="text-xs text-green-600 mt-1">Entregados al cliente</div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
                        <div class="text-gray-500 text-xs font-bold uppercase">Tasa de Éxito</div>
                        <div class="text-3xl font-bold text-gray-800 mt-2">{{ kpis.tasa_exito }}%</div>
                        <div class="text-xs text-purple-600 mt-1">Ratio resolución</div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-yellow-500">
                        <div class="text-gray-500 text-xs font-bold uppercase">Facturación Taller</div>
                        <div class="text-3xl font-bold text-gray-800 mt-2">{{ formatCurrency(kpis.ingresos) }}</div>
                        <div class="text-xs text-yellow-600 mt-1">Mano de obra + Repuestos</div>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Desde</label>
                            <input type="date" v-model="form.fecha_desde" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hasta</label>
                            <input type="date" v-model="form.fecha_hasta" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        
                        <!-- Buscador de Técnicos -->
                        <div class="relative tecnico-search-container">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Técnico</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    v-model="searchTecnico"
                                    @input="buscarTecnicos"
                                    @focus="searchTecnico.length >= 2 && (showTecnicoDropdown = true)"
                                    placeholder="Buscar técnico..."
                                    autocomplete="off"
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8"
                                />
                                <button 
                                    v-if="tecnicoActual" 
                                    @click="limpiarTecnico" 
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                <div v-if="buscandoTecnico" class="absolute inset-y-0 right-0 flex items-center pr-2">
                                    <svg class="animate-spin h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div 
                                v-if="showTecnicoDropdown && tecnicosResultados.length > 0" 
                                class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto"
                            >
                                <ul class="py-1">
                                    <li 
                                        v-for="tecnico in tecnicosResultados" 
                                        :key="tecnico.id"
                                        @click="seleccionarTecnico(tecnico)"
                                        class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm"
                                    >
                                        <div class="font-medium text-gray-900">{{ tecnico.name }}</div>
                                        <div class="text-xs text-gray-500">{{ tecnico.rol?.nombre || 'Sin rol' }}</div>
                                    </li>
                                </ul>
                            </div>
                            <div 
                                v-if="showTecnicoDropdown && tecnicosResultados.length === 0 && searchTecnico.length >= 2 && !buscandoTecnico" 
                                class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg p-3 text-sm text-gray-500"
                            >
                                No se encontraron técnicos
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Estado</label>
                            <select v-model="form.estado_id" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                                <option value="">Todos</option>
                                <option v-for="e in estados" :key="e.id" :value="e.id">{{ e.nombre }}</option>
                            </select>
                        </div>
                        <div>
                            <PrimaryButton @click="exportar" class="w-full justify-center h-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Exportar
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" v-if="kpis.total > 0">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="font-bold text-gray-700 mb-4 text-center">Estado de Reparaciones</h3>
                        <div class="h-64 relative flex justify-center">
                            <Doughnut :data="graficos.estados" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="font-bold text-gray-700 mb-4 text-center">Rendimiento por Técnico</h3>
                        <div class="h-64 relative">
                            <Bar :data="graficos.tecnicos" :options="{ responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }" />
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Código</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Equipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Técnico</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="rep in reparaciones.data" :key="rep.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600">
                                        #{{ rep.codigo_reparacion ?? rep.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ new Date(rep.fecha_ingreso).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ rep.cliente?.apellido }}, {{ rep.cliente?.nombre }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 hidden md:table-cell">
                                        {{ rep.marca?.nombre }} {{ rep.modelo?.nombre }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 hidden sm:table-cell">
                                        {{ rep.tecnico?.name || 'Sin Asignar' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ rep.estado?.nombreEstado || 'Desconocido' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                        {{ formatCurrency(rep.total_final ?? 0) }}
                                    </td>
                                </tr>
                                <tr v-if="reparaciones.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        No se encontraron reparaciones en este periodo.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        <Pagination :links="reparaciones.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>