<script setup>
/**
 * Vista: Portal de Cotización para Proveedor (CU-20)
 * 
 * Página pública donde el proveedor puede:
 * - Ver los productos solicitados
 * - Seleccionar cuáles puede cotizar (respuesta parcial)
 * - Ingresar precios, cantidades y plazos
 * - Enviar o rechazar la cotización
 * 
 * Acceso mediante Magic Link (sin autenticación)
 */
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    token: String,
    proveedor: Object,
    solicitud: Object,
    productos: Array,
})

// Control de productos incluidos (por defecto todos activados)
const productosIncluidos = ref(
    Object.fromEntries(props.productos.map(p => [p.id, true]))
)

// Formulario de respuestas
const form = useForm({
    respuestas: props.productos.map(p => ({
        producto_id: p.id,
        precio_unitario: '',
        cantidad_disponible: p.cantidad_sugerida,
        plazo_entrega_dias: '',
        observaciones: '',
    }))
})

// Formulario de rechazo
const formRechazo = useForm({
    motivo: ''
})

const mostrarModalRechazo = ref(false)

// Respuestas activas (solo productos incluidos)
const respuestasActivas = computed(() => {
    return form.respuestas.filter(r => productosIncluidos.value[r.producto_id])
})

// Cantidad de productos incluidos
const productosSeleccionados = computed(() => {
    return respuestasActivas.value.length
})

// Calcular totales (solo productos incluidos)
const totalCotizado = computed(() => {
    return respuestasActivas.value.reduce((sum, r) => {
        const precio = parseFloat(r.precio_unitario) || 0
        const cantidad = parseInt(r.cantidad_disponible) || 0
        return sum + (precio * cantidad)
    }, 0)
})

// Validar solo los productos incluidos
const todosCompletos = computed(() => {
    if (respuestasActivas.value.length === 0) return false
    return respuestasActivas.value.every(r => 
        r.precio_unitario > 0 && 
        r.cantidad_disponible > 0 && 
        r.plazo_entrega_dias > 0
    )
})

// Helper: verificar si un producto está completo
function productoCompleto(productoId) {
    const r = form.respuestas.find(r => r.producto_id === productoId)
    if (!r) return false
    return r.precio_unitario > 0 && r.cantidad_disponible > 0 && r.plazo_entrega_dias > 0
}

// Enviar cotización (solo productos incluidos)
function enviarCotizacion() {
    // Filtrar solo las respuestas de productos incluidos
    const respuestasFiltradas = form.respuestas.filter(r => productosIncluidos.value[r.producto_id])
    
    const formFiltrado = useForm({
        respuestas: respuestasFiltradas
    })
    
    formFiltrado.post(route('portal.cotizacion.responder', props.token))
}

// Rechazar cotización
function rechazarCotizacion() {
    formRechazo.post(route('portal.cotizacion.rechazar', props.token))
}

// Toggle producto
function toggleProducto(productoId) {
    productosIncluidos.value[productoId] = !productosIncluidos.value[productoId]
}

// Formatear moneda
function formatCurrency(value) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS'
    }).format(value)
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-4xl mx-auto px-4 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">TecnoSoluciones</h1>
                        <p class="text-sm text-gray-500">Portal de Proveedores</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Solicitud</p>
                        <p class="font-mono font-semibold text-indigo-600">{{ solicitud.codigo }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-4 py-8">
            <!-- Bienvenida -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">
                    Hola, {{ proveedor.razon_social }}
                </h2>
                <p class="text-gray-600">
                    Le invitamos a cotizar los siguientes productos. Puede cotizar todos o solo los que tenga disponibles.
                    Active los productos que desea cotizar y complete los campos requeridos.
                </p>
                <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <strong>Cotización parcial:</strong> No es necesario cotizar todos los productos. 
                        Desactive los que no pueda ofrecer usando el interruptor de cada producto.
                    </p>
                </div>
                <div class="mt-4 flex gap-4 text-sm">
                    <div class="flex items-center text-gray-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Emitida: {{ solicitud.fecha_emision }}
                    </div>
                    <div class="flex items-center text-orange-600 font-medium">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Vence: {{ solicitud.fecha_vencimiento }}
                    </div>
                </div>
                <div v-if="solicitud.observaciones" class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">
                        <span class="font-medium">Observaciones:</span> {{ solicitud.observaciones }}
                    </p>
                </div>
            </div>

            <!-- Formulario de Productos -->
            <form @submit.prevent="enviarCotizacion">
                <div class="space-y-4">
                    <div 
                        v-for="(producto, index) in productos" 
                        :key="producto.id"
                        class="bg-white rounded-xl shadow-sm overflow-hidden transition-all duration-200"
                        :class="productosIncluidos[producto.id] ? 'ring-1 ring-indigo-200' : 'opacity-60'"
                    >
                        <!-- Cabecera del producto -->
                        <div class="px-6 py-4 border-b" :class="productosIncluidos[producto.id] ? 'bg-gray-50' : 'bg-gray-100'">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-3">
                                    <!-- Toggle Switch -->
                                    <button 
                                        type="button"
                                        @click="toggleProducto(producto.id)"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                                        :class="productosIncluidos[producto.id] ? 'bg-indigo-600' : 'bg-gray-200'"
                                        role="switch"
                                        :aria-checked="productosIncluidos[producto.id]"
                                    >
                                        <span 
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                            :class="productosIncluidos[producto.id] ? 'translate-x-5' : 'translate-x-0'"
                                        />
                                    </button>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">{{ producto.nombre }}</h3>
                                        <p class="text-sm text-gray-500">{{ producto.categoria }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="!productosIncluidos[producto.id]" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">
                                        No cotizar
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                        Cantidad sugerida: {{ producto.cantidad_sugerida }}
                                    </span>
                                </div>
                            </div>
                            <p v-if="producto.observaciones" class="mt-2 text-sm text-gray-600">
                                {{ producto.observaciones }}
                            </p>
                        </div>

                        <!-- Campos de cotización -->
                        <div v-if="productosIncluidos[producto.id]" class="px-6 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <!-- Precio Unitario -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Precio Unitario *
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            v-model="form.respuestas[index].precio_unitario"
                                            class="pl-8 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="0.00"
                                        />
                                    </div>
                                </div>

                                <!-- Cantidad Disponible -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Cantidad Disponible *
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        v-model="form.respuestas[index].cantidad_disponible"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>

                                <!-- Plazo de Entrega -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Plazo (días hábiles) *
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        v-model="form.respuestas[index].plazo_entrega_dias"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Días"
                                    />
                                </div>

                                <!-- Subtotal -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Subtotal
                                    </label>
                                    <div class="h-10 flex items-center text-lg font-semibold text-gray-900">
                                        {{ formatCurrency((form.respuestas[index].precio_unitario || 0) * (form.respuestas[index].cantidad_disponible || 0)) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones del producto -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Observaciones (opcional)
                                </label>
                                <textarea
                                    v-model="form.respuestas[index].observaciones"
                                    rows="2"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Información adicional sobre este producto..."
                                ></textarea>
                            </div>
                        </div>

                        <!-- Mensaje cuando está excluido -->
                        <div v-else class="px-6 py-6 text-center text-gray-400">
                            <p class="text-sm">Producto no incluido en la cotización</p>
                            <button type="button" @click="toggleProducto(producto.id)" class="mt-1 text-xs text-indigo-500 hover:text-indigo-700 underline">
                                Activar para cotizar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Total y Acciones -->
                <div class="mt-6 bg-white rounded-xl shadow-sm p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-4 mb-1">
                                <div>
                                    <p class="text-sm text-gray-500">Total de la cotización</p>
                                    <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(totalCotizado) }}</p>
                                </div>
                                <div class="border-l border-gray-200 pl-4">
                                    <p class="text-sm text-gray-500">Productos cotizados</p>
                                    <p class="text-lg font-semibold" :class="productosSeleccionados > 0 ? 'text-indigo-600' : 'text-red-500'">
                                        {{ productosSeleccionados }} / {{ productos.length }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="mostrarModalRechazo = true"
                                class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors"
                            >
                                No puedo cotizar
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || !todosCompletos"
                                class="px-8 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span v-if="form.processing">Enviando...</span>
                                <span v-else>Enviar Cotización</span>
                            </button>
                        </div>
                    </div>
                    <p v-if="productosSeleccionados === 0" class="mt-2 text-sm text-red-600">
                        Debe incluir al menos un producto para enviar la cotización
                    </p>
                    <p v-else-if="!todosCompletos" class="mt-2 text-sm text-orange-600">
                        Complete todos los campos obligatorios de los productos activados
                    </p>
                    <p v-else-if="productosSeleccionados < productos.length" class="mt-2 text-sm text-blue-600">
                        Cotización parcial: {{ productosSeleccionados }} de {{ productos.length }} productos
                    </p>
                </div>
            </form>
        </main>

        <!-- Modal de Rechazo -->
        <div v-if="mostrarModalRechazo" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="mostrarModalRechazo = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        ¿No puede cotizar esta solicitud?
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Por favor indíquenos el motivo para que podamos mejorar nuestro proceso.
                    </p>
                    <form @submit.prevent="rechazarCotizacion">
                        <textarea
                            v-model="formRechazo.motivo"
                            rows="4"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Ej: No contamos con stock, precios no actualizados, etc."
                            required
                        ></textarea>
                        <div class="mt-4 flex gap-3 justify-end">
                            <button
                                type="button"
                                @click="mostrarModalRechazo = false"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="formRechazo.processing"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                            >
                                Confirmar Rechazo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-12 py-6 text-center text-sm text-gray-500">
            <p>© {{ new Date().getFullYear() }} TecnoSoluciones. Todos los derechos reservados.</p>
            <p class="mt-1">Este enlace es personal y confidencial.</p>
        </footer>
    </div>
</template>
