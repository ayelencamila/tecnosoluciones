<script setup>
/**
 * Vista: Centro de Control de Solicitud (CU-20 / CU-21)
 * Diseño: Pantalla dividida - Contexto (izquierda) + Estado Proveedores (derecha)
 * 
 * Flujo: Index → Show → Comparar → Ordenes/Show
 */
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    solicitud: Object,
    ranking: Array,
    cotizacionGanadora: Object,
    ordenCompra: Object,
    puedeEnviar: Boolean,
})

// --- Estados de UI ---
const mostrarModalEnvio = ref(false)
const mostrarModalReenvio = ref(false)
const mostrarModalEliminar = ref(false)
const cotizacionReenviar = ref(null)

// --- Formularios ---
const formEnvio = useForm({ canal: 'inteligente' })
const formReenvio = useForm({ canal: 'whatsapp' })
const formEliminar = useForm({})

// --- Computed ---
const progreso = computed(() => {
    const total = props.solicitud.cotizaciones_proveedores?.length || 0
    const respondidos = props.solicitud.cotizaciones_proveedores?.filter(c => c.fecha_respuesta).length || 0
    return { total, respondidos, porcentaje: total > 0 ? Math.round((respondidos / total) * 100) : 0 }
})

const tieneRespuestas = computed(() => progreso.value.respondidos > 0)

// --- Helpers de Estado ---
function esBorrador() {
    return props.solicitud.estado?.nombre === 'Pendiente de Revisión'
}

function estaAbierta() {
    return props.solicitud.estado?.nombre === 'Abierta'
}

function tieneProveedoresPendientes() {
    return props.solicitud.cotizaciones_proveedores?.some(c => c.estado_envio === 'Pendiente')
}

function puedeEnviar() {
    return (esBorrador() || estaAbierta()) && tieneProveedoresPendientes()
}

function estaCerrada() {
    return props.solicitud.estado?.nombre === 'Cerrada'
}

function estaFinalizada() {
    return ['Cerrada', 'Vencida', 'Cancelada'].includes(props.solicitud.estado?.nombre)
}

function puedeReenviar(cotizacion) {
    if (estaFinalizada()) return false
    return cotizacion.estado_envio === 'Enviado' 
        && !cotizacion.fecha_respuesta 
        && !cotizacion.motivo_rechazo
        && new Date(props.solicitud.fecha_vencimiento) > new Date()
}

function puedeEliminar() {
    const estado = props.solicitud.estado?.nombre
    return ['Pendiente de Revisión', 'Cancelada', 'Vencida'].includes(estado)
}

// --- Acciones ---
function aprobarYEnviar() {
    formEnvio.post(route('solicitudes-cotizacion.enviar', props.solicitud.id), {
        onSuccess: () => mostrarModalEnvio.value = false
    })
}

function abrirModalReenvio(cotizacion) {
    cotizacionReenviar.value = cotizacion
    mostrarModalReenvio.value = true
}

function reenviarRecordatorio() {
    formReenvio.post(route('solicitudes-cotizacion.reenviar', [props.solicitud.id, cotizacionReenviar.value.id]), {
        onSuccess: () => {
            mostrarModalReenvio.value = false
            cotizacionReenviar.value = null
        }
    })
}

function cerrarSolicitud() {
    if (confirm('¿Cerrar esta solicitud? Ya no se recibirán más ofertas.')) {
        useForm({}).post(route('solicitudes-cotizacion.cerrar', props.solicitud.id))
    }
}

function cancelarSolicitud() {
    if (confirm('¿Cancelar esta solicitud?')) {
        useForm({}).post(route('solicitudes-cotizacion.cancelar', props.solicitud.id))
    }
}

function eliminarSolicitud() {
    formEliminar.delete(route('solicitudes-cotizacion.destroy', props.solicitud.id), {
        onSuccess: () => mostrarModalEliminar.value = false
    })
}

// --- Helpers Visuales ---
function getEstadoConfig(estado) {
    const configs = {
        'Pendiente de Revisión': { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', label: 'Borrador' },
        'Abierta': { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', label: 'Abierta' },
        'Enviada': { bg: 'bg-indigo-50', text: 'text-indigo-700', border: 'border-indigo-200', label: 'Enviada' },
        'Cerrada': { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', label: 'Cerrada' },
        'Vencida': { bg: 'bg-gray-50', text: 'text-gray-500', border: 'border-gray-200', label: 'Vencida' },
        'Cancelada': { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-200', label: 'Cancelada' },
    }
    return configs[estado] || { bg: 'bg-gray-50', text: 'text-gray-600', border: 'border-gray-200', label: estado }
}

function getEstadoEnvioConfig(estado) {
    const configs = {
        'Pendiente': { bg: 'bg-gray-100', text: 'text-gray-600', icon: 'clock' },
        'Enviado': { bg: 'bg-blue-100', text: 'text-blue-700', icon: 'paper-airplane' },
        'Entregado': { bg: 'bg-emerald-100', text: 'text-emerald-700', icon: 'check' },
        'Leído': { bg: 'bg-emerald-100', text: 'text-emerald-800', icon: 'eye' },
        'Fallido': { bg: 'bg-red-100', text: 'text-red-700', icon: 'x-circle' },
    }
    return configs[estado] || configs['Pendiente']
}

function formatDate(date) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    })
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value || 0)
}
</script>

<template>
    <AppLayout :title="`Solicitud ${solicitud.codigo_solicitud}`">
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('solicitudes-cotizacion.index')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="font-bold text-xl text-gray-900">{{ solicitud.codigo_solicitud }}</h2>
                            <span 
                                class="px-2.5 py-1 rounded-full text-xs font-semibold border"
                                :class="[getEstadoConfig(solicitud.estado?.nombre).bg, getEstadoConfig(solicitud.estado?.nombre).text, getEstadoConfig(solicitud.estado?.nombre).border]"
                            >
                                {{ getEstadoConfig(solicitud.estado?.nombre).label }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">Creada el {{ formatDate(solicitud.created_at) }}</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Alerta: Proveedores pendientes de envío -->
                <div v-if="puedeEnviar()" class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-amber-100 rounded-lg">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-amber-800">
                                {{ esBorrador() ? 'Solicitud en Borrador' : 'Proveedores Pendientes de Envío' }}
                            </h3>
                            <p class="text-sm text-amber-700 mt-0.5">
                                {{ esBorrador() 
                                    ? 'Revise los productos y cantidades antes de enviar a los proveedores.' 
                                    : 'Hay proveedores que aún no han recibido la solicitud de cotización.' 
                                }}
                            </p>
                        </div>
                    </div>
                    <button 
                        @click="mostrarModalEnvio = true"
                        class="flex items-center gap-2 px-4 py-2.5 bg-amber-600 text-white font-semibold text-sm rounded-lg hover:bg-amber-700 transition-colors shadow-sm"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        {{ esBorrador() ? 'Aprobar y Enviar' : 'Enviar a Proveedores' }}
                    </button>
                </div>

                <!-- Resultado: Solicitud Cerrada con Ganador -->
                <div v-if="estaCerrada() && cotizacionGanadora" class="mb-6 bg-emerald-50 border border-emerald-200 rounded-xl p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-emerald-100 rounded-xl">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-emerald-600">Proveedor Seleccionado</p>
                                <p class="text-xl font-bold text-gray-900">{{ cotizacionGanadora.proveedor?.razon_social }}</p>
                            </div>
                        </div>
                        <div v-if="ordenCompra" class="text-right">
                            <p class="text-sm text-gray-500">Orden de Compra</p>
                            <Link :href="route('ordenes.show', ordenCompra.id)" class="text-lg font-bold text-indigo-600 hover:text-indigo-800">
                                {{ ordenCompra.numero_oc }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Layout Principal: 2 columnas -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    
                    <!-- Panel Izquierdo: Contexto (2/5) -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Información General -->
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 bg-gray-50 border-b border-gray-100">
                                <h3 class="font-semibold text-gray-900">Información</h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-500">Vencimiento</span>
                                    <span class="text-sm font-medium" :class="new Date(solicitud.fecha_vencimiento) < new Date() ? 'text-red-600' : 'text-gray-900'">
                                        {{ formatDate(solicitud.fecha_vencimiento) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-sm text-gray-500">Creada por</span>
                                    <span class="text-sm font-medium text-gray-900">{{ solicitud.usuario?.name || 'Sistema' }}</span>
                                </div>
                                <div v-if="solicitud.observaciones" class="pt-2">
                                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Observaciones</p>
                                    <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3">{{ solicitud.observaciones }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Productos Solicitados -->
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900">Productos a Cotizar</h3>
                                <span class="text-xs text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full">
                                    {{ solicitud.detalles?.length || 0 }} items
                                </span>
                            </div>
                            <ul class="divide-y divide-gray-100">
                                <li v-for="detalle in solicitud.detalles" :key="detalle.id" class="p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center gap-4">
                                        <div class="h-12 w-12 flex-shrink-0 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-600 font-bold text-sm">
                                            {{ detalle.producto?.codigo?.substring(0, 3) || '?' }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ detalle.producto?.nombre }}</p>
                                            <p class="text-xs text-gray-500">{{ detalle.producto?.categoria?.nombre || 'Sin categoría' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-bold bg-gray-100 text-gray-800">
                                                {{ detalle.cantidad_sugerida }}
                                            </span>
                                            <p class="text-xs text-gray-500 mt-0.5">unidades</p>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- Acciones de Gestión -->
                        <div v-if="!estaFinalizada()" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Gestión</h4>
                            <div class="space-y-2">
                                <button
                                    v-if="solicitud.estado?.nombre === 'Enviada'"
                                    @click="cerrarSolicitud"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Finalizar Espera
                                </button>
                                <button
                                    @click="cancelarSolicitud"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 border border-red-200 rounded-lg text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Cancelar Solicitud
                                </button>
                            </div>
                        </div>

                        <!-- Eliminar (solo estados finales) -->
                        <div v-if="puedeEliminar()" class="bg-white rounded-xl border border-dashed border-gray-200 p-4">
                            <button
                                @click="mostrarModalEliminar = true"
                                class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium text-gray-400 hover:text-red-500 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Eliminar Solicitud
                            </button>
                        </div>
                    </div>

                    <!-- Panel Derecho: Estado de Proveedores (3/5) -->
                    <div class="lg:col-span-3 space-y-6">
                        
                        <!-- Barra de Progreso -->
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-semibold text-gray-900">Progreso de Respuestas</h3>
                                <span class="text-sm font-medium text-gray-500">
                                    {{ progreso.respondidos }} de {{ progreso.total }} proveedores
                                </span>
                            </div>
                            <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                                <div 
                                    class="h-full rounded-full transition-all duration-700"
                                    :class="progreso.porcentaje === 100 ? 'bg-emerald-500' : 'bg-indigo-500'"
                                    :style="{ width: `${progreso.porcentaje}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Lista de Proveedores -->
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900">Proveedores Invitados</h3>
                                <span v-if="tieneRespuestas && !estaFinalizada()">
                                    <Link 
                                        :href="route('solicitudes-cotizacion.comparar', solicitud.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                        </svg>
                                        Comparar Ofertas
                                    </Link>
                                </span>
                            </div>
                            
                            <ul class="divide-y divide-gray-100">
                                <li v-for="cotizacion in solicitud.cotizaciones_proveedores" :key="cotizacion.id" class="p-5 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4">
                                            <!-- Avatar Proveedor -->
                                            <div class="h-12 w-12 flex-shrink-0 bg-gray-100 rounded-xl flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ cotizacion.proveedor?.razon_social }}</p>
                                                <p class="text-sm text-gray-500">{{ cotizacion.proveedor?.whatsapp || cotizacion.proveedor?.email || 'Sin contacto' }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center gap-3">
                                            <!-- Estado de Envío -->
                                            <span 
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium"
                                                :class="[getEstadoEnvioConfig(cotizacion.estado_envio).bg, getEstadoEnvioConfig(cotizacion.estado_envio).text]"
                                            >
                                                <svg v-if="cotizacion.estado_envio === 'Enviado'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                                </svg>
                                                <svg v-else-if="cotizacion.estado_envio === 'Entregado' || cotizacion.estado_envio === 'Leído'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <svg v-else-if="cotizacion.estado_envio === 'Fallido'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ cotizacion.estado_envio }}
                                            </span>

                                            <!-- Estado de Respuesta -->
                                            <span 
                                                v-if="cotizacion.fecha_respuesta"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-700"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Respondió
                                            </span>
                                            <span 
                                                v-else-if="cotizacion.motivo_rechazo"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-red-100 text-red-700"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Rechazó
                                            </span>
                                            <span 
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-500"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Pendiente
                                            </span>

                                            <!-- Botón Reenviar -->
                                            <button 
                                                v-if="puedeReenviar(cotizacion)"
                                                @click="abrirModalReenvio(cotizacion)"
                                                class="p-1.5 text-gray-400 hover:text-orange-500 hover:bg-orange-50 rounded-lg transition-colors"
                                                title="Reenviar recordatorio"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Total Cotizado (si respondió) -->
                                    <div v-if="cotizacion.fecha_respuesta && cotizacion.total_estimado" class="mt-3 flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <span class="text-sm text-gray-600">Total Cotizado</span>
                                        <span class="text-lg font-bold text-gray-900">{{ formatCurrency(cotizacion.total_estimado) }}</span>
                                    </div>
                                </li>
                            </ul>

                            <!-- Empty State -->
                            <div v-if="!solicitud.cotizaciones_proveedores?.length" class="p-8 text-center">
                                <div class="mx-auto h-12 w-12 text-gray-300 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-500">No hay proveedores asignados</p>
                            </div>
                        </div>

                        <!-- CTA: Comparar Ofertas (prominente si hay respuestas) -->
                        <div v-if="tieneRespuestas && !estaFinalizada()" class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl p-6 text-white shadow-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold">¡Hay ofertas para revisar!</h3>
                                    <p class="text-emerald-100 text-sm mt-1">
                                        {{ progreso.respondidos }} proveedor(es) han enviado su cotización
                                    </p>
                                </div>
                                <Link 
                                    :href="route('solicitudes-cotizacion.comparar', solicitud.id)"
                                    class="flex items-center gap-2 px-5 py-3 bg-white text-emerald-600 font-bold rounded-lg hover:bg-emerald-50 transition-colors shadow-sm"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                    </svg>
                                    Comparar y Elegir
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Aprobar y Enviar -->
        <Modal :show="mostrarModalEnvio" @close="mostrarModalEnvio = false">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-indigo-100 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirmar Envío</h3>
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Se enviará un mensaje a <strong>{{ solicitud.cotizaciones_proveedores?.length || 0 }} proveedores</strong> con un enlace único para que carguen sus precios.
                </p>
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-6">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" v-model="formEnvio.canal" true-value="inteligente" false-value="whatsapp" class="mt-0.5 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <div>
                            <span class="font-medium text-blue-800">Envío inteligente</span>
                            <p class="text-xs text-blue-600 mt-0.5">WhatsApp preferido, Email como respaldo</p>
                        </div>
                    </label>
                </div>
                <div class="flex justify-end gap-3">
                    <button @click="mostrarModalEnvio = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button 
                        @click="aprobarYEnviar" 
                        :disabled="formEnvio.processing" 
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ formEnvio.processing ? 'Enviando...' : 'Confirmar y Enviar' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Modal: Reenviar Recordatorio -->
        <Modal :show="mostrarModalReenvio" @close="mostrarModalReenvio = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Reenviar Recordatorio</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Enviar recordatorio a <strong>{{ cotizacionReenviar?.proveedor?.razon_social }}</strong>
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="mostrarModalReenvio = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button @click="reenviarRecordatorio" :disabled="formReenvio.processing" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 disabled:opacity-50">
                        {{ formReenvio.processing ? 'Enviando...' : 'Reenviar' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Modal: Eliminar -->
        <Modal :show="mostrarModalEliminar" @close="mostrarModalEliminar = false">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">¿Eliminar definitivamente?</h3>
                <p class="text-center text-sm text-gray-500 mb-6">
                    Esta acción borrará la solicitud <strong>{{ solicitud.codigo_solicitud }}</strong> y todos sus datos.
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="mostrarModalEliminar = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button @click="eliminarSolicitud" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
