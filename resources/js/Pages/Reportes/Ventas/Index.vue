<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Line, Doughnut } from 'vue-chartjs'; 
import { 
    Chart as ChartJS, 
    Title, 
    Tooltip, 
    Legend, 
    LineElement, 
    PointElement, 
    CategoryScale, 
    LinearScale, 
    ArcElement,
    Filler 
} from 'chart.js';
import axios from 'axios';
import { debounce } from 'lodash';

// Registrar componentes de gráficos
ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, ArcElement, Filler);

const props = defineProps({
    ventas: Object,
    kpis: Object,
    graficos: Object,
    filters: Object,
    clienteSeleccionado: Object // Cliente preseleccionado si existe
});

// Estado reactivo para los filtros
const form = ref({
    fecha_desde: props.filters.fecha_desde || '',
    fecha_hasta: props.filters.fecha_hasta || '',
    cliente_id: props.filters.cliente_id || '',
});

// Estado para el buscador de clientes
const searchCliente = ref('');
const clientesResultados = ref([]);
const showClienteDropdown = ref(false);
const buscandoCliente = ref(false);
const clienteActual = ref(props.clienteSeleccionado || null);

// Inicializar el campo de búsqueda si hay cliente seleccionado
onMounted(() => {
    if (props.clienteSeleccionado) {
        searchCliente.value = `${props.clienteSeleccionado.nombre} ${props.clienteSeleccionado.apellido}`;
    }
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (e) => {
    if (!e.target.closest('.cliente-search-container')) {
        showClienteDropdown.value = false;
    }
};

// Buscar clientes en la API
const buscarClientes = debounce(async () => {
    if (searchCliente.value.length < 2) {
        clientesResultados.value = [];
        showClienteDropdown.value = false;
        return;
    }
    buscandoCliente.value = true;
    try {
        const response = await axios.get(route('api.clientes.buscar'), { 
            params: { q: searchCliente.value } 
        });
        clientesResultados.value = response.data;
        showClienteDropdown.value = true;
    } catch (error) {
        console.error('Error buscando clientes:', error);
    } finally {
        buscandoCliente.value = false;
    }
}, 300);

// Seleccionar un cliente
const seleccionarCliente = (cliente) => {
    clienteActual.value = cliente;
    searchCliente.value = `${cliente.nombre} ${cliente.apellido}`;
    form.value.cliente_id = cliente.clienteID;
    showClienteDropdown.value = false;
};

// Limpiar el cliente seleccionado
const limpiarCliente = () => {
    clienteActual.value = null;
    searchCliente.value = '';
    form.value.cliente_id = '';
    clientesResultados.value = [];
};

// Función para exportar a Excel
const exportar = () => {
    const params = new URLSearchParams({
        fecha_desde: form.value.fecha_desde,
        fecha_hasta: form.value.fecha_hasta,
        cliente_id: form.value.cliente_id
    }).toString();
    
    window.location.href = route('reportes.ventas.exportar') + '?' + params;
};

// Observar cambios de fechas para recargar (cliente_id se maneja manualmente)
watch(() => [form.value.fecha_desde, form.value.fecha_hasta, form.value.cliente_id], () => {
    router.get(route('reportes.ventas'), form.value, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
});

// Formateo de moneda
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};
</script>

<template>
    <AppLayout>
        <Head title="Reporte de Ventas" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Reporte de Ventas y Facturación
                </h2>
                <span class="text-sm text-gray-500">
                    Periodo: {{ new Date(form.fecha_desde).toLocaleDateString() }} al {{ new Date(form.fecha_hasta).toLocaleDateString() }}
                </span>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b-4 border-green-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-gray-500 text-sm font-medium uppercase tracking-wider">Facturación Total</div>
                                <div class="text-3xl font-bold text-gray-900 mt-2">{{ formatCurrency(kpis.total_ingresos) }}</div>
                                <div class="text-xs text-green-600 mt-1 font-semibold">Ingresos brutos</div>
                            </div>
                            <div class="p-3 bg-green-50 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b-4 border-blue-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-gray-500 text-sm font-medium uppercase tracking-wider">Operaciones</div>
                                <div class="text-3xl font-bold text-gray-900 mt-2">{{ kpis.cantidad_ventas }}</div>
                                <div class="text-xs text-gray-400 mt-1">Ventas realizadas</div>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b-4 border-purple-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-gray-500 text-sm font-medium uppercase tracking-wider">Ticket Promedio</div>
                                <div class="text-3xl font-bold text-gray-900 mt-2">{{ formatCurrency(kpis.ticket_promedio) }}</div>
                                <div class="text-xs text-gray-400 mt-1">Promedio por operación</div>
                            </div>
                            <div class="p-3 bg-purple-50 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                            <input type="date" v-model="form.fecha_desde" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                            <input type="date" v-model="form.fecha_hasta" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>

                        <div class="relative cliente-search-container">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    v-model="searchCliente"
                                    @input="buscarClientes"
                                    @focus="searchCliente.length >= 2 && (showClienteDropdown = true)"
                                    placeholder="Buscar por nombre, apellido o DNI..."
                                    autocomplete="off"
                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm pr-8"
                                />
                                <!-- Botón limpiar -->
                                <button 
                                    v-if="clienteActual" 
                                    @click="limpiarCliente" 
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                <!-- Indicador de carga -->
                                <div v-if="buscandoCliente" class="absolute inset-y-0 right-0 flex items-center pr-2">
                                    <svg class="animate-spin h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            <!-- Dropdown de resultados -->
                            <div 
                                v-if="showClienteDropdown && clientesResultados.length > 0" 
                                class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                            >
                                <ul class="py-1">
                                    <li 
                                        v-for="cliente in clientesResultados" 
                                        :key="cliente.clienteID"
                                        @click="seleccionarCliente(cliente)"
                                        class="px-3 py-2 hover:bg-indigo-50 cursor-pointer text-sm"
                                    >
                                        <div class="font-medium text-gray-900">{{ cliente.apellido }}, {{ cliente.nombre }}</div>
                                        <div class="text-xs text-gray-500">DNI: {{ cliente.dni || 'N/A' }}</div>
                                    </li>
                                </ul>
                            </div>
                            <!-- Mensaje sin resultados -->
                            <div 
                                v-if="showClienteDropdown && clientesResultados.length === 0 && searchCliente.length >= 2 && !buscandoCliente" 
                                class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg p-3 text-sm text-gray-500"
                            >
                                No se encontraron clientes
                            </div>
                        </div>

                        <div>
                            <PrimaryButton @click="exportar" class="w-full justify-center bg-green-600 hover:bg-green-700 h-10 focus:ring-green-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Exportar Excel
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6" v-if="kpis.cantidad_ventas > 0">
                    <div class="md:col-span-2 bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800">Evolución Diaria</h3>
                        </div>
                        <div class="h-72 relative w-full">
                             <Line :data="graficos.tiempo" :options="{ responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }" />
                        </div>
                    </div>
                    
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800">Por Vendedor</h3>
                        </div>
                        <div class="h-64 relative w-full flex justify-center">
                            <Doughnut :data="graficos.vendedores" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-md font-semibold text-gray-700">Detalle de Operaciones</h3>
                        <span class="text-xs text-gray-500">Mostrando {{ ventas.from }}-{{ ventas.to }} de {{ ventas.total }} registros</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Vendedor</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="venta in ventas.data" :key="venta.venta_id || venta.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ new Date(venta.fecha_venta || venta.created_at).toLocaleDateString() }} 
                                        <span class="text-xs text-gray-400 block">{{ new Date(venta.fecha_venta || venta.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ venta.cliente?.nombre }} {{ venta.cliente?.apellido }}</div>
                                        <div class="text-xs text-gray-500">DNI: {{ venta.cliente?.dni || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 hidden md:table-cell">
                                        {{ venta.vendedor?.name || 'Sistema' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                        {{ formatCurrency(venta.total) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800" 
                                              v-if="venta.estado?.nombreEstado === 'Completada'">
                                            Completada
                                        </span>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800" 
                                              v-else-if="venta.estado?.nombreEstado === 'Anulada'">
                                            Anulada
                                        </span>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800" 
                                              v-else-if="venta.estado?.nombreEstado === 'Pendiente'">
                                            Pendiente
                                        </span>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800" v-else>
                                            {{ venta.estado?.nombreEstado || 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <a :href="route('ventas.show', venta.venta_id || venta.id)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded hover:bg-indigo-100 transition">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                                <tr v-if="ventas.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="h-10 w-10 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p>No se encontraron ventas para los filtros seleccionados.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        <Pagination :links="ventas.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>