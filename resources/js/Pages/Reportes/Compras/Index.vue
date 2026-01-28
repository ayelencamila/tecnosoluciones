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

ChartJS.register(Title, Tooltip, Legend, ArcElement, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    ordenes: Object,
    kpis: Object,
    graficos: Object,
    filters: Object,
    proveedorSeleccionado: Object,
    estados: Array
});

const form = ref({
    fecha_desde: props.filters.fecha_desde || '',
    fecha_hasta: props.filters.fecha_hasta || '',
    proveedor_id: props.filters.proveedor_id || '',
    estado_id: props.filters.estado_id || '',
});

// Estado para el buscador de proveedores
const searchProveedor = ref('');
const proveedoresResultados = ref([]);
const showProveedorDropdown = ref(false);
const buscandoProveedor = ref(false);
const proveedorActual = ref(props.proveedorSeleccionado || null);

// Inicializar el campo de búsqueda si hay proveedor seleccionado
onMounted(() => {
    if (props.proveedorSeleccionado) {
        searchProveedor.value = props.proveedorSeleccionado.razon_social;
    }
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (e) => {
    if (!e.target.closest('.proveedor-search-container')) {
        showProveedorDropdown.value = false;
    }
};

// Buscar proveedores en la API
const buscarProveedores = debounce(async () => {
    if (searchProveedor.value.length < 2) {
        proveedoresResultados.value = [];
        showProveedorDropdown.value = false;
        return;
    }
    buscandoProveedor.value = true;
    try {
        const response = await axios.get(route('api.proveedores.buscar'), { 
            params: { q: searchProveedor.value } 
        });
        proveedoresResultados.value = response.data;
        showProveedorDropdown.value = true;
    } catch (error) {
        console.error('Error buscando proveedores:', error);
    } finally {
        buscandoProveedor.value = false;
    }
}, 300);

// Seleccionar un proveedor
const seleccionarProveedor = (proveedor) => {
    proveedorActual.value = proveedor;
    searchProveedor.value = proveedor.razon_social;
    form.value.proveedor_id = proveedor.id;
    showProveedorDropdown.value = false;
};

// Limpiar el proveedor seleccionado
const limpiarProveedor = () => {
    proveedorActual.value = null;
    searchProveedor.value = '';
    form.value.proveedor_id = '';
    proveedoresResultados.value = [];
};

const exportar = () => {
    const params = new URLSearchParams(form.value).toString();
    window.location.href = route('reportes.compras.exportar') + '?' + params;
};

// Observar cambios en los filtros
watch(() => [form.value.fecha_desde, form.value.fecha_hasta, form.value.proveedor_id, form.value.estado_id], () => {
    router.get(route('reportes.compras'), form.value, {
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
        <Head title="Reporte de Compras" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Reporte de Compras y Proveedores
                </h2>
                <span class="text-sm text-gray-500">
                    Periodo: {{ new Date(form.fecha_desde).toLocaleDateString() }} - {{ new Date(form.fecha_hasta).toLocaleDateString() }}
                </span>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                        <div class="text-gray-500 text-xs font-bold uppercase">Total Gastado</div>
                        <div class="text-3xl font-bold text-gray-800 mt-2">{{ formatCurrency(kpis.total_gastado) }}</div>
                        <div class="text-xs text-blue-600 mt-1">Inversión en stock</div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-indigo-500">
                        <div class="text-gray-500 text-xs font-bold uppercase">Órdenes Generadas</div>
                        <div class="text-3xl font-bold text-gray-800 mt-2">{{ kpis.cantidad_ordenes }}</div>
                        <div class="text-xs text-indigo-600 mt-1">Volumen de compra</div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
                        <div class="text-gray-500 text-xs font-bold uppercase">Costo Promedio Orden</div>
                        <div class="text-3xl font-bold text-gray-800 mt-2">{{ formatCurrency(kpis.promedio_orden) }}</div>
                        <div class="text-xs text-green-600 mt-1">Ticket medio proveedor</div>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Desde</label>
                            <input type="date" v-model="form.fecha_desde" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hasta</label>
                            <input type="date" v-model="form.fecha_hasta" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                        </div>
                        
                        <!-- Buscador de Proveedores -->
                        <div class="relative proveedor-search-container">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Proveedor</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    v-model="searchProveedor"
                                    @input="buscarProveedores"
                                    @focus="searchProveedor.length >= 2 && (showProveedorDropdown = true)"
                                    placeholder="Buscar por razón social o CUIT..."
                                    autocomplete="off"
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm pr-8"
                                />
                                <button 
                                    v-if="proveedorActual" 
                                    @click="limpiarProveedor" 
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                <div v-if="buscandoProveedor" class="absolute inset-y-0 right-0 flex items-center pr-2">
                                    <svg class="animate-spin h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div 
                                v-if="showProveedorDropdown && proveedoresResultados.length > 0" 
                                class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto"
                            >
                                <ul class="py-1">
                                    <li 
                                        v-for="proveedor in proveedoresResultados" 
                                        :key="proveedor.id"
                                        @click="seleccionarProveedor(proveedor)"
                                        class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm"
                                    >
                                        <div class="font-medium text-gray-900">{{ proveedor.razon_social }}</div>
                                        <div class="text-xs text-gray-500">CUIT: {{ proveedor.cuit }}</div>
                                    </li>
                                </ul>
                            </div>
                            <div 
                                v-if="showProveedorDropdown && proveedoresResultados.length === 0 && searchProveedor.length >= 2 && !buscandoProveedor" 
                                class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg p-3 text-sm text-gray-500"
                            >
                                No se encontraron proveedores
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
                                Exportar
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" v-if="kpis.cantidad_ordenes > 0">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="font-bold text-gray-700 mb-4 text-center">Top Proveedores (Por Gasto)</h3>
                        <div class="h-64 relative">
                            <Bar :data="graficos.proveedores" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="font-bold text-gray-700 mb-4 text-center">Estado de Órdenes</h3>
                        <div class="h-64 relative flex justify-center">
                            <Doughnut :data="graficos.estados" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">N° Orden</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Proveedor</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="orden in ordenes.data" :key="orden.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600">
                                        {{ orden.numero_oc || '#' + orden.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ new Date(orden.fecha_emision || orden.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ orden.proveedor?.razon_social || 'Desconocido' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ orden.estado?.nombre || 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                        {{ formatCurrency(orden.total_final) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <a :href="route('ordenes.show', orden.id)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded hover:bg-indigo-100 transition">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                                <tr v-if="ordenes.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        No hay órdenes de compra registradas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        <Pagination :links="ordenes.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>