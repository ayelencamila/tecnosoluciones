<script setup>
/**
 * CU-22: Listado de Órdenes de Compra
 * 
 * Vista índice que muestra todas las OC generadas con opciones de filtrado.
 */
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AlertMessage from '@/Components/AlertMessage.vue';

const props = defineProps({
    ordenes: Object, // Paginación de órdenes
});

// Filtros
const filtroEstado = ref('');

// Helper para clases de estado
const estadoClass = (estado) => {
    if (!estado) return 'bg-gray-100 text-gray-800';
    switch (estado.nombre) {
        case 'Borrador': return 'bg-gray-100 text-gray-800';
        case 'Enviada': return 'bg-blue-100 text-blue-800';
        case 'Envío Fallido': return 'bg-red-100 text-red-800';
        case 'Confirmada': return 'bg-green-100 text-green-800';
        case 'Recibida Parcial': return 'bg-yellow-100 text-yellow-800';
        case 'Recibida Total': return 'bg-emerald-100 text-emerald-800';
        case 'Cancelada': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Icono según estado
const estadoIcon = (estado) => {
    if (!estado) return '📋';
    switch (estado.nombre) {
        case 'Borrador': return '📝';
        case 'Enviada': return '📤';
        case 'Envío Fallido': return '⚠️';
        case 'Confirmada': return '✅';
        case 'Recibida Parcial': return '📦';
        case 'Recibida Total': return '✔️';
        case 'Cancelada': return '❌';
        default: return '📋';
    }
};

// Formatear fecha
const formatFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Formatear moneda
const formatMoneda = (valor) => {
    return Number(valor).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2,
    });
};
</script>

<template>
    <Head title="Órdenes de Compra" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Órdenes de Compra</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <AlertMessage v-if="$page.props.flash.success" :message="$page.props.flash.success" type="success" />
                <AlertMessage v-if="$page.props.flash.error" :message="$page.props.flash.error" type="error" />

                <!-- Estadísticas rápidas -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-indigo-500">
                        <p class="text-sm font-medium text-gray-500">Total OC</p>
                        <p class="text-2xl font-bold text-gray-900">{{ ordenes.total }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                        <p class="text-sm font-medium text-gray-500">Enviadas</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ ordenes.data.filter(o => o.estado?.nombre === 'Enviada').length }}
                        </p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                        <p class="text-sm font-medium text-gray-500">Confirmadas</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ ordenes.data.filter(o => o.estado?.nombre === 'Confirmada').length }}
                        </p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                        <p class="text-sm font-medium text-gray-500">Envío Fallido</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ ordenes.data.filter(o => o.estado?.nombre === 'Envío Fallido').length }}
                        </p>
                    </div>
                </div>

                <!-- Barra de acciones -->
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <p class="text-sm text-gray-600">
                            Las órdenes de compra se generan automáticamente al elegir una oferta de proveedor.
                        </p>
                        <Link :href="route('ofertas.index')">
                            <PrimaryButton>Ver Ofertas de Compra</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <!-- Tabla de órdenes -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        N° OC
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Proveedor
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha Emisión
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="orden in ordenes.data" :key="orden.id" 
                                    class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('ordenes.show', orden.id)" 
                                              class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            {{ orden.numero_oc }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ orden.proveedor?.razon_social || 'Sin proveedor' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ orden.proveedor?.cuit }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatFecha(orden.fecha_emision) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-right">
                                        {{ formatMoneda(orden.total_final) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 inline-flex items-center text-xs font-semibold rounded-full"
                                              :class="estadoClass(orden.estado)">
                                            <span class="mr-1">{{ estadoIcon(orden.estado) }}</span>
                                            {{ orden.estado?.nombre || 'Sin estado' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                        <div class="flex justify-center space-x-2">
                                            <Link :href="route('ordenes.show', orden.id)" 
                                                  class="text-indigo-600 hover:text-indigo-900"
                                                  title="Ver detalle">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </Link>
                                            <a v-if="orden.archivo_pdf" 
                                               :href="route('ordenes.descargar-pdf', orden.id)" 
                                               class="text-green-600 hover:text-green-900"
                                               title="Descargar PDF">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="ordenes.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay órdenes de compra</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Las órdenes se generan desde ofertas elegidas.
                                        </p>
                                        <div class="mt-6">
                                            <Link :href="route('ofertas.index')" 
                                                  class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                                Ver Ofertas
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="ordenes.links && ordenes.links.length > 3" 
                         class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link v-if="ordenes.prev_page_url" :href="ordenes.prev_page_url"
                                  class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Anterior
                            </Link>
                            <Link v-if="ordenes.next_page_url" :href="ordenes.next_page_url"
                                  class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Siguiente
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Mostrando <span class="font-medium">{{ ordenes.from }}</span> a 
                                    <span class="font-medium">{{ ordenes.to }}</span> de 
                                    <span class="font-medium">{{ ordenes.total }}</span> resultados
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <Link v-for="link in ordenes.links" :key="link.label"
                                          :href="link.url || '#'"
                                          :class="[
                                              link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                              !link.url ? 'cursor-not-allowed opacity-50' : '',
                                              'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                          ]"
                                          v-html="link.label">
                                    </Link>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
