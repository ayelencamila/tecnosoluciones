<script setup>
/**
 * Vista: Arena de Comparación de Ofertas (CU-21)
 * Diseño: Tarjetas interactivas con ranking automático
 * 
 * Flujo: Index → Show → Comparar → Ordenes/Show
 */
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    solicitud: Object,
    cotizaciones: Array, // Cotizaciones con respuestas (ordenadas por total)
    productos: Array,    // Lista de productos solicitados
})

// --- Estado UI ---
const cotizacionSeleccionada = ref(null)
const mostrarDetalles = ref({}) // { cotizacionId: boolean }
const mostrarModalElegir = ref(false)
const mostrarTablaComparativa = ref(false)

// --- Formulario ---
const formElegir = useForm({
    generar_orden: true
})

// --- Computed ---
const cotizacionesOrdenadas = computed(() => {
    return [...(props.cotizaciones || [])].sort((a, b) => (a.total || 0) - (b.total || 0))
})

const mejorPrecio = computed(() => {
    if (!cotizacionesOrdenadas.value.length) return null
    return cotizacionesOrdenadas.value[0]
})

const diferenciaMaxima = computed(() => {
    if (cotizacionesOrdenadas.value.length < 2) return 0
    const min = cotizacionesOrdenadas.value[0]?.total || 0
    const max = cotizacionesOrdenadas.value[cotizacionesOrdenadas.value.length - 1]?.total || 0
    return max - min
})

// --- Métodos ---
function toggleDetalles(cotizacionId) {
    mostrarDetalles.value[cotizacionId] = !mostrarDetalles.value[cotizacionId]
}

function abrirModalElegir(cotizacion) {
    cotizacionSeleccionada.value = cotizacion
    mostrarModalElegir.value = true
}

function elegirGanador() {
    formElegir.post(route('solicitudes-cotizacion.elegir-cotizacion', [props.solicitud.id, cotizacionSeleccionada.value.id]), {
        onSuccess: () => {
            mostrarModalElegir.value = false
            cotizacionSeleccionada.value = null
        }
    })
}

function getPosicion(cotizacion) {
    return cotizacionesOrdenadas.value.findIndex(c => c.id === cotizacion.id) + 1
}

function esMejorPrecio(cotizacion) {
    return mejorPrecio.value?.id === cotizacion.id
}

function getPorcentajeDiferencia(cotizacion) {
    if (!mejorPrecio.value || !cotizacion.total) return 0
    const diff = cotizacion.total - mejorPrecio.value.total
    return mejorPrecio.value.total > 0 ? Math.round((diff / mejorPrecio.value.total) * 100) : 0
}

function getMejorPrecioProducto(productoId) {
    let mejor = null
    let mejorPrecio = Infinity
    
    cotizacionesOrdenadas.value.forEach(cot => {
        const respuesta = cot.respuestas?.find(r => r.producto_id === productoId)
        if (respuesta && respuesta.precio_unitario < mejorPrecio) {
            mejorPrecio = respuesta.precio_unitario
            mejor = cot.id
        }
    })
    
    return mejor
}

// --- Helpers Visuales ---
function formatCurrency(value) {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value || 0)
}

function formatDate(date) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function getInitials(name) {
    if (!name) return '?'
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}
</script>

<template>
    <AppLayout :title="`Comparar - ${solicitud.codigo_solicitud}`">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('solicitudes-cotizacion.show', solicitud.id)" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="font-bold text-xl text-gray-900">Comparar Ofertas</h2>
                        <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-semibold border border-indigo-200">
                            {{ solicitud.codigo_solicitud }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ cotizaciones?.length || 0 }} ofertas recibidas
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-100 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Info: Diferencia de precios -->
                <div v-if="diferenciaMaxima > 0" class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center gap-3">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-blue-800">
                        <strong>Diferencia de precios:</strong> {{ formatCurrency(diferenciaMaxima) }} entre la oferta más económica y la más cara.
                    </p>
                </div>

                <!-- Grid de Tarjetas -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div 
                        v-for="cotizacion in cotizacionesOrdenadas" 
                        :key="cotizacion.id"
                        class="relative bg-white rounded-2xl border-2 shadow-sm overflow-hidden transition-all duration-300 hover:shadow-lg"
                        :class="esMejorPrecio(cotizacion) ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-gray-200 hover:border-gray-300'"
                    >
                        <!-- Badge: Mejor Precio -->
                        <div v-if="esMejorPrecio(cotizacion)" class="absolute top-0 left-0 right-0">
                            <div class="bg-emerald-500 text-white text-xs font-bold py-1.5 px-3 flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                MEJOR PRECIO
                            </div>
                        </div>

                        <!-- Contenido de la Tarjeta -->
                        <div :class="esMejorPrecio(cotizacion) ? 'pt-10' : 'pt-6'" class="px-6 pb-6">
                            
                            <!-- Encabezado: Proveedor -->
                            <div class="flex items-start gap-4 mb-5">
                                <!-- Avatar/Logo -->
                                <div class="h-14 w-14 flex-shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl flex items-center justify-center shadow-inner">
                                    <span class="text-lg font-bold text-gray-500">{{ getInitials(cotizacion.proveedor?.razon_social) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-gray-900 truncate">{{ cotizacion.proveedor?.razon_social }}</h3>
                                    <!-- Rating placeholder -->
                                    <div class="flex items-center gap-1 mt-1">
                                        <div class="flex">
                                            <svg v-for="i in 5" :key="i" class="w-3.5 h-3.5" :class="i <= 4 ? 'text-amber-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </div>
                                        <span class="text-xs text-gray-500">(histórico)</span>
                                    </div>
                                </div>
                                <!-- Posición -->
                                <div 
                                    class="h-8 w-8 flex items-center justify-center rounded-full text-sm font-bold"
                                    :class="esMejorPrecio(cotizacion) ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    #{{ getPosicion(cotizacion) }}
                                </div>
                            </div>

                            <!-- Precio Total -->
                            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Cotizado</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl font-bold" :class="esMejorPrecio(cotizacion) ? 'text-emerald-600' : 'text-gray-900'">
                                        {{ formatCurrency(cotizacion.total) }}
                                    </span>
                                    <span v-if="!esMejorPrecio(cotizacion) && getPorcentajeDiferencia(cotizacion) > 0" class="text-sm text-red-500 font-medium">
                                        +{{ getPorcentajeDiferencia(cotizacion) }}%
                                    </span>
                                </div>
                            </div>

                            <!-- Info Adicional -->
                            <div class="grid grid-cols-2 gap-3 mb-5">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>{{ cotizacion.tiempo_entrega || '?' }} días</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Válido {{ cotizacion.validez_dias || '?' }}d</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    <span>{{ cotizacion.condicion_pago || 'Contado' }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <span>{{ cotizacion.respuestas?.length || 0 }} productos</span>
                                </div>
                            </div>

                            <!-- Botón Ver Detalle -->
                            <button 
                                @click="toggleDetalles(cotizacion.id)"
                                class="w-full flex items-center justify-between px-4 py-2.5 bg-gray-100 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors mb-4"
                            >
                                <span>Ver detalle de precios</span>
                                <svg 
                                    class="w-4 h-4 transition-transform duration-200" 
                                    :class="mostrarDetalles[cotizacion.id] ? 'rotate-180' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Detalle Expandible -->
                            <div v-if="mostrarDetalles[cotizacion.id]" class="mb-4 border border-gray-200 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Producto</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Precio</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="respuesta in cotizacion.respuestas" :key="respuesta.id" class="hover:bg-gray-50">
                                            <td class="px-3 py-2 text-gray-900">
                                                {{ respuesta.producto?.nombre || `Producto #${respuesta.producto_id}` }}
                                            </td>
                                            <td class="px-3 py-2 text-right font-medium" :class="getMejorPrecioProducto(respuesta.producto_id) === cotizacion.id ? 'text-emerald-600' : 'text-gray-900'">
                                                {{ formatCurrency(respuesta.precio_unitario) }}
                                                <svg v-if="getMejorPrecioProducto(respuesta.producto_id) === cotizacion.id" class="w-3.5 h-3.5 inline ml-1 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Botón Elegir -->
                            <button 
                                @click="abrirModalElegir(cotizacion)"
                                class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-semibold transition-all duration-200"
                                :class="esMejorPrecio(cotizacion) 
                                    ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-200' 
                                    : 'bg-gray-900 text-white hover:bg-gray-800'"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                {{ esMejorPrecio(cotizacion) ? 'Elegir Ganador' : 'Elegir esta oferta' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla Comparativa Colapsable -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <button 
                        @click="mostrarTablaComparativa = !mostrarTablaComparativa"
                        class="w-full px-6 py-4 flex items-center justify-between bg-gray-50 hover:bg-gray-100 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                            </svg>
                            <span class="font-semibold text-gray-900">Tabla Comparativa Detallada</span>
                        </div>
                        <svg 
                            class="w-5 h-5 text-gray-400 transition-transform duration-200" 
                            :class="mostrarTablaComparativa ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    
                    <div v-if="mostrarTablaComparativa" class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-t border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50">
                                        Producto
                                    </th>
                                    <th 
                                        v-for="cot in cotizacionesOrdenadas" 
                                        :key="cot.id"
                                        class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider"
                                        :class="esMejorPrecio(cot) ? 'text-emerald-700 bg-emerald-50' : 'text-gray-500'"
                                    >
                                        {{ cot.proveedor?.razon_social?.substring(0, 15) }}
                                        <span v-if="esMejorPrecio(cot)" class="block text-emerald-600 text-[10px] font-bold">MEJOR</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="producto in productos" :key="producto.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm text-gray-900 sticky left-0 bg-white">
                                        {{ producto.nombre }}
                                    </td>
                                    <td 
                                        v-for="cot in cotizacionesOrdenadas" 
                                        :key="cot.id"
                                        class="px-6 py-3 text-center"
                                        :class="esMejorPrecio(cot) ? 'bg-emerald-50/50' : ''"
                                    >
                                        <span 
                                            v-if="cot.respuestas?.find(r => r.producto_id === producto.id)"
                                            class="text-sm font-medium"
                                            :class="getMejorPrecioProducto(producto.id) === cot.id ? 'text-emerald-600 font-bold' : 'text-gray-700'"
                                        >
                                            {{ formatCurrency(cot.respuestas.find(r => r.producto_id === producto.id)?.precio_unitario) }}
                                            <svg v-if="getMejorPrecioProducto(producto.id) === cot.id" class="w-3.5 h-3.5 inline ml-0.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                        <span v-else class="text-sm text-gray-400">-</span>
                                    </td>
                                </tr>
                                <!-- Fila Total -->
                                <tr class="bg-gray-50 font-bold">
                                    <td class="px-6 py-4 text-sm text-gray-900 sticky left-0 bg-gray-50">
                                        TOTAL
                                    </td>
                                    <td 
                                        v-for="cot in cotizacionesOrdenadas" 
                                        :key="cot.id"
                                        class="px-6 py-4 text-center text-base"
                                        :class="esMejorPrecio(cot) ? 'text-emerald-700 bg-emerald-100' : 'text-gray-900'"
                                    >
                                        {{ formatCurrency(cot.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!cotizaciones?.length" class="text-center py-16">
                    <div class="mx-auto h-16 w-16 text-gray-300 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">No hay ofertas para comparar</h3>
                    <p class="text-sm text-gray-500 mt-1">Aún no se han recibido respuestas de los proveedores.</p>
                    <Link 
                        :href="route('solicitudes-cotizacion.show', solicitud.id)"
                        class="inline-flex items-center gap-2 mt-6 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver a la solicitud
                    </Link>
                </div>
            </div>
        </div>

        <!-- Modal: Elegir Ganador -->
        <Modal :show="mostrarModalElegir" @close="mostrarModalElegir = false" max-width="md">
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="mx-auto h-14 w-14 bg-emerald-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Confirmar Selección</h3>
                </div>

                <div class="bg-gray-50 rounded-xl p-5 mb-6">
                    <p class="text-sm text-gray-500 mb-1">Proveedor seleccionado</p>
                    <p class="text-lg font-bold text-gray-900">{{ cotizacionSeleccionada?.proveedor?.razon_social }}</p>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <p class="text-sm text-gray-500 mb-1">Total de la oferta</p>
                        <p class="text-2xl font-bold text-emerald-600">{{ formatCurrency(cotizacionSeleccionada?.total) }}</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors" :class="formElegir.generar_orden ? 'border-indigo-300 bg-indigo-50' : ''">
                        <input type="radio" v-model="formElegir.generar_orden" :value="true" class="mt-0.5 h-4 w-4 text-indigo-600 border-gray-300">
                        <div>
                            <span class="font-medium text-gray-900">Generar Orden de Compra ahora</span>
                            <p class="text-xs text-gray-500 mt-0.5">Se creará y enviará la OC automáticamente al proveedor</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors" :class="!formElegir.generar_orden ? 'border-indigo-300 bg-indigo-50' : ''">
                        <input type="radio" v-model="formElegir.generar_orden" :value="false" class="mt-0.5 h-4 w-4 text-indigo-600 border-gray-300">
                        <div>
                            <span class="font-medium text-gray-900">Solo marcar como elegida</span>
                            <p class="text-xs text-gray-500 mt-0.5">Podrás generar la OC más tarde</p>
                        </div>
                    </label>
                </div>

                <div class="flex gap-3 mt-6">
                    <button 
                        @click="mostrarModalElegir = false" 
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="elegirGanador" 
                        :disabled="formElegir.processing"
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ formElegir.processing ? 'Procesando...' : 'Confirmar Selección' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
