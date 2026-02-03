<script setup>
/**
 * Vista: Registrar Egreso de Stock
 * 
 * Formulario para registrar salidas de stock por:
 * robo, pérdida, defectuosos, uso interno, muestras, etc.
 */
import { ref, computed, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    productos: Array,
    tiposEgreso: Array,
})

const form = useForm({
    producto_id: '',
    tipo_movimiento_id: '',
    cantidad: 1,
    motivo: '',
})

const busquedaProducto = ref('')
const productoSeleccionado = ref(null)

// Filtrar productos por búsqueda
const productosFiltrados = computed(() => {
    if (!busquedaProducto.value) return props.productos.slice(0, 10)
    const query = busquedaProducto.value.toLowerCase()
    return props.productos.filter(p => 
        p.nombre.toLowerCase().includes(query) || 
        p.codigo.toLowerCase().includes(query)
    ).slice(0, 10)
})

// Cuando se selecciona un producto
function seleccionarProducto(producto) {
    productoSeleccionado.value = producto
    form.producto_id = producto.id
    busquedaProducto.value = ''
}

// Quitar producto seleccionado
function quitarProducto() {
    productoSeleccionado.value = null
    form.producto_id = ''
}

// Validar cantidad máxima
watch(() => form.cantidad, (val) => {
    if (productoSeleccionado.value && val > productoSeleccionado.value.stock_disponible) {
        form.cantidad = productoSeleccionado.value.stock_disponible
    }
})

function submit() {
    form.post(route('egresos-stock.store'))
}

function getTipoIcon(tipo) {
    const nombre = tipo?.nombre?.toLowerCase() || ''
    if (nombre.includes('robo')) return 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'
    if (nombre.includes('defectuoso')) return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
    if (nombre.includes('pérdida') || nombre.includes('merma')) return 'M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    if (nombre.includes('muestra') || nombre.includes('donación')) return 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7'
    if (nombre.includes('uso interno')) return 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
    return 'M20 12H4'
}

function getTipoClass(tipo) {
    const nombre = tipo?.nombre?.toLowerCase() || ''
    if (nombre.includes('robo')) return 'border-red-200 bg-red-50 hover:bg-red-100'
    if (nombre.includes('defectuoso')) return 'border-orange-200 bg-orange-50 hover:bg-orange-100'
    if (nombre.includes('pérdida') || nombre.includes('merma')) return 'border-amber-200 bg-amber-50 hover:bg-amber-100'
    if (nombre.includes('muestra') || nombre.includes('donación')) return 'border-purple-200 bg-purple-50 hover:bg-purple-100'
    if (nombre.includes('uso interno')) return 'border-blue-200 bg-blue-50 hover:bg-blue-100'
    return 'border-gray-200 bg-gray-50 hover:bg-gray-100'
}

function getTipoIconClass(tipo) {
    const nombre = tipo?.nombre?.toLowerCase() || ''
    if (nombre.includes('robo')) return 'text-red-600'
    if (nombre.includes('defectuoso')) return 'text-orange-600'
    if (nombre.includes('pérdida') || nombre.includes('merma')) return 'text-amber-600'
    if (nombre.includes('muestra') || nombre.includes('donación')) return 'text-purple-600'
    if (nombre.includes('uso interno')) return 'text-blue-600'
    return 'text-gray-600'
}
</script>

<template>
    <AppLayout title="Registrar Egreso">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('egresos-stock.index')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </Link>
                <div>
                    <h2 class="font-bold text-xl text-gray-900">Registrar Egreso de Stock</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Salida por pérdida, robo, defectuoso u otro motivo</p>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <!-- Paso 1: Seleccionar Producto -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center gap-3">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold text-sm">1</div>
                            <h3 class="font-semibold text-gray-900">Seleccionar Producto</h3>
                        </div>
                        <div class="p-6">
                            <!-- Producto Seleccionado -->
                            <div v-if="productoSeleccionado" class="flex items-center justify-between p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 flex-shrink-0 bg-indigo-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ productoSeleccionado.nombre }}</p>
                                        <p class="text-sm text-gray-500">
                                            <span class="font-mono">{{ productoSeleccionado.codigo }}</span>
                                            <span class="mx-2">|</span>
                                            Stock disponible: <span class="font-semibold text-indigo-600">{{ productoSeleccionado.stock_disponible }}</span>
                                        </p>
                                    </div>
                                </div>
                                <button type="button" @click="quitarProducto" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Buscador de Producto -->
                            <div v-else>
                                <div class="relative">
                                    <input
                                        v-model="busquedaProducto"
                                        type="text"
                                        placeholder="Buscar producto por nombre o código..."
                                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                    <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                
                                <!-- Lista de productos -->
                                <ul v-if="productosFiltrados.length > 0" class="mt-2 border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-64 overflow-y-auto">
                                    <li 
                                        v-for="producto in productosFiltrados" 
                                        :key="producto.id"
                                        @click="seleccionarProducto(producto)"
                                        class="p-3 hover:bg-gray-50 cursor-pointer flex items-center justify-between"
                                    >
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ producto.nombre }}</p>
                                            <p class="text-xs text-gray-500 font-mono">{{ producto.codigo }}</p>
                                        </div>
                                        <span class="text-sm text-gray-600">Stock: {{ producto.stock_disponible }}</span>
                                    </li>
                                </ul>
                                <p v-else-if="busquedaProducto" class="mt-2 text-sm text-gray-500 text-center py-4">
                                    No se encontraron productos con stock disponible
                                </p>
                            </div>
                            <InputError :message="form.errors.producto_id" class="mt-2" />
                        </div>
                    </div>

                    <!-- Paso 2: Tipo de Egreso -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center gap-3">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold text-sm">2</div>
                            <h3 class="font-semibold text-gray-900">Tipo de Egreso</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <button
                                    v-for="tipo in tiposEgreso"
                                    :key="tipo.id"
                                    type="button"
                                    @click="form.tipo_movimiento_id = tipo.id"
                                    class="p-4 border-2 rounded-lg text-center transition-all"
                                    :class="[
                                        getTipoClass(tipo),
                                        form.tipo_movimiento_id === tipo.id ? 'ring-2 ring-indigo-500 border-indigo-500' : ''
                                    ]"
                                >
                                    <svg class="w-6 h-6 mx-auto mb-2" :class="getTipoIconClass(tipo)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTipoIcon(tipo)"/>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-700">{{ tipo.nombre }}</p>
                                </button>
                            </div>
                            <InputError :message="form.errors.tipo_movimiento_id" class="mt-2" />
                        </div>
                    </div>

                    <!-- Paso 3: Cantidad y Motivo -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center gap-3">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold text-sm">3</div>
                            <h3 class="font-semibold text-gray-900">Cantidad y Motivo</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Cantidad a egresar
                                    <span v-if="productoSeleccionado" class="text-gray-400 font-normal">(max: {{ productoSeleccionado.stock_disponible }})</span>
                                </label>
                                <input
                                    v-model.number="form.cantidad"
                                    type="number"
                                    min="1"
                                    :max="productoSeleccionado?.stock_disponible || 999"
                                    class="w-full md:w-48 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg font-semibold"
                                />
                                <InputError :message="form.errors.cantidad" class="mt-1" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Motivo / Observaciones <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.motivo"
                                    rows="3"
                                    placeholder="Describa el motivo del egreso con detalle..."
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                ></textarea>
                                <InputError :message="form.errors.motivo" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Resumen y Acción -->
                    <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-red-100 rounded-lg">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-red-800">Confirmar Egreso</h4>
                                <p class="text-sm text-red-700 mt-1">
                                    Esta acción descontará <strong>{{ form.cantidad }}</strong> unidad(es) del stock del producto seleccionado. Esta operación quedará registrada en el historial.
                                </p>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex justify-end gap-3">
                            <Link :href="route('egresos-stock.index')" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.producto_id || !form.tipo_movimiento_id || !form.motivo"
                                class="px-6 py-2.5 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ form.processing ? 'Registrando...' : 'Registrar Egreso' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
