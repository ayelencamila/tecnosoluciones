<script setup>
/**
 * Vista: Detalle de Solicitud de Cotización (CU-20)
 */
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    solicitud: Object,
    ranking: Array,
    puedeEnviar: Boolean,
})

const mostrarModalEnvio = ref(false)
const formEnvio = useForm({ canal: 'whatsapp' })

function enviarASProveedores() {
    formEnvio.post(route('solicitudes-cotizacion.enviar', props.solicitud.id), {
        onSuccess: () => {
            mostrarModalEnvio.value = false
        }
    })
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
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ solicitud.codigo_solicitud }}
                    </h2>
                    <div class="flex items-center gap-3 mt-1">
                        <span
                            :class="getEstadoClass(solicitud.estado?.nombre)"
                            class="px-2 py-1 text-xs font-medium rounded-full"
                        >
                            {{ solicitud.estado?.nombre }}
                        </span>
                        <span class="text-sm text-gray-500">
                            Creada el {{ formatDate(solicitud.fecha_emision) }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button
                        v-if="puedeEnviar"
                        @click="mostrarModalEnvio = true"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Enviar a Proveedores
                    </button>
                    <button
                        v-if="solicitud.estado?.nombre === 'Enviada'"
                        @click="cerrarSolicitud"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                    >
                        Cerrar Solicitud
                    </button>
                    <button
                        v-if="['Abierta', 'Enviada'].includes(solicitud.estado?.nombre)"
                        @click="cancelarSolicitud"
                        class="inline-flex items-center px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50"
                    >
                        Cancelar
                    </button>
                    <Link
                        :href="route('solicitudes-cotizacion.index')"
                        class="text-gray-600 hover:text-gray-800"
                    >
                        ← Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Info general -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Fecha Emisión</p>
                            <p class="font-medium">{{ formatDate(solicitud.fecha_emision) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Fecha Vencimiento</p>
                            <p class="font-medium" :class="new Date(solicitud.fecha_vencimiento) < new Date() ? 'text-red-600' : ''">
                                {{ formatDate(solicitud.fecha_vencimiento) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Creada por</p>
                            <p class="font-medium">{{ solicitud.usuario?.name || 'Sistema (automático)' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Respuestas</p>
                            <p class="font-medium">
                                <span class="text-green-600">
                                    {{ solicitud.cotizaciones_proveedores?.filter(c => c.fecha_respuesta).length || 0 }}
                                </span>
                                de {{ solicitud.cotizaciones_proveedores?.length || 0 }} proveedores
                            </p>
                        </div>
                    </div>
                    <div v-if="solicitud.observaciones" class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Observaciones:</span> {{ solicitud.observaciones }}
                        </p>
                    </div>
                </div>

                <!-- Productos solicitados -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-900">Productos Solicitados</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="detalle in solicitud.detalles" :key="detalle.id">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900">{{ detalle.producto?.nombre }}</p>
                                    <p class="text-sm text-gray-500">{{ detalle.producto?.codigo }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ detalle.producto?.categoria?.nombre || '-' }}
                                </td>
                                <td class="px-6 py-4 text-center font-medium">
                                    {{ detalle.cantidad_sugerida }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ detalle.observaciones || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Proveedores invitados -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-gray-50">
                        <h3 class="font-semibold text-gray-900">Proveedores Invitados</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado Envío</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Fecha Envío</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Respuesta</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="cotizacion in solicitud.cotizaciones_proveedores" :key="cotizacion.id">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900">{{ cotizacion.proveedor?.razon_social }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ cotizacion.proveedor?.email || cotizacion.proveedor?.whatsapp }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        :class="getEstadoEnvioClass(cotizacion.estado_envio)"
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                    >
                                        {{ cotizacion.estado_envio }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-500">
                                    {{ cotizacion.fecha_envio ? formatDate(cotizacion.fecha_envio) : '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="cotizacion.fecha_respuesta" class="text-green-600">
                                        {{ formatDate(cotizacion.fecha_respuesta) }}
                                    </span>
                                    <span v-else-if="cotizacion.motivo_rechazo" class="text-red-600">
                                        Rechazó
                                    </span>
                                    <span v-else class="text-gray-400">
                                        Pendiente
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a
                                        v-if="cotizacion.token_unico"
                                        :href="route('portal.cotizacion', cotizacion.token_unico)"
                                        target="_blank"
                                        class="text-indigo-600 hover:text-indigo-800 text-sm"
                                    >
                                        Ver Portal
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Ranking de ofertas -->
                <div v-if="ranking.length > 0" class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-green-50">
                        <h3 class="font-semibold text-green-800">🏆 Ranking de Ofertas</h3>
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
                                <span class="font-medium">📱 WhatsApp</span>
                                <span class="text-sm text-gray-500 block">Envío inmediato por mensajería</span>
                            </span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="{ 'border-indigo-500 bg-indigo-50': formEnvio.canal === 'email' }">
                            <input type="radio" v-model="formEnvio.canal" value="email" class="text-indigo-600" />
                            <span class="ml-3">
                                <span class="font-medium">📧 Email</span>
                                <span class="text-sm text-gray-500 block">Correo electrónico formal</span>
                            </span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                               :class="{ 'border-indigo-500 bg-indigo-50': formEnvio.canal === 'ambos' }">
                            <input type="radio" v-model="formEnvio.canal" value="ambos" class="text-indigo-600" />
                            <span class="ml-3">
                                <span class="font-medium">📱📧 Ambos canales</span>
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
    </AppLayout>
</template>
