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
    ventas: Object,         // Paginador de Laravel (data, links, etc.)
    filters: Object,        // Filtros activos
    // La prop 'clientes_filtro' se mantiene en el backend, pero se ignora en el frontend 
    // porque usaremos el campo 'search' para buscar clientes.
});

// Estado reactivo de los filtros (inicializado con lo que viene del backend)
const form = ref({
    search: props.filters.search || '',
    estado: props.filters.estado || '',
    fecha_desde: props.filters.fecha_desde || '',
    fecha_hasta: props.filters.fecha_hasta || '',
    // No usamos cliente_id como filtro <select> en el UI.
});

// Watcher con Debounce: Detecta cambios en 'form' y recarga la página vía AJAX
watch(form, debounce(() => {
    router.get(route('ventas.index'), form.value, {
        preserveState: true,
        replace: true,
    });
}, 300), { deep: true });

// Función para limpiar filtros
const resetFilters = () => {
    form.value = {
        search: '',
        estado: '',
        fecha_desde: '',
        fecha_hasta: '',
    };
};

// Formateador de moneda
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

// Formateador de fecha
const formatDate = (dateString) => {
    if (!dateString) return '-';
    // Muestra fecha y hora para ser más preciso (Venta es 'datetime')
    return new Date(dateString).toLocaleDateString('es-AR', {
        year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute:'2-digit'
    });
};

// --- Lógica de Paginación Unificada ---
const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;'; // El primero siempre es "Anterior"
    if (index === totalLinks - 1) return '&raquo;'; // El último siempre es "Siguiente"
    return label;
};
</script>

<template>
    <Head title="Listado de Ventas" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Ventas (CU-07)
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-center">
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-indigo-500">
                        <p class="text-sm font-medium text-gray-500">Total Ventas (Periodo)</p>
                        <p class="text-2xl font-bold text-gray-900">$XX,XXX</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-green-500">
                        <p class="text-sm font-medium text-gray-500">Ventas Activas</p>
                        <p class="text-2xl font-bold text-gray-900">X,XXX</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-red-500">
                        <p class="text-sm font-medium text-gray-500">Ventas Anuladas</p>
                        <p class="text-2xl font-bold text-gray-900">XXX</p>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 md:space-x-4">
                        
                        <div class="flex-1">
                            <TextInput 
                                v-model="form.search" 
                                placeholder="Buscar N° Comprobante, Cliente (Nombre/DNI)..." 
                                class="w-full"
                            />
                        </div>

                        <div class="w-full md:w-40">
                            <select 
                                v-model="form.estado"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                            >
                                <option value="">Todos los estados</option>
                                <option value="activa">Activas</option>
                                <option value="anulada">Anuladas</option>
                            </select>
                        </div>

                        <div>
                            <Link :href="route('ventas.create')">
                                <PrimaryButton>
                                    + Nueva Venta
                                </PrimaryButton>
                            </Link>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Desde:</label>
                            <TextInput type="date" v-model="form.fecha_desde" class="w-full" />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hasta:</label>
                            <TextInput type="date" v-model="form.fecha_hasta" class="w-full" />
                        </div>

                        <div class="hidden md:block"></div> 

                        <div class="flex justify-end items-center">
                            <button 
                                @click="resetFilters" 
                                class="text-sm text-gray-600 hover:text-gray-900 underline"
                            >
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comprobante</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="venta in ventas.data" :key="venta.venta_id" class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(venta.fecha_venta) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ venta.numero_comprobante }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ venta.cliente?.nombre }} {{ venta.cliente?.apellido }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold text-right">
                                        {{ formatCurrency(venta.total) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span 
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            :class="venta.anulada ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                        >
                                            {{ venta.anulada ? 'ANULADA' : 'ACTIVA' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link 
                                            :href="route('ventas.show', venta.venta_id)" 
                                            class="text-indigo-600 hover:text-indigo-900 font-bold"
                                        >
                                            Ver Detalle
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="ventas.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                        <span class="text-lg font-medium">No se encontraron ventas con los filtros seleccionados.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200" v-if="ventas.links.length > 3">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in ventas.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-medium transition-all duration-150"
                                    :class="link.active 
                                        ? 'bg-indigo-600 text-white shadow-md ring-2 ring-indigo-300' 
                                        : 'bg-white text-gray-600 border border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'"
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