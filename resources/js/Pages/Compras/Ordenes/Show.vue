<script setup>
/**
 * Vista: Orden de Compra - Estilo Documento Formal (CU-22)
 * Diseño: Simula una hoja de papel con acciones de gestión
 * 
 * Flujo: Index → Show → Comparar → Ordenes/Show
 */
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    orden: Object,
})

// --- Estado UI ---
const showCancelarModal = ref(false)
const procesando = ref({
    whatsapp: false,
    email: false,
    pdf: false,
    confirmar: false,
})

// --- Formulario ---
const formCancelar = useForm({
    motivo: '',
})

// --- Computed ---
const timeline = computed(() => {
    const estados = []
    const estadoActual = props.orden.estado?.nombre
    
    // Definir pasos del timeline
    const pasos = [
        { key: 'Borrador', label: 'Generada', icon: 'document' },
        { key: 'Enviada', label: 'Enviada', icon: 'paper-airplane' },
        { key: 'Confirmada', label: 'Confirmada', icon: 'check' },
        { key: 'Recibida Total', label: 'Recibida', icon: 'truck' },
    ]
    
    let encontrado = false
    pasos.forEach(paso => {
        const completado = !encontrado && (
            estadoActual === paso.key || 
            (paso.key === 'Borrador' && ['Enviada', 'Confirmada', 'Recibida Parcial', 'Recibida Total'].includes(estadoActual)) ||
            (paso.key === 'Enviada' && ['Confirmada', 'Recibida Parcial', 'Recibida Total'].includes(estadoActual)) ||
            (paso.key === 'Confirmada' && ['Recibida Parcial', 'Recibida Total'].includes(estadoActual))
        )
        
        if (estadoActual === paso.key) encontrado = true
        
        estados.push({
            ...paso,
            completado,
            actual: estadoActual === paso.key || (paso.key === 'Confirmada' && estadoActual === 'Recibida Parcial')
        })
    })
    
    return estados
})

const estaCancelada = computed(() => props.orden.estado?.nombre === 'Cancelada')

// --- Acciones ---
function reenviarWhatsApp() {
    if (confirm('¿Reenviar la orden por WhatsApp al proveedor?')) {
        procesando.value.whatsapp = true
        router.post(route('ordenes.reenviar-whatsapp', props.orden.id), {}, {
            preserveScroll: true,
            onFinish: () => procesando.value.whatsapp = false,
        })
    }
}

function reenviarEmail() {
    if (confirm('¿Reenviar la orden por Email al proveedor?')) {
        procesando.value.email = true
        router.post(route('ordenes.reenviar-email', props.orden.id), {}, {
            preserveScroll: true,
            onFinish: () => procesando.value.email = false,
        })
    }
}

function regenerarPdf() {
    if (confirm('¿Regenerar el PDF de esta orden?')) {
        procesando.value.pdf = true
        router.post(route('ordenes.regenerar-pdf', props.orden.id), {}, {
            preserveScroll: true,
            onFinish: () => procesando.value.pdf = false,
        })
    }
}

function confirmarOrden() {
    if (confirm('¿Marcar esta orden como confirmada por el proveedor?')) {
        procesando.value.confirmar = true
        router.post(route('ordenes.confirmar', props.orden.id), {}, {
            preserveScroll: true,
            onFinish: () => procesando.value.confirmar = false,
        })
    }
}

function cancelarOrden() {
    formCancelar.post(route('ordenes.cancelar', props.orden.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCancelarModal.value = false
            formCancelar.reset()
        },
    })
}

// --- Permisos ---
function puedeReenviar() {
    return ['Borrador', 'Enviada', 'Envío Fallido'].includes(props.orden.estado?.nombre)
}

function puedeConfirmar() {
    return props.orden.estado?.nombre === 'Enviada'
}

function puedeCancelar() {
    return !['Cancelada', 'Recibida Parcial', 'Recibida Total'].includes(props.orden.estado?.nombre)
}

function puedeRecepcionar() {
    return ['Confirmada', 'Recibida Parcial'].includes(props.orden.estado?.nombre)
}

// --- Helpers ---
function getEstadoConfig(estado) {
    const configs = {
        'Borrador': { bg: 'bg-gray-100', text: 'text-gray-700', border: 'border-gray-300' },
        'Enviada': { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-300' },
        'Envío Fallido': { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-300' },
        'Confirmada': { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-300' },
        'Recibida Parcial': { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-300' },
        'Recibida Total': { bg: 'bg-emerald-100', text: 'text-emerald-800', border: 'border-emerald-400' },
        'Cancelada': { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-300' },
    }
    return configs[estado] || configs['Borrador']
}

function formatFecha(fecha) {
    if (!fecha) return '-'
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    })
}

function formatFechaHora(fecha) {
    if (!fecha) return '-'
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    })
}

function formatMoneda(valor) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency', currency: 'ARS', minimumFractionDigits: 2
    }).format(valor || 0)
}
</script>

<template>
    <Head :title="`OC ${orden.numero_oc}`" />
    
    <AppLayout :title="`Orden de Compra ${orden.numero_oc}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('ordenes.index')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="font-bold text-xl text-gray-900">{{ orden.numero_oc }}</h2>
                            <span 
                                class="px-2.5 py-1 rounded-full text-xs font-semibold border"
                                :class="[getEstadoConfig(orden.estado?.nombre).bg, getEstadoConfig(orden.estado?.nombre).text, getEstadoConfig(orden.estado?.nombre).border]"
                            >
                                {{ orden.estado?.nombre }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">Emitida el {{ formatFecha(orden.fecha_emision) }}</p>
                    </div>
                </div>
                
                <!-- Acciones rápidas -->
                <div class="flex items-center gap-2">
                    <a 
                        v-if="orden.archivo_pdf"
                        :href="route('ordenes.descargar-pdf', orden.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Descargar PDF
                    </a>
                    <button 
                        v-if="puedeRecepcionar()"
                        @click="router.visit(route('recepciones.create', { orden: orden.id }))"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        Registrar Recepción
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-100 min-h-screen">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Timeline de Estados -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <template v-for="(paso, index) in timeline" :key="paso.key">
                            <!-- Paso -->
                            <div class="flex flex-col items-center">
                                <div 
                                    class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition-colors"
                                    :class="paso.completado 
                                        ? 'bg-emerald-500 border-emerald-500 text-white' 
                                        : paso.actual 
                                            ? 'bg-indigo-500 border-indigo-500 text-white' 
                                            : 'bg-white border-gray-300 text-gray-400'"
                                >
                                    <svg v-if="paso.icon === 'document'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <svg v-else-if="paso.icon === 'paper-airplane'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    <svg v-else-if="paso.icon === 'check'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <svg v-else-if="paso.icon === 'truck'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    </svg>
                                </div>
                                <span 
                                    class="mt-2 text-xs font-medium"
                                    :class="paso.completado || paso.actual ? 'text-gray-900' : 'text-gray-400'"
                                >
                                    {{ paso.label }}
                                </span>
                            </div>
                            <!-- Línea conectora -->
                            <div 
                                v-if="index < timeline.length - 1" 
                                class="flex-1 h-0.5 mx-2"
                                :class="timeline[index + 1].completado || timeline[index + 1].actual ? 'bg-emerald-500' : 'bg-gray-200'"
                            ></div>
                        </template>
                    </div>
                </div>

                <!-- Documento Principal -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-lg overflow-hidden">
                    
                    <!-- Encabezado del Documento -->
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-8 py-6 text-white">
                        <div class="flex justify-between items-start">
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight">ORDEN DE COMPRA</h1>
                                <p class="text-gray-300 mt-1">TecnoSoluciones - Servicio Técnico</p>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-bold font-mono">{{ orden.numero_oc }}</p>
                                <p class="text-gray-400 text-sm mt-1">Fecha: {{ formatFecha(orden.fecha_emision) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Proveedor -->
                    <div class="px-8 py-6 border-b border-gray-200 bg-gray-50">
                        <div class="grid grid-cols-2 gap-8">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-2">Proveedor</p>
                                <p class="text-lg font-bold text-gray-900">{{ orden.proveedor?.razon_social }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ orden.proveedor?.direccion || 'Sin dirección' }}</p>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                                    <span v-if="orden.proveedor?.whatsapp" class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ orden.proveedor?.whatsapp }}
                                    </span>
                                    <span v-if="orden.proveedor?.email" class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        {{ orden.proveedor?.email }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-2">Referencia</p>
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Solicitud:</span> 
                                    <Link 
                                        v-if="orden.cotizacion_proveedor?.solicitud" 
                                        :href="route('solicitudes-cotizacion.show', orden.cotizacion_proveedor.solicitud.id)"
                                        class="text-indigo-600 hover:text-indigo-800"
                                    >
                                        {{ orden.cotizacion_proveedor?.solicitud?.codigo_solicitud }}
                                    </Link>
                                    <span v-else class="text-gray-400">-</span>
                                </p>
                                <p class="text-sm text-gray-700 mt-1">
                                    <span class="font-medium">Generada por:</span> {{ orden.usuario?.name || 'Sistema' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Productos -->
                    <div class="px-8 py-6">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-gray-200">
                                    <th class="pb-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="pb-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Cantidad</th>
                                    <th class="pb-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Precio Unit.</th>
                                    <th class="pb-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="detalle in orden.detalles" :key="detalle.id" class="hover:bg-gray-50">
                                    <td class="py-4">
                                        <p class="font-medium text-gray-900">{{ detalle.producto?.nombre }}</p>
                                        <p class="text-xs text-gray-500">{{ detalle.producto?.codigo }}</p>
                                    </td>
                                    <td class="py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-semibold bg-gray-100 text-gray-800">
                                            {{ detalle.cantidad_pedida }}
                                        </span>
                                        <p v-if="detalle.cantidad_recibida > 0" class="text-xs text-emerald-600 mt-1">
                                            {{ detalle.cantidad_recibida }} recibidos
                                        </p>
                                    </td>
                                    <td class="py-4 text-right text-gray-700">
                                        {{ formatMoneda(detalle.precio_unitario) }}
                                    </td>
                                    <td class="py-4 text-right font-medium text-gray-900">
                                        {{ formatMoneda(detalle.cantidad_pedida * detalle.precio_unitario) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-200">
                                    <td colspan="3" class="pt-4 text-right font-bold text-gray-900 text-lg">TOTAL</td>
                                    <td class="pt-4 text-right font-bold text-gray-900 text-xl">
                                        {{ formatMoneda(orden.total_final) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Observaciones -->
                    <div v-if="orden.observaciones" class="px-8 py-4 bg-gray-50 border-t border-gray-200">
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1">Observaciones</p>
                        <p class="text-sm text-gray-700">{{ orden.observaciones }}</p>
                    </div>

                    <!-- Pie del Documento -->
                    <div class="px-8 py-4 bg-gray-100 border-t border-gray-200 text-center text-xs text-gray-500">
                        <p>Documento generado electrónicamente - {{ formatFechaHora(orden.created_at) }}</p>
                    </div>
                </div>

                <!-- Panel de Acciones -->
                <div class="mt-6 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Acciones</h3>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <!-- Reenviar WhatsApp -->
                        <button 
                            v-if="puedeReenviar()"
                            @click="reenviarWhatsApp"
                            :disabled="procesando.whatsapp"
                            class="flex flex-col items-center gap-2 p-4 border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-colors disabled:opacity-50"
                        >
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700">WhatsApp</span>
                        </button>

                        <!-- Reenviar Email -->
                        <button 
                            v-if="puedeReenviar()"
                            @click="reenviarEmail"
                            :disabled="procesando.email"
                            class="flex flex-col items-center gap-2 p-4 border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-colors disabled:opacity-50"
                        >
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700">Email</span>
                        </button>

                        <!-- Regenerar PDF -->
                        <button 
                            @click="regenerarPdf"
                            :disabled="procesando.pdf"
                            class="flex flex-col items-center gap-2 p-4 border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-colors disabled:opacity-50"
                        >
                            <div class="p-2 bg-red-100 rounded-lg">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700">Regenerar PDF</span>
                        </button>

                        <!-- Confirmar -->
                        <button 
                            v-if="puedeConfirmar()"
                            @click="confirmarOrden"
                            :disabled="procesando.confirmar"
                            class="flex flex-col items-center gap-2 p-4 border border-emerald-200 bg-emerald-50 rounded-xl hover:bg-emerald-100 transition-colors disabled:opacity-50"
                        >
                            <div class="p-2 bg-emerald-200 rounded-lg">
                                <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-emerald-700">Confirmar</span>
                        </button>

                        <!-- Cancelar -->
                        <button 
                            v-if="puedeCancelar()"
                            @click="showCancelarModal = true"
                            class="flex flex-col items-center gap-2 p-4 border border-gray-200 rounded-xl hover:bg-red-50 hover:border-red-200 transition-colors"
                        >
                            <div class="p-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-500">Cancelar</span>
                        </button>
                    </div>
                </div>

                <!-- Alerta: Orden Cancelada -->
                <div v-if="estaCancelada" class="mt-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-red-800">Orden Cancelada</h4>
                        <p class="text-sm text-red-700 mt-0.5">{{ orden.motivo_cancelacion || 'Sin motivo especificado' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Cancelar Orden -->
        <Modal :show="showCancelarModal" @close="showCancelarModal = false">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Cancelar Orden de Compra</h3>
                </div>
                
                <p class="text-sm text-gray-600 mb-4">
                    Esta acción cancelará la orden <strong>{{ orden.numero_oc }}</strong>. Por favor, indique el motivo.
                </p>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo de cancelación</label>
                    <textarea
                        v-model="formCancelar.motivo"
                        rows="3"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Escriba el motivo..."
                    ></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button 
                        @click="showCancelarModal = false" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Volver
                    </button>
                    <button 
                        @click="cancelarOrden" 
                        :disabled="formCancelar.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
                    >
                        {{ formCancelar.processing ? 'Cancelando...' : 'Confirmar Cancelación' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
