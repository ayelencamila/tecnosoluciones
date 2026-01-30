<script setup>
/**
 * Vista: Tablero de Control de Solicitudes de Cotización (CU-20)
 * Diseño: Dashboard operativo con KPIs interactivos y acciones rápidas.
 * 
 * Flujo: Index → Show → Comparar → Ordenes/Show
 */
import { ref, computed, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/Modal.vue'
import debounce from 'lodash/debounce'

const props = defineProps({
    solicitudes: Object,
    estados: Array,
    filtros: Object,
    resumenStock: Object,
})

// --- Estado Reactivo ---
const filtros = ref({
    codigo: props.filtros.codigo || '',
    estado_id: props.filtros.estado_id || '',
    fecha_desde: props.filtros.fecha_desde || '',
    fecha_hasta: props.filtros.fecha_hasta || '',
})

const filtroRapido = ref(null)
const confirmingDeletion = ref(false)
const solicitudToDelete = ref(null)

// --- KPIs calculados ---
const kpis = computed(() => {
    const data = props.solicitudes?.data || []
    return {
        borradores: data.filter(s => s.estado?.nombre === 'Pendiente de Revisión').length,
        enviadas: data.filter(s => s.estado?.nombre === 'Enviada').length,
        listasParaComprar: data.filter(s => {
            const tieneRespuestas = s.cotizaciones_proveedores?.some(c => c.fecha_respuesta)
            const noTieneOC = !s.orden_compra_id
            return tieneRespuestas && noTieneOC && ['Enviada', 'Abierta'].includes(s.estado?.nombre)
        }).length,
    }
})

// --- Lógica de Filtros ---
const aplicarFiltros = debounce(() => {
    router.get(route('solicitudes-cotizacion.index'), filtros.value, {
        preserveState: true,
        preserveScroll: true,
    })
}, 300)

watch(filtros, aplicarFiltros, { deep: true })

function limpiarFiltros() {
    filtros.value = { codigo: '', estado_id: '', fecha_desde: '', fecha_hasta: '' }
    filtroRapido.value = null
}

function aplicarFiltroRapido(tipo) {
    if (filtroRapido.value === tipo) {
        filtroRapido.value = null
        filtros.value.estado_id = ''
    } else {
        filtroRapido.value = tipo
        const estadoMap = {
            'borradores': props.estados.find(e => e.nombre === 'Pendiente de Revisión')?.id,
            'enviadas': props.estados.find(e => e.nombre === 'Enviada')?.id,
            'listas': '',
        }
        filtros.value.estado_id = estadoMap[tipo] || ''
    }
}

// --- Helpers Visuales ---
function getEstadoConfig(estado) {
    const configs = {
        'Pendiente de Revisión': { 
            bg: 'bg-amber-50', 
            text: 'text-amber-700', 
            border: 'border-amber-200',
            label: 'Borrador'
        },
        'Abierta': { 
            bg: 'bg-blue-50', 
            text: 'text-blue-700', 
            border: 'border-blue-200',
            label: 'Abierta'
        },
        'Enviada': { 
            bg: 'bg-indigo-50', 
            text: 'text-indigo-700', 
            border: 'border-indigo-200',
            label: 'Enviada'
        },
        'Cerrada': { 
            bg: 'bg-emerald-50', 
            text: 'text-emerald-700', 
            border: 'border-emerald-200',
            label: 'Cerrada'
        },
        'Vencida': { 
            bg: 'bg-gray-50', 
            text: 'text-gray-500', 
            border: 'border-gray-200',
            label: 'Vencida'
        },
        'Cancelada': { 
            bg: 'bg-red-50', 
            text: 'text-red-700', 
            border: 'border-red-200',
            label: 'Cancelada'
        },
    }
    return configs[estado] || { bg: 'bg-gray-50', text: 'text-gray-600', border: 'border-gray-200', label: estado }
}

function getProgreso(solicitud) {
    const total = solicitud.cotizaciones_proveedores?.length || 0
    const respondidos = solicitud.cotizaciones_proveedores?.filter(c => c.fecha_respuesta).length || 0
    return { total, respondidos, porcentaje: total > 0 ? Math.round((respondidos / total) * 100) : 0 }
}

function formatDate(date) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function estaVencida(solicitud) {
    return new Date(solicitud.fecha_vencimiento) < new Date()
}

function getAccionPrincipal(solicitud) {
    const estado = solicitud.estado?.nombre
    if (estado === 'Pendiente de Revisión') {
        return { label: 'Revisar y Enviar', style: 'primary' }
    }
    if (estado === 'Enviada' || estado === 'Abierta') {
        const progreso = getProgreso(solicitud)
        if (progreso.respondidos > 0) {
            return { label: 'Comparar Ofertas', style: 'success' }
        }
        return { label: 'Ver Estado', style: 'secondary' }
    }
    if (estado === 'Cerrada') {
        return { label: 'Ver Resultado', style: 'success' }
    }
    return { label: 'Ver Detalle', style: 'secondary' }
}

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

function puedeEliminar(solicitud) {
    const estadosEliminables = ['Vencida', 'Cancelada', 'Pendiente de Revisión']
    return estadosEliminables.includes(solicitud.estado?.nombre)
}
</script>

<template>
    <AppLayout title="Gestión de Compras">
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                        Panel de Compras
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Monitoreo y gestión de solicitudes de cotización
                    </p>
                </div>
                <Link
                    :href="route('solicitudes-cotizacion.create')"
                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva Solicitud
                </Link>
            </div>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- KPIs Interactivos -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Productos Bajo Mínimo -->
                    <div class="bg-white overflow-hidden rounded-xl border border-gray-200 shadow-sm p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Productos Bajo Mínimo</p>
                            <p class="mt-1 text-3xl font-bold text-gray-900">{{ resumenStock?.bajo_stock || 0 }}</p>
                        </div>
                        <div class="p-3 bg-red-50 rounded-xl">
                            <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Borradores - Clickeable -->
                    <button 
                        @click="aplicarFiltroRapido('borradores')"
                        class="bg-white overflow-hidden rounded-xl border-2 shadow-sm p-5 flex items-center justify-between text-left transition-all duration-200 hover:shadow-md"
                        :class="filtroRapido === 'borradores' ? 'border-amber-400 ring-2 ring-amber-100' : 'border-gray-200 hover:border-amber-200'"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-500">Borradores por Aprobar</p>
                            <p class="mt-1 text-3xl font-bold text-amber-600">{{ kpis.borradores }}</p>
                        </div>
                        <div class="p-3 bg-amber-50 rounded-xl">
                            <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                    </button>

                    <!-- Esperando Respuestas - Clickeable -->
                    <button 
                        @click="aplicarFiltroRapido('enviadas')"
                        class="bg-white overflow-hidden rounded-xl border-2 shadow-sm p-5 flex items-center justify-between text-left transition-all duration-200 hover:shadow-md"
                        :class="filtroRapido === 'enviadas' ? 'border-indigo-400 ring-2 ring-indigo-100' : 'border-gray-200 hover:border-indigo-200'"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-500">Esperando Respuestas</p>
                            <p class="mt-1 text-3xl font-bold text-indigo-600">{{ kpis.enviadas }}</p>
                        </div>
                        <div class="p-3 bg-indigo-50 rounded-xl">
                            <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </button>

                    <!-- Listas para Comprar - Clickeable -->
                    <button 
                        @click="aplicarFiltroRapido('listas')"
                        class="bg-white overflow-hidden rounded-xl border-2 shadow-sm p-5 flex items-center justify-between text-left transition-all duration-200 hover:shadow-md"
                        :class="filtroRapido === 'listas' ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-gray-200 hover:border-emerald-200'"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-500">Listas para Comprar</p>
                            <p class="mt-1 text-3xl font-bold text-emerald-600">{{ kpis.listasParaComprar }}</p>
                        </div>
                        <div class="p-3 bg-emerald-50 rounded-xl">
                            <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </button>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="lg:col-span-1">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Buscar</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    v-model="filtros.codigo"
                                    placeholder="Código o Proveedor..."
                                    class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Estado</label>
                            <select v-model="filtros.estado_id" class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="estado in estados" :key="estado.id" :value="estado.id">
                                    {{ estado.nombre }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Desde</label>
                            <input type="date" v-model="filtros.fecha_desde" class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Hasta</label>
                            <input type="date" v-model="filtros.fecha_hasta" class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="limpiarFiltros"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla Principal -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Solicitud</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Progreso</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vencimiento</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="solicitud in solicitudes.data" :key="solicitud.id" class="hover:bg-gray-50/50 transition-colors group">
                                    
                                    <!-- Columna: Solicitud -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-11 w-11 flex items-center justify-center rounded-xl bg-gray-100 text-gray-500 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <Link :href="route('solicitudes-cotizacion.show', solicitud.id)" class="text-sm font-bold text-gray-900 hover:text-indigo-600 transition-colors">
                                                    {{ solicitud.codigo_solicitud }}
                                                </Link>
                                                <div class="text-xs text-gray-500 mt-0.5">
                                                    {{ solicitud.detalles?.length || 0 }} productos
                                                    <span class="text-gray-300 mx-1">·</span>
                                                    {{ solicitud.cotizaciones_proveedores?.length || 0 }} proveedores
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Columna: Estado con Badge -->
                                    <td class="px-6 py-4">
                                        <span 
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                                            :class="[getEstadoConfig(solicitud.estado?.nombre).bg, getEstadoConfig(solicitud.estado?.nombre).text, getEstadoConfig(solicitud.estado?.nombre).border]"
                                        >
                                            <svg v-if="solicitud.estado?.nombre === 'Pendiente de Revisión'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <svg v-else-if="solicitud.estado?.nombre === 'Enviada'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                            </svg>
                                            <svg v-else-if="solicitud.estado?.nombre === 'Cerrada'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            {{ getEstadoConfig(solicitud.estado?.nombre).label }}
                                        </span>
                                    </td>

                                    <!-- Columna: Progreso -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 max-w-[120px]">
                                                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                                    <div 
                                                        class="h-full rounded-full transition-all duration-500"
                                                        :class="getProgreso(solicitud).porcentaje === 100 ? 'bg-emerald-500' : 'bg-indigo-500'"
                                                        :style="{ width: `${getProgreso(solicitud).porcentaje}%` }"
                                                    ></div>
                                                </div>
                                            </div>
                                            <span class="text-xs text-gray-500 whitespace-nowrap">
                                                {{ getProgreso(solicitud).respondidos }} de {{ getProgreso(solicitud).total }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Columna: Vencimiento -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <svg v-if="estaVencida(solicitud)" class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span 
                                                class="text-sm"
                                                :class="estaVencida(solicitud) ? 'text-red-600 font-semibold' : 'text-gray-600'"
                                            >
                                                {{ formatDate(solicitud.fecha_vencimiento) }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Columna: Acción Principal -->
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Botón Ver/Editar -->
                                            <Link 
                                                :href="route('solicitudes-cotizacion.show', solicitud.id)"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-500 hover:text-indigo-600 hover:border-indigo-300 hover:bg-indigo-50 transition-all duration-150"
                                                title="Ver detalle"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </Link>

                                            <!-- Botón Comparar (solo si tiene respuestas) -->
                                            <Link 
                                                v-if="solicitud.estado?.nombre === 'Enviada' && getProgreso(solicitud).respondidos > 0"
                                                :href="route('solicitudes-cotizacion.comparar', solicitud.id)"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-600 hover:text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100 transition-all duration-150"
                                                title="Comparar ofertas"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                                </svg>
                                            </Link>

                                            <!-- Botón Enviar (solo si es borrador) -->
                                            <Link 
                                                v-if="solicitud.estado?.nombre === 'Pendiente de Revisión'"
                                                :href="route('solicitudes-cotizacion.show', solicitud.id)"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-amber-200 bg-amber-50 text-amber-600 hover:text-amber-700 hover:border-amber-300 hover:bg-amber-100 transition-all duration-150"
                                                title="Enviar a proveedores"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                                </svg>
                                            </Link>

                                            <!-- Botón Eliminar -->
                                            <button 
                                                v-if="puedeEliminar(solicitud)"
                                                @click="confirmDelete(solicitud)" 
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-400 hover:text-red-600 hover:border-red-300 hover:bg-red-50 transition-all duration-150" 
                                                title="Eliminar"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!solicitudes.data?.length" class="px-6 py-16 text-center bg-white">
                        <div class="mx-auto h-16 w-16 text-gray-300 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">No hay solicitudes</h3>
                        <p class="mt-1 text-sm text-gray-500">Comienza creando una nueva solicitud o espera al monitoreo automático.</p>
                        <div class="mt-6">
                            <Link
                                :href="route('solicitudes-cotizacion.create')"
                                class="inline-flex items-center px-4 py-2.5 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Crear solicitud manual
                            </Link>
                        </div>
                    </div>
                </div>

                <Pagination v-if="solicitudes.links && solicitudes.data?.length" :links="solicitudes.links" />
            </div>
        </div>

        <!-- Modal Confirmación Eliminar -->
        <Modal :show="confirmingDeletion" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900 text-center mb-2">¿Eliminar solicitud?</h2>
                <p class="text-center text-sm text-gray-500 mb-6">
                    Estás a punto de eliminar la solicitud <span class="font-bold text-gray-900">{{ solicitudToDelete?.codigo_solicitud }}</span>. Esta acción no se puede deshacer.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button @click="deleteSolicitud" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
