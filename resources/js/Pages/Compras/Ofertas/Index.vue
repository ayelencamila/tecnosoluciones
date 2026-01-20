<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { debounce } from 'lodash';

const props = defineProps({
    ofertas: Object,
    filters: Object,
    counts: Object,
    productosConOfertas: {
        type: Array,
        default: () => []
    },
    solicitudesConRespuestas: {
        type: Array,
        default: () => []
    },
});

const search = ref(props.filters?.search || '');

// Búsqueda con debounce
watch(search, debounce((value) => {
    router.get(
        route('ofertas.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 300));

const estadoClass = (estado) => {
    switch (estado) {
        case 'Pendiente': return 'bg-yellow-100 text-yellow-800';
        case 'Pre-aprobada': return 'bg-blue-100 text-blue-800';
        case 'Elegida': return 'bg-green-100 text-green-800';
        case 'Procesada': return 'bg-indigo-100 text-indigo-800';
        case 'Rechazada': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Paginación - Mismo patrón que Clientes
const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};

// Ir a comparar por producto
const compararPorProducto = (productoId) => {
    router.get(route('ofertas.comparar', { producto_id: productoId }));
};
</script>

<template>
    <Head title="Ofertas de Compra" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Ofertas de Compra</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- TARJETAS DE RESUMEN -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 text-center">
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-indigo-500">
                        <p class="text-sm font-medium text-gray-500">Total Ofertas</p>
                        <p class="text-2xl font-bold text-gray-900">{{ props.counts?.total || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-yellow-500">
                        <p class="text-sm font-medium text-gray-500">Pendientes</p>
                        <p class="text-2xl font-bold text-gray-900">{{ props.counts?.pendientes || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-green-500">
                        <p class="text-sm font-medium text-gray-500">Elegidas</p>
                        <p class="text-2xl font-bold text-gray-900">{{ props.counts?.elegidas || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-purple-500">
                        <p class="text-sm font-medium text-gray-500">Por Convertir</p>
                        <p class="text-2xl font-bold text-gray-900">{{ solicitudesConRespuestas.length || 0 }}</p>
                    </div>
                </div>

                <!-- ALERTA: SOLICITUDES CON RESPUESTAS PENDIENTES -->
                <div v-if="solicitudesConRespuestas && solicitudesConRespuestas.length > 0" 
                     class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg shadow-sm border-l-4 border-blue-500 p-5 mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <div>
                                <h3 class="text-base font-semibold text-blue-800">
                                    Cotizaciones Listas para Registrar
                                </h3>
                                <p class="text-sm text-blue-600">
                                    Los proveedores respondieron — Complete los datos y formalice la oferta
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1.5 bg-blue-600 text-white text-sm font-bold rounded-full shadow-sm">
                            {{ solicitudesConRespuestas.length }}
                        </span>
                    </div>
                    
                    <div class="space-y-3 mt-4">
                        <div v-for="solicitud in solicitudesConRespuestas" :key="solicitud.id" 
                             class="bg-white rounded-lg p-4 shadow-sm border border-blue-100 hover:shadow-md transition-shadow duration-150">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <Link :href="`/solicitudes-cotizacion/${solicitud.id}`" 
                                          class="text-base font-semibold text-blue-700 hover:text-blue-900 hover:underline">
                                        {{ solicitud.codigo_solicitud }}
                                    </Link>
                                    <span class="ml-3 text-sm text-gray-500">
                                        {{ new Date(solicitud.fecha_emision).toLocaleDateString('es-AR') }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- UN CARD POR CADA PROVEEDOR QUE RESPONDIÓ -->
                            <div class="space-y-2">
                                <div v-for="cotizacion in solicitud.cotizaciones_proveedores" :key="cotizacion.id"
                                     class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <span class="text-sm font-semibold text-blue-800">
                                                {{ cotizacion.proveedor.razon_social }}
                                            </span>
                                            <span class="ml-2 text-xs text-blue-600">
                                                {{ cotizacion.respuestas.length }} producto(s)
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <Link :href="route('ofertas.create', { solicitud_id: solicitud.id, cotizacion_id: cotizacion.id })"
                                          class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md shadow-sm transition-colors duration-150">
                                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        Registrar
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ALERTA: PRODUCTOS CON MÚLTIPLES OFERTAS -->
                <div v-if="productosConOfertas && productosConOfertas.length > 0" 
                     class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg shadow-sm border-l-4 border-purple-500 p-5 mb-6">
                    <div class="flex items-center mb-3">
                        <svg class="w-6 h-6 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <div>
                            <h3 class="text-base font-semibold text-purple-800">
                                Productos con Múltiples Ofertas
                            </h3>
                            <p class="text-sm text-purple-600">
                                CU-21 Paso 10: Compare y seleccione la mejor opción
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <button 
                            v-for="prod in productosConOfertas" 
                            :key="prod.id"
                            @click="compararPorProducto(prod.id)"
                            class="inline-flex items-center px-4 py-2 text-sm bg-white rounded-lg border-2 border-purple-300 text-purple-700 hover:bg-purple-100 hover:border-purple-400 transition-all duration-150 shadow-sm">
                            <span class="font-medium">{{ prod.nombre }}</span>
                            <span class="ml-2 px-2 py-0.5 text-xs bg-purple-600 text-white rounded-full font-bold">
                                {{ prod.ofertas_count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- BARRA DE BÚSQUEDA Y ACCIÓN -->
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex-1 w-full">
                            <TextInput
                                v-model="search"
                                placeholder="Buscar por código, proveedor o estado..."
                                class="w-full"
                            />
                        </div>
                        <Link :href="route('ofertas.create')">
                            <PrimaryButton>+ Registrar Oferta Manual</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <!-- TABLA DE OFERTAS -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Oferta</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proveedor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha / Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="oferta in ofertas.data" :key="oferta.id" class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <Link :href="route('ofertas.show', oferta.id)" 
                                              class="text-sm font-semibold text-indigo-600 hover:text-indigo-900 hover:underline">
                                            {{ oferta.codigo_oferta }}
                                        </Link>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ oferta.detalles_count || 0 }} producto(s)
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ oferta.proveedor.razon_social }}</div>
                                        <div class="text-xs text-gray-500">{{ oferta.proveedor.email || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            {{ new Date(oferta.fecha_recepcion).toLocaleDateString('es-AR') }}
                                        </div>
                                        <span class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                              :class="estadoClass(oferta.estado.nombre)">
                                            {{ oferta.estado.nombre }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="text-sm font-bold text-gray-900">
                                            ${{ Number(oferta.total_estimado).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('ofertas.show', oferta.id)" 
                                              class="text-indigo-600 hover:text-indigo-900 font-bold" 
                                              title="Ver Detalle">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="ofertas.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="text-lg font-medium">No se encontraron ofertas registradas</span>
                                        <p class="text-sm text-gray-400 mt-2">Comience registrando una oferta manual o convierta una respuesta de proveedor</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- PAGINACIÓN -->
                    <div class="px-6 py-4 border-t border-gray-200" v-if="ofertas.links && ofertas.links.length > 3">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in ofertas.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-medium transition-all duration-150"
                                    :class="link.active 
                                        ? 'bg-indigo-600 text-white shadow-md ring-2 ring-indigo-300' 
                                        : 'bg-white text-gray-600 border border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'"
                                >
                                    <span v-html="getPaginationLabel(link.label, k, ofertas.links.length)"></span>
                                </Link>
                                <span 
                                    v-else 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm text-gray-300 border border-gray-200 cursor-not-allowed" 
                                    v-html="getPaginationLabel(link.label, k, ofertas.links.length)"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>