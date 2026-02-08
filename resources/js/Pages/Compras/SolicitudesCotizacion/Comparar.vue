<script setup>
/**
 * Vista: Arena de Comparación de Ofertas (CU-21)
 * Diseño: Comparación producto por producto con ranking de proveedores
 * 
 * Muestra ganador POR PRODUCTO (precio + calificación),
 * resumen de proveedores y tabla comparativa cruzada.
 * 
 * Flujo: Index → Show → Comparar → Ordenes/Show
 */
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    solicitud: Object,
    cotizaciones: Array,           // Cotizaciones con respuestas
    productos: Array,              // Lista de productos solicitados
    comparacionProductos: Array,   // Comparación producto por producto (del service)
    resumenProveedores: Array,     // Resumen por proveedor con productos ganados
})

// --- Estado UI ---
const cotizacionSeleccionada = ref(null)
const mostrarModalElegir = ref(false)
const vistaActiva = ref('productos') // 'productos' | 'proveedores' | 'tabla'

// --- Formulario ---
const formElegir = useForm({
    generar_orden: true
})

// --- Computed ---
const totalProductos = computed(() => props.productos?.length || 0)

const productosSinOferta = computed(() => {
    return (props.comparacionProductos || []).filter(p => p.sin_ofertas).length
})

const proveedorMasGanador = computed(() => {
    if (!props.resumenProveedores?.length) return null
    return props.resumenProveedores[0] // Ya viene ordenado del backend
})

// Cotizaciones ordenadas por total (para la tabla cruzada)
const cotizacionesOrdenadas = computed(() => {
    return [...(props.cotizaciones || [])].sort((a, b) => (a.total || 0) - (b.total || 0))
})

// --- Métodos ---
function abrirModalElegir(cotizacion) {
    // Puede recibir un objeto cotización o un resumen de proveedor
    const cot = cotizacion.cotizacion_id 
        ? props.cotizaciones?.find(c => c.id === cotizacion.cotizacion_id) || cotizacion
        : cotizacion
    cotizacionSeleccionada.value = cot
    mostrarModalElegir.value = true
}

function elegirGanador() {
    const id = cotizacionSeleccionada.value?.id || cotizacionSeleccionada.value?.cotizacion_id
    formElegir.post(route('solicitudes-cotizacion.elegir-cotizacion', [props.solicitud.id, id]), {
        onSuccess: () => {
            mostrarModalElegir.value = false
            cotizacionSeleccionada.value = null
        }
    })
}

function getMejorPrecioProducto(productoId) {
    const comp = props.comparacionProductos?.find(p => p.producto_id === productoId)
    return comp?.mejor_cotizacion_id || null
}

function getEstrellas(calificacion) {
    return Math.round(parseFloat(calificacion) || 0)
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
                        {{ cotizaciones?.length || 0 }} ofertas · {{ totalProductos }} productos solicitados
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-100 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Resumen rápido -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl p-4 border border-gray-200">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Ofertas recibidas</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ cotizaciones?.length || 0 }}</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 border border-gray-200">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Productos solicitados</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ totalProductos }}</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 border border-gray-200">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Sin ofertas</p>
                        <p class="text-2xl font-bold mt-1" :class="productosSinOferta > 0 ? 'text-red-600' : 'text-emerald-600'">{{ productosSinOferta }}</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 border border-emerald-200 bg-emerald-50" v-if="proveedorMasGanador">
                        <p class="text-xs text-emerald-700 uppercase tracking-wider">Mejor proveedor</p>
                        <p class="text-lg font-bold text-emerald-800 mt-1 truncate">{{ proveedorMasGanador.proveedor?.razon_social }}</p>
                        <p class="text-xs text-emerald-600">{{ proveedorMasGanador.productos_ganados }} de {{ totalProductos }} productos ganados</p>
                    </div>
                </div>

                <!-- Tabs de navegación -->
                <div class="bg-white rounded-t-xl border border-gray-200 border-b-0 px-2 pt-2 flex gap-1">
                    <button 
                        @click="vistaActiva = 'productos'"
                        class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors"
                        :class="vistaActiva === 'productos' ? 'bg-indigo-50 text-indigo-700 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                    >
                        Por Producto
                    </button>
                    <button 
                        @click="vistaActiva = 'proveedores'"
                        class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors"
                        :class="vistaActiva === 'proveedores' ? 'bg-indigo-50 text-indigo-700 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                    >
                        Por Proveedor
                    </button>
                    <button 
                        @click="vistaActiva = 'tabla'"
                        class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors"
                        :class="vistaActiva === 'tabla' ? 'bg-indigo-50 text-indigo-700 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                    >
                        Tabla Cruzada
                    </button>
                </div>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- VISTA 1: COMPARACIÓN POR PRODUCTO (principal)      -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div v-if="vistaActiva === 'productos'" class="bg-white rounded-b-xl rounded-tr-xl border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900">Comparación producto por producto</h3>
                        <p class="text-sm text-gray-500">Para cada producto se muestra el ganador por mejor precio. A igual precio, gana el proveedor con mejor calificación.</p>
                    </div>

                    <div class="space-y-4">
                        <div 
                            v-for="(comp, idx) in comparacionProductos" 
                            :key="comp.producto_id"
                            class="border rounded-xl overflow-hidden"
                            :class="comp.sin_ofertas ? 'border-red-200 bg-red-50' : 'border-gray-200'"
                        >
                            <!-- Cabecera producto -->
                            <div class="px-5 py-3 flex items-center justify-between" :class="comp.sin_ofertas ? 'bg-red-100' : 'bg-gray-50'">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">
                                        {{ idx + 1 }}
                                    </span>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">{{ comp.producto_nombre }}</h4>
                                        <span v-if="comp.producto_codigo" class="text-xs text-gray-500 font-mono">{{ comp.producto_codigo }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="text-gray-500">
                                        Cantidad solicitada: <strong>{{ comp.cantidad_solicitada }}</strong>
                                    </span>
                                    <span 
                                        class="px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="comp.sin_ofertas ? 'bg-red-200 text-red-800' : 'bg-emerald-100 text-emerald-800'"
                                    >
                                        {{ comp.total_ofertas }} {{ comp.total_ofertas === 1 ? 'oferta' : 'ofertas' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Sin ofertas -->
                            <div v-if="comp.sin_ofertas" class="px-5 py-4 text-center">
                                <p class="text-sm text-red-600 font-medium">Ningún proveedor cotizó este producto</p>
                            </div>

                            <!-- Tabla de ofertas -->
                            <div v-else class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50/50">
                                        <tr>
                                            <th class="px-5 py-2 text-left text-xs font-medium text-gray-500 uppercase w-10"></th>
                                            <th class="px-5 py-2 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                                            <th class="px-5 py-2 text-center text-xs font-medium text-gray-500 uppercase">Calificación</th>
                                            <th class="px-5 py-2 text-right text-xs font-medium text-gray-500 uppercase">Precio Unit.</th>
                                            <th class="px-5 py-2 text-center text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                            <th class="px-5 py-2 text-center text-xs font-medium text-gray-500 uppercase">Plazo</th>
                                            <th class="px-5 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr 
                                            v-for="(oferta, oIdx) in comp.ofertas" 
                                            :key="oferta.cotizacion_id"
                                            :class="oferta.cotizacion_id === comp.mejor_cotizacion_id ? 'bg-emerald-50' : 'hover:bg-gray-50'"
                                        >
                                            <!-- Posición -->
                                            <td class="px-5 py-2.5">
                                                <span 
                                                    class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                                    :class="oIdx === 0 ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-600'"
                                                >
                                                    {{ oIdx + 1 }}
                                                </span>
                                            </td>
                                            <!-- Proveedor -->
                                            <td class="px-5 py-2.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium text-gray-900">{{ oferta.proveedor }}</span>
                                                    <svg v-if="oferta.cotizacion_id === comp.mejor_cotizacion_id" class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                                    </svg>
                                                </div>
                                            </td>
                                            <!-- Calificación -->
                                            <td class="px-5 py-2.5 text-center">
                                                <div class="flex items-center justify-center gap-0.5">
                                                    <svg v-for="i in 5" :key="i" class="w-3.5 h-3.5" :class="i <= getEstrellas(oferta.calificacion) ? 'text-amber-400' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                    <span class="ml-1 text-xs text-gray-500">{{ parseFloat(oferta.calificacion || 0).toFixed(1) }}</span>
                                                </div>
                                            </td>
                                            <!-- Precio -->
                                            <td class="px-5 py-2.5 text-right font-semibold" :class="oIdx === 0 ? 'text-emerald-700' : 'text-gray-900'">
                                                {{ formatCurrency(oferta.precio_unitario) }}
                                            </td>
                                            <!-- Cantidad -->
                                            <td class="px-5 py-2.5 text-center text-gray-700">
                                                {{ oferta.cantidad_disponible }}
                                            </td>
                                            <!-- Plazo -->
                                            <td class="px-5 py-2.5 text-center text-gray-700">
                                                {{ oferta.plazo_entrega_dias }} días
                                            </td>
                                            <!-- Subtotal -->
                                            <td class="px-5 py-2.5 text-right font-semibold" :class="oIdx === 0 ? 'text-emerald-700' : 'text-gray-900'">
                                                {{ formatCurrency(oferta.subtotal) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- ═══════════════════════════════════════════════════ -->
                <!-- VISTA 2: RESUMEN POR PROVEEDOR (tarjetas)          -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div v-if="vistaActiva === 'proveedores'" class="bg-white rounded-b-xl rounded-tr-xl border border-gray-200 p-6">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900">Resumen por proveedor</h3>
                        <p class="text-sm text-gray-500">Ordenados por cantidad de productos con mejor oferta, luego por calificación.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div 
                            v-for="(prov, pIdx) in resumenProveedores" 
                            :key="prov.cotizacion_id"
                            class="relative bg-white rounded-2xl border-2 shadow-sm overflow-hidden transition-all duration-300 hover:shadow-lg"
                            :class="pIdx === 0 ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-gray-200 hover:border-gray-300'"
                        >
                            <!-- Badge: Mejor proveedor -->
                            <div v-if="pIdx === 0" class="bg-emerald-500 text-white text-xs font-bold py-1.5 px-3 flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                MEJOR PROVEEDOR
                            </div>

                            <div class="px-6 pb-6" :class="pIdx === 0 ? 'pt-4' : 'pt-6'">
                                <!-- Encabezado -->
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="h-12 w-12 flex-shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl flex items-center justify-center shadow-inner">
                                        <span class="text-sm font-bold text-gray-500">{{ getInitials(prov.proveedor?.razon_social) }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-900 truncate">{{ prov.proveedor?.razon_social }}</h3>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <svg v-for="i in 5" :key="i" class="w-3.5 h-3.5" :class="i <= getEstrellas(prov.calificacion) ? 'text-amber-400' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            <span class="text-xs text-gray-500 ml-0.5">{{ parseFloat(prov.calificacion || 0).toFixed(1) }}</span>
                                        </div>
                                    </div>
                                    <span 
                                        class="h-8 w-8 flex items-center justify-center rounded-full text-sm font-bold"
                                        :class="pIdx === 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                    >
                                        #{{ pIdx + 1 }}
                                    </span>
                                </div>

                                <!-- Métricas -->
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <div class="bg-emerald-50 rounded-lg p-3 text-center">
                                        <p class="text-xs text-emerald-600 font-medium">Productos ganados</p>
                                        <p class="text-2xl font-bold text-emerald-700">{{ prov.productos_ganados }}</p>
                                        <p class="text-xs text-emerald-600">de {{ prov.productos_requeridos }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                                        <p class="text-xs text-gray-500 font-medium">Productos cotizados</p>
                                        <p class="text-2xl font-bold text-gray-900">{{ prov.productos_cotizados }}</p>
                                        <p class="text-xs text-gray-500">de {{ prov.productos_requeridos }}</p>
                                    </div>
                                </div>

                                <!-- Total y plazo -->
                                <div class="bg-gray-50 rounded-xl p-4 mb-4">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase tracking-wider">Total cotizado</p>
                                            <p class="text-xl font-bold" :class="pIdx === 0 ? 'text-emerald-600' : 'text-gray-900'">
                                                {{ formatCurrency(prov.total_cotizado) }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500 uppercase tracking-wider">Plazo máx.</p>
                                            <p class="text-lg font-semibold text-gray-700">{{ prov.plazo_maximo || '?' }} días</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Barra de cobertura -->
                                <div class="mb-4">
                                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                                        <span>Cobertura de productos</span>
                                        <span>{{ Math.round((prov.productos_cotizados / prov.productos_requeridos) * 100) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="h-2 rounded-full transition-all duration-500"
                                            :class="prov.productos_cotizados >= prov.productos_requeridos ? 'bg-emerald-500' : 'bg-amber-500'"
                                            :style="{ width: Math.min(100, (prov.productos_cotizados / prov.productos_requeridos) * 100) + '%' }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Botón Elegir -->
                                <button 
                                    @click="abrirModalElegir(prov)"
                                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-semibold transition-all duration-200"
                                    :class="pIdx === 0 
                                        ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-200' 
                                        : 'bg-gray-900 text-white hover:bg-gray-800'"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                    {{ pIdx === 0 ? 'Elegir Ganador' : 'Elegir esta oferta' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- ═══════════════════════════════════════════════════ -->
                <!-- VISTA 3: TABLA COMPARATIVA CRUZADA                 -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div v-if="vistaActiva === 'tabla'" class="bg-white rounded-b-xl rounded-tr-xl border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">Tabla comparativa cruzada</h3>
                        <p class="text-sm text-gray-500">Productos × Proveedores. El mejor precio por producto se resalta en verde.</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 min-w-[180px]">
                                        Producto
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">
                                        Cant.
                                    </th>
                                    <th 
                                        v-for="cot in cotizacionesOrdenadas" 
                                        :key="cot.id"
                                        class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider min-w-[140px]"
                                        :class="resumenProveedores?.[0]?.cotizacion_id === cot.id ? 'text-emerald-700 bg-emerald-50' : 'text-gray-500'"
                                    >
                                        <div class="truncate">{{ cot.proveedor?.razon_social?.substring(0, 18) }}</div>
                                        <div class="flex items-center justify-center gap-0.5 mt-1">
                                            <svg v-for="i in 5" :key="i" class="w-2.5 h-2.5" :class="i <= getEstrellas(cot.calificacion) ? 'text-amber-400' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </div>
                                        <div class="text-[10px] mt-0.5">
                                            {{ cot.productos_count || cot.respuestas?.length || 0 }}/{{ totalProductos }} prod.
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="producto in productos" :key="producto.id" class="hover:bg-gray-50">
                                    <td class="px-5 py-3 text-sm text-gray-900 sticky left-0 bg-white font-medium z-10">
                                        {{ producto.nombre }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-500">
                                        {{ producto.cantidad }}
                                    </td>
                                    <td 
                                        v-for="cot in cotizacionesOrdenadas" 
                                        :key="cot.id"
                                        class="px-5 py-3 text-center"
                                    >
                                        <template v-if="cot.respuestas?.find(r => r.producto_id === producto.id)">
                                            <span 
                                                class="text-sm font-medium"
                                                :class="getMejorPrecioProducto(producto.id) === cot.id ? 'text-emerald-600 font-bold' : 'text-gray-700'"
                                            >
                                                {{ formatCurrency(cot.respuestas.find(r => r.producto_id === producto.id)?.precio_unitario) }}
                                            </span>
                                            <svg v-if="getMejorPrecioProducto(producto.id) === cot.id" class="w-3.5 h-3.5 inline ml-0.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <div class="text-[10px] text-gray-400 mt-0.5">
                                                {{ cot.respuestas.find(r => r.producto_id === producto.id)?.plazo_entrega_dias }}d
                                            </div>
                                        </template>
                                        <span v-else class="text-sm text-gray-300 italic">No cotizó</span>
                                    </td>
                                </tr>
                                <!-- Fila Total -->
                                <tr class="bg-gray-50 font-bold border-t-2 border-gray-300">
                                    <td class="px-5 py-4 text-sm text-gray-900 sticky left-0 bg-gray-50 z-10" colspan="2">
                                        TOTAL
                                    </td>
                                    <td 
                                        v-for="cot in cotizacionesOrdenadas" 
                                        :key="cot.id"
                                        class="px-5 py-4 text-center text-base"
                                        :class="resumenProveedores?.[0]?.cotizacion_id === cot.id ? 'text-emerald-700 bg-emerald-100' : 'text-gray-900'"
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
                        <p class="text-2xl font-bold text-emerald-600">{{ formatCurrency(cotizacionSeleccionada?.total || cotizacionSeleccionada?.total_cotizado) }}</p>
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
