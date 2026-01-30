<script setup>
/**
 * Vista: Listado de Solicitudes de Cotización (CU-20)
 */
import { ref, watch } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/Modal.vue'
import DangerButton from '@/Components/DangerButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
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

// Modal de eliminación
const confirmingDeletion = ref(false)
const solicitudToDelete = ref(null)

function confirmDelete(solicitud) {
    solicitudToDelete.value = solicitud
    confirmingDeletion.value = true
}

function closeModal() {
    confirmingDeletion.value = false
    solicitudToDelete.value = null
}

function deleteSolicitud() {
    if (!solicitudToDelete.value) return
    
    router.delete(route('solicitudes-cotizacion.destroy', solicitudToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    })
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
                <div class="flex items-center justify-end mb-6">
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

                <!-- Alerta informativa de stock bajo -->
                <div v-if="resumenStock?.bajo_stock > 0" class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-amber-800">
                                <span class="font-medium">{{ resumenStock.bajo_stock }} producto(s)</span> bajo stock mínimo
                                <span v-if="resumenStock.sin_stock > 0" class="text-amber-700">
                                    • {{ resumenStock.sin_stock }} sin stock
                                </span>
                            </p>
                        </div>
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
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span 
                                                v-if="solicitud.cotizaciones_proveedores?.filter(c => c.fecha_respuesta).length > 0"
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800"
                                            >
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ solicitud.cotizaciones_proveedores.filter(c => c.fecha_respuesta).length }}
                                            </span>
                                            <span class="text-gray-400">/</span>
                                            <span class="text-gray-600 font-medium">
                                                {{ solicitud.cotizaciones_proveedores?.length || 0 }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span
                                            :class="getEstadoClass(solicitud.estado?.nombre)"
                                            class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full"
                                        >
                                            {{ solicitud.estado?.nombre }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex justify-end space-x-3 items-center">
                                            <Link :href="route('solicitudes-cotizacion.show', solicitud.id)" class="text-indigo-600 hover:text-indigo-900 font-bold" title="Ver Detalle">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </Link>
                                            <button @click="confirmDelete(solicitud)" class="text-red-600 hover:text-red-900 transition" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                            </button>
                                        </div>
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

        <!-- Modal de confirmación de eliminación -->
        <Modal :show="confirmingDeletion" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white text-center mb-2">
                    ¿Está seguro de eliminar esta solicitud?
                </h2>
                <p class="text-center text-sm text-gray-600 dark:text-gray-400 mb-1">
                    Solicitud: <strong class="text-gray-900 dark:text-white">{{ solicitudToDelete?.codigo_solicitud }}</strong>
                </p>
                <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Esta acción no se puede deshacer.
                </p>
                <div class="flex gap-3 justify-center">
                    <button
                        @click="closeModal"
                        class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button
                        @click="deleteSolicitud"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
