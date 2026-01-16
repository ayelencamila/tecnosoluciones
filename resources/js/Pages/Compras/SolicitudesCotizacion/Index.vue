<script setup>
/**
 * Vista: Listado de Solicitudes de Cotización (CU-20)
 */
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import debounce from 'lodash/debounce'

const props = defineProps({
    solicitudes: Object,
    estados: Array,
    filtros: Object,
    resumenStock: Object,
})

const filtros = ref({
    codigo: props.filtros.codigo || '',
    estado_id: props.filtros.estado_id || '',
    fecha_desde: props.filtros.fecha_desde || '',
    fecha_hasta: props.filtros.fecha_hasta || '',
})

const aplicarFiltros = debounce(() => {
    router.get(route('solicitudes-cotizacion.index'), filtros.value, {
        preserveState: true,
        preserveScroll: true,
    })
}, 300)

watch(filtros, aplicarFiltros, { deep: true })

function limpiarFiltros() {
    filtros.value = { codigo: '', estado_id: '', fecha_desde: '', fecha_hasta: '' }
}

function getEstadoClass(estado) {
    const clases = {
        'Abierta': 'bg-blue-100 text-blue-800',
        'Enviada': 'bg-yellow-100 text-yellow-800',
        'Cerrada': 'bg-green-100 text-green-800',
        'Vencida': 'bg-gray-100 text-gray-800',
        'Cancelada': 'bg-red-100 text-red-800',
    }
    return clases[estado] || 'bg-gray-100 text-gray-800'
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-AR')
}
</script>

<template>
    <AppLayout title="Solicitudes de Cotización">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Solicitudes de Cotización
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Gestión de solicitudes enviadas a proveedores
            </p>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Barra de acciones -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('monitoreo-stock.index')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Monitoreo de Stock
                        </Link>
                    </div>
                    <Link
                        :href="route('solicitudes-cotizacion.create')"
                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nueva Solicitud
                    </Link>
                </div>

                <!-- Alerta de stock bajo -->
                <div v-if="resumenStock?.bajo_stock > 0" class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-amber-800">
                                    {{ resumenStock.bajo_stock }} producto(s) bajo stock mínimo
                                </p>
                                <p class="text-sm text-amber-700 mt-0.5">
                                    {{ resumenStock.sin_stock }} sin stock disponible
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('solicitudes-cotizacion.generar-automaticas')"
                            method="post"
                            as="button"
                            class="inline-flex items-center px-3 py-1.5 bg-amber-600 text-white text-sm font-medium rounded-md hover:bg-amber-700 transition-colors"
                        >
                            Generar Solicitudes Automáticas
                        </Link>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Código</label>
                            <input
                                type="text"
                                v-model="filtros.codigo"
                                placeholder="SOL-..."
                                class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Estado</label>
                            <select v-model="filtros.estado_id" class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos los estados</option>
                                <option v-for="estado in estados" :key="estado.id" :value="estado.id">
                                    {{ estado.nombre }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Fecha desde</label>
                            <input
                                type="date"
                                v-model="filtros.fecha_desde"
                                class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Fecha hasta</label>
                            <input
                                type="date"
                                v-model="filtros.fecha_hasta"
                                class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="limpiarFiltros"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
                            >
                                Limpiar filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de solicitudes -->
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Código
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Fecha Emisión
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Vencimiento
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Productos
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Respuestas
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="solicitud in solicitudes.data" :key="solicitud.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link
                                            :href="route('solicitudes-cotizacion.show', solicitud.id)"
                                            class="font-mono text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                        >
                                            {{ solicitud.codigo_solicitud }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ formatDate(solicitud.fecha_emision) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="new Date(solicitud.fecha_vencimiento) < new Date() ? 'text-red-600 font-medium' : 'text-gray-600'">
                                            {{ formatDate(solicitud.fecha_vencimiento) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-600">
                                        {{ solicitud.detalles?.length || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                        <span class="text-green-600 font-medium">
                                            {{ solicitud.cotizaciones_proveedores?.filter(c => c.fecha_respuesta).length || 0 }}
                                        </span>
                                        <span class="text-gray-400">/</span>
                                        <span class="text-gray-600">
                                            {{ solicitud.cotizaciones_proveedores?.length || 0 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span
                                            :class="getEstadoClass(solicitud.estado?.nombre)"
                                            class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full"
                                        >
                                            {{ solicitud.estado?.nombre }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <Link
                                            :href="route('solicitudes-cotizacion.show', solicitud.id)"
                                            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                        >
                                            Ver detalle
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Estado vacío -->
                    <div v-if="!solicitudes.data?.length" class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-4 text-sm text-gray-500">No hay solicitudes de cotización registradas</p>
                        <Link
                            :href="route('solicitudes-cotizacion.create')"
                            class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        >
                            Crear primera solicitud
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Paginación -->
                <Pagination v-if="solicitudes.links && solicitudes.data?.length" :links="solicitudes.links" class="mt-6" />
            </div>
        </div>
    </AppLayout>
</template>
