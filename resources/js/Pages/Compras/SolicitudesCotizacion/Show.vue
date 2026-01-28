<script setup>
/**
 * Vista: Detalle de Solicitud de Cotización (CU-20)
 */
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    solicitud: Object,
    ranking: Array,
    puedeEnviar: Boolean,
})

const mostrarModalEnvio = ref(false)
const mostrarModalReenvio = ref(false)
const cotizacionReenviar = ref(null)
const formEnvio = useForm({ canal: 'whatsapp' })
const formReenvio = useForm({ canal: 'whatsapp' })

function enviarASProveedores() {
    formEnvio.post(route('solicitudes-cotizacion.enviar', props.solicitud.id), {
        onSuccess: () => {
            mostrarModalEnvio.value = false
        }
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

function puedeReenviar(cotizacion) {
    // Puede reenviar si: está enviado, no ha respondido, no ha rechazado, solicitud no vencida
    return cotizacion.estado_envio === 'Enviado' 
        && !cotizacion.fecha_respuesta 
        && !cotizacion.motivo_rechazo
        && new Date(props.solicitud.fecha_vencimiento) > new Date()
}

function cerrarSolicitud() {
    if (confirm('¿Está seguro de cerrar esta solicitud?')) {
        useForm({}).post(route('solicitudes-cotizacion.cerrar', props.solicitud.id))
    }
}

function cancelarSolicitud() {
    if (confirm('¿Está seguro de cancelar esta solicitud?')) {
        useForm({}).post(route('solicitudes-cotizacion.cancelar', props.solicitud.id))
    }
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

function getEstadoEnvioClass(estado) {
    const clases = {
        'Pendiente': 'bg-gray-100 text-gray-600',
        'Enviado': 'bg-blue-100 text-blue-600',
        'Fallido': 'bg-red-100 text-red-600',
    }
    return clases[estado] || 'bg-gray-100 text-gray-600'
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS'
    }).format(value)
}
</script>

<template>
    <AppLayout :title="`Solicitud ${solicitud.codigo_solicitud}`">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Header con navegación y acciones principales -->
                <div class="mb-6 flex justify-between items-center">
                    <Link :href="route('solicitudes-cotizacion.index')" class="text-gray-600 hover:text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Volver
                    </Link>
                    <div class="flex items-center gap-2">
                        <span
                            :class="getEstadoClass(solicitud.estado?.nombre)"
                            class="px-3 py-1 text-sm font-semibold rounded-full"
                        >
                            {{ solicitud.estado?.nombre }}
                        </span>
                    </div>
                </div>

                <!-- Contenedor principal en grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Columna izquierda: Información principal -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Información General -->
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">{{ solicitud.codigo_solicitud }}</h3>
                                <p class="text-sm text-gray-500 mt-1">Solicitud de Cotización</p>
                            </div>
                            <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Fecha Emisión</dt>
                                    <dd class="mt-1 text-md text-gray-900">{{ formatDate(solicitud.fecha_emision) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Fecha Vencimiento</dt>
                                    <dd class="mt-1 text-md" :class="new Date(solicitud.fecha_vencimiento) < new Date() ? 'text-red-600 font-semibold' : 'text-gray-900'">
                                        {{ formatDate(solicitud.fecha_vencimiento) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Creada por</dt>
                                    <dd class="mt-1 text-md text-gray-900">{{ solicitud.usuario?.name || 'Sistema (automático)' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Respuestas Recibidas</dt>
                                    <dd class="mt-1 text-md font-semibold">
                                        <span class="text-green-600">
                                            {{ solicitud.cotizaciones_proveedores?.filter(c => c.fecha_respuesta).length || 0 }}
                                        </span>
                                        <span class="text-gray-500"> / {{ solicitud.cotizaciones_proveedores?.length || 0 }}</span>
                                    </dd>
                                </div>
                                <div v-if="solicitud.observaciones" class="col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Observaciones</dt>
                                    <dd class="mt-1 text-md text-gray-900 bg-gray-50 p-3 rounded">{{ solicitud.observaciones }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Productos Solicitados -->
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-sm font-bold text-gray-700 uppercase">Productos Solicitados</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="detalle in solicitud.detalles" :key="detalle.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <p class="font-medium text-gray-900">{{ detalle.producto?.nombre }}</p>
                                                <p class="text-sm text-gray-500 font-mono">{{ detalle.producto?.codigo }}</p>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                {{ detalle.producto?.categoria?.nombre || '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="font-bold text-gray-900">{{ detalle.cantidad_sugerida }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                {{ detalle.observaciones || '-' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Proveedores Invitados -->
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-sm font-bold text-gray-700 uppercase">Proveedores Invitados</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proveedor</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Enviado</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Respuesta</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="cotizacion in solicitud.cotizaciones_proveedores" :key="cotizacion.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <p class="font-medium text-gray-900">{{ cotizacion.proveedor?.razon_social }}</p>
                                                <p class="text-sm text-gray-500">{{ cotizacion.proveedor?.email || cotizacion.proveedor?.whatsapp }}</p>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span :class="getEstadoEnvioClass(cotizacion.estado_envio)" class="px-2 py-1 text-xs font-medium rounded-full">
                                                    {{ cotizacion.estado_envio }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center text-sm text-gray-600">
                                                {{ cotizacion.fecha_envio ? formatDate(cotizacion.fecha_envio) : '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-center text-sm">
                                                <span v-if="cotizacion.fecha_respuesta" class="text-green-600 font-medium">
                                                    ✓ {{ formatDate(cotizacion.fecha_respuesta) }}
                                                </span>
                                                <span v-else-if="cotizacion.motivo_rechazo" class="text-red-600 font-medium">
                                                    ✗ Rechazó
                                                </span>
                                                <span v-else class="text-gray-400">
                                                    Pendiente
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a
                                                        v-if="cotizacion.token_unico"
                                                        :href="route('portal.cotizacion', cotizacion.token_unico)"
                                                        target="_blank"
                                                        class="text-indigo-600 hover:text-indigo-800 text-xs font-medium underline"
                                                    >
                                                        Portal
                                                    </a>
                                                    <button
                                                        v-if="puedeReenviar(cotizacion)"
                                                        @click="abrirModalReenvio(cotizacion)"
                                                        class="text-orange-600 hover:text-orange-800 text-xs font-medium"
                                                        title="Enviar recordatorio"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha: Acciones y estadísticas -->
                    <div class="space-y-6">
                        
                        <!-- Acciones disponibles -->
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-sm font-bold text-gray-700 uppercase">Acciones</h3>
                            </div>
                            <div class="p-6 space-y-3">
                                <button
                                    v-if="puedeEnviar"
                                    @click="mostrarModalEnvio = true"
                                    class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm flex items-center justify-center"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    Enviar a Proveedores
                                </button>
                                <button
                                    v-if="solicitud.estado?.nombre === 'Enviada'"
                                    @click="cerrarSolicitud"
                                    class="w-full px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium text-sm"
                                >
                                    Cerrar Solicitud
                                </button>
                                <button
                                    v-if="['Abierta', 'Enviada'].includes(solicitud.estado?.nombre)"
                                    @click="cancelarSolicitud"
                                    class="w-full px-4 py-3 border-2 border-red-300 text-red-600 rounded-lg hover:bg-red-50 font-medium text-sm"
                                >
                                    Cancelar Solicitud
                                </button>
                            </div>
                        </div>

                        <!-- Ranking de ofertas (lateral) -->
                        <div v-if="ranking.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-green-200 bg-green-50">
                                <h3 class="text-sm font-bold text-green-800 uppercase flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                                    Ranking de Respuestas
                                </h3>
                                <p class="text-xs text-green-600 mt-1">
                                    {{ ranking.length }} {{ ranking.length === 1 ? 'proveedor respondió' : 'proveedores respondieron' }}
                                </p>
                            </div>
                            <div class="p-4 space-y-3 max-h-[600px] overflow-y-auto">
                                <div
                                    v-for="(oferta, index) in ranking"
                                    :key="oferta.cotizacion_id"
                                    class="border rounded-lg p-3"
                                    :class="index === 0 ? 'border-green-400 bg-green-50' : 'border-gray-200'"
                                >
                                    <div class="flex items-start gap-2">
                                        <span
                                            class="w-6 h-6 rounded-full flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                                            :class="index === 0 ? 'bg-green-500' : index === 1 ? 'bg-gray-400' : 'bg-gray-300'"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-sm text-gray-900 truncate">{{ oferta.proveedor?.razon_social }}</p>
                                            <p class="text-lg font-bold text-gray-900 mt-1">{{ formatCurrency(oferta.total) }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ oferta.productos_cotizados }}/{{ oferta.productos_requeridos }} productos
                                                <span v-if="oferta.cotizo_completo" class="text-green-600 ml-1">✓</span>
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">
                                                {{ formatDate(oferta.fecha_respuesta) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mensaje cuando no hay respuestas -->
                        <div v-else class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-sm font-bold text-gray-800 uppercase flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    Respuestas
                                </h3>
                            </div>
                            <div class="p-6 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="mt-2 text-sm">Sin respuestas aún</p>
                                <p class="text-xs mt-1">Las respuestas aparecerán aquí cuando los proveedores coticen</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ranking completo (debajo, si hay más de 3) -->
                <div v-if="ranking.length > 3" id="ranking-completo" class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-green-50">
                        <h3 class="font-semibold text-green-800 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                            Ranking Completo de Ofertas
                        </h3>
                        <p class="text-sm text-green-600">Ordenado por menor precio total</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div
                            v-for="(oferta, index) in ranking"
                            :key="oferta.cotizacion_id"
                            class="border rounded-lg p-4"
                            :class="index === 0 ? 'border-green-500 bg-green-50' : 'border-gray-200'"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold"
                                        :class="index === 0 ? 'bg-green-500' : index === 1 ? 'bg-gray-400' : 'bg-gray-300'"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ oferta.proveedor?.razon_social }}</p>
                                        <p class="text-sm text-gray-500">
                                            Respondió: {{ formatDate(oferta.fecha_respuesta) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold" :class="index === 0 ? 'text-green-600' : 'text-gray-700'">
                                        {{ formatCurrency(oferta.total) }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ oferta.productos_cotizados }}/{{ oferta.productos_requeridos }} productos
                                        <span v-if="oferta.cotizo_completo" class="text-green-600">✓ Completo</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Detalle de productos cotizados -->
                            <div class="mt-3 border-t pt-3">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-gray-500">
                                            <th class="text-left py-1">Producto</th>
                                            <th class="text-center py-1">Precio Unit.</th>
                                            <th class="text-center py-1">Cantidad</th>
                                            <th class="text-center py-1">Plazo</th>
                                            <th class="text-right py-1">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="resp in oferta.respuestas" :key="resp.id">
                                            <td class="py-1">{{ resp.producto?.nombre }}</td>
                                            <td class="py-1 text-center">{{ formatCurrency(resp.precio_unitario) }}</td>
                                            <td class="py-1 text-center">{{ resp.cantidad_disponible }}</td>
                                            <td class="py-1 text-center">{{ resp.plazo_entrega_dias }} días</td>
                                            <td class="py-1 text-right font-medium">
                                                {{ formatCurrency(resp.precio_unitario * resp.cantidad_disponible) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de envío -->
        <div v-if="mostrarModalEnvio" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="mostrarModalEnvio = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Enviar Solicitud a Proveedores
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Seleccione el canal de comunicación para enviar los Magic Links:
                    </p>
                    
                    <div class="space-y-3">
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="{ 'border-indigo-500 bg-indigo-50': formEnvio.canal === 'whatsapp' }">
                            <input type="radio" v-model="formEnvio.canal" value="whatsapp" class="text-indigo-600" />
                            <span class="ml-3">
                                <span class="font-medium flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    WhatsApp
                                </span>
                                <span class="text-sm text-gray-500 block">Envío inmediato por mensajería</span>
                            </span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="{ 'border-indigo-500 bg-indigo-50': formEnvio.canal === 'email' }">
                            <input type="radio" v-model="formEnvio.canal" value="email" class="text-indigo-600" />
                            <span class="ml-3">
                                <span class="font-medium flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    Email
                                </span>
                                <span class="text-sm text-gray-500 block">Correo electrónico formal</span>
                            </span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="{ 'border-indigo-500 bg-indigo-50': formEnvio.canal === 'ambos' }">
                            <input type="radio" v-model="formEnvio.canal" value="ambos" class="text-indigo-600" />
                            <span class="ml-3">
                                <span class="font-medium flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    Ambos canales
                                </span>
                                <span class="text-sm text-gray-500 block">WhatsApp y Email simultáneamente</span>
                            </span>
                        </label>
                    </div>

                    <div class="mt-6 flex gap-3 justify-end">
                        <button
                            type="button"
                            @click="mostrarModalEnvio = false"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="enviarASProveedores"
                            :disabled="formEnvio.processing"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
                        >
                            <span v-if="formEnvio.processing">Enviando...</span>
                            <span v-else>Enviar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de reenvío de recordatorio -->
        <div v-if="mostrarModalReenvio" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="mostrarModalReenvio = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        Enviar Recordatorio
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Enviar recordatorio a: <strong>{{ cotizacionReenviar?.proveedor?.razon_social }}</strong>
                    </p>
                    
                    <div class="space-y-3">
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="{ 'border-orange-500 bg-orange-50': formReenvio.canal === 'whatsapp' }">
                            <input type="radio" v-model="formReenvio.canal" value="whatsapp" class="text-orange-600" />
                            <span class="ml-3">
                                <span class="font-medium flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    WhatsApp
                                </span>
                            </span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="{ 'border-orange-500 bg-orange-50': formReenvio.canal === 'email' }">
                            <input type="radio" v-model="formReenvio.canal" value="email" class="text-orange-600" />
                            <span class="ml-3">
                                <span class="font-medium flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    Email
                                </span>
                            </span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="{ 'border-orange-500 bg-orange-50': formReenvio.canal === 'ambos' }">
                            <input type="radio" v-model="formReenvio.canal" value="ambos" class="text-orange-600" />
                            <span class="ml-3">
                                <span class="font-medium flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    Ambos
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="mt-6 flex gap-3 justify-end">
                        <button
                            type="button"
                            @click="mostrarModalReenvio = false"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="reenviarRecordatorio"
                            :disabled="formReenvio.processing"
                            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 disabled:opacity-50"
                        >
                            <span v-if="formReenvio.processing">Enviando...</span>
                            <span v-else>Enviar Recordatorio</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
