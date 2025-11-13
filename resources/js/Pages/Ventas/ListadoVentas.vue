<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; //sin color
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue'; //sin color
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { debounce } from 'lodash';

// Props que vienen del VentaController@index
const props = defineProps({
    ventas: Object,         // Paginador de Laravel (data, links, etc.)
    filters: Object,        // Filtros activos
    clientes_filtro: Array, // Lista para el <select> de clientes
});

// Estado reactivo de los filtros (inicializado con lo que viene del backend)
const form = ref({
    search: props.filters.search || '',
    estado: props.filters.estado || '',
    cliente_id: props.filters.cliente_id || '',
    fecha_desde: props.filters.fecha_desde || '',
    fecha_hasta: props.filters.fecha_hasta || '',
});

// Watcher con Debounce: Detecta cambios en 'form' y recarga la página vía AJAX
// Esto cumple con el principio de "Efectividad" de Kendall (no hay que dar clic en "Buscar" a cada rato)
watch(form, debounce(() => {
    router.get(route('ventas.index'), form.value, {
        preserveState: true, // Mantiene el scroll y el estado de la página
        replace: true,       // No llena el historial del navegador con cada letra
    });
}, 300), { deep: true });

// Función para limpiar filtros
const resetFilters = () => {
    form.value = {
        search: '',
        estado: '',
        cliente_id: '',
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
    return new Date(dateString).toLocaleDateString('es-AR', {
        year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute:'2-digit'
    });
};
</script>

<template>
    <Head title="Listado de Ventas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Ventas
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 md:space-x-4">
                        
                        <div class="flex-1">
                            <TextInput 
                                v-model="form.search" 
                                placeholder="Buscar por comprobante o cliente..." 
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
                        <select 
                            v-model="form.cliente_id"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                        >
                            <option value="">Todos los Clientes</option>
                            <option v-for="cli in clientes_filtro" :key="cli.clienteID" :value="cli.clienteID">
                                {{ cli.apellido }}, {{ cli.nombre }}
                            </option>
                        </select>

                        <TextInput type="date" v-model="form.fecha_desde" class="w-full" placeholder="Desde" />
                        
                        <TextInput type="date" v-model="form.fecha_hasta" class="w-full" placeholder="Hasta" />

                        <button 
                            @click="resetFilters" 
                            class="text-sm text-gray-600 hover:text-gray-900 underline text-right md:text-center"
                        >
                            Limpiar Filtros
                        </button>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="venta in ventas.data" :key="venta.venta_id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(venta.fecha_venta) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ venta.numero_comprobante }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ venta.cliente?.nombre }} {{ venta.cliente?.apellido }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">
                                        {{ formatCurrency(venta.total) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
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
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No se encontraron ventas con los filtros seleccionados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="ventas.links.length > 3">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-1 justify-between sm:hidden">
                                </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <template v-for="(link, key) in ventas.links" :key="key">
                                            <Link 
                                                v-if="link.url" 
                                                :href="link.url" 
                                                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                                :class="{'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': link.active, 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': !link.active}"
                                                v-html="link.label"
                                            />
                                            <span 
                                                v-else 
                                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                                v-html="link.label"
                                            ></span>
                                        </template>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>