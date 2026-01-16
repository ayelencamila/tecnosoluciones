<script setup>
/**
 * Vista: Crear Solicitud de Cotización (CU-20)
 */
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    productos: Array,
    proveedores: Array,
    productosBajoStock: Array,
})

const form = useForm({
    fecha_vencimiento: '',
    observaciones: '',
    productos: [],
    proveedores: [],
})

// Búsqueda de productos
const busquedaProducto = ref('')
const productosFiltrados = computed(() => {
    if (!busquedaProducto.value) return props.productos.slice(0, 20)
    const termino = busquedaProducto.value.toLowerCase()
    return props.productos.filter(p =>
        p.nombre.toLowerCase().includes(termino) ||
        p.codigo?.toLowerCase().includes(termino)
    ).slice(0, 20)
})

// Agregar producto
function agregarProducto(producto) {
    if (form.productos.find(p => p.producto_id === producto.id)) return
    
    form.productos.push({
        producto_id: producto.id,
        nombre: producto.nombre,
        codigo: producto.codigo,
        cantidad_sugerida: 1,
        observaciones: '',
    })
    busquedaProducto.value = ''
}

// Remover producto
function removerProducto(index) {
    form.productos.splice(index, 1)
}

// Agregar productos bajo stock
function agregarProductosBajoStock() {
    props.productosBajoStock.forEach(item => {
        if (!form.productos.find(p => p.producto_id === item.producto_id)) {
            form.productos.push({
                producto_id: item.producto_id,
                nombre: item.producto?.nombre || item.producto_nombre,
                codigo: item.producto?.codigo || item.producto_codigo,
                cantidad_sugerida: item.faltante || item.cantidad_sugerida || 10,
                observaciones: `Stock actual: ${item.cantidad_actual || item.stock_actual}`,
            })
        }
    })
}

// Toggle proveedor
function toggleProveedor(proveedorId) {
    const index = form.proveedores.indexOf(proveedorId)
    if (index > -1) {
        form.proveedores.splice(index, 1)
    } else {
        form.proveedores.push(proveedorId)
    }
}

// Seleccionar todos los proveedores
function seleccionarTodosProveedores() {
    form.proveedores = props.proveedores.map(p => p.id)
}

// Enviar formulario
function enviar() {
    form.post(route('solicitudes-cotizacion.store'))
}
</script>

<template>
    <AppLayout title="Nueva Solicitud de Cotización">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Nueva Solicitud de Cotización
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Seleccione los productos y proveedores para solicitar cotización
            </p>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Barra de navegación -->
                <div class="mb-6">
                    <Link
                        :href="route('solicitudes-cotizacion.index')"
                        class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Volver al listado
                    </Link>
                </div>

                <form @submit.prevent="enviar" class="space-y-6">
                    <!-- Datos generales -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Datos de la Solicitud</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                                    Fecha de Vencimiento *
                                </label>
                                <input
                                    type="date"
                                    v-model="form.fecha_vencimiento"
                                    :min="new Date().toISOString().split('T')[0]"
                                    class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Fecha límite para recibir cotizaciones
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">
                                    Observaciones
                                </label>
                                <textarea
                                    v-model="form.observaciones"
                                    rows="3"
                                    class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Instrucciones adicionales para los proveedores..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Productos bajo stock -->
                    <div v-if="productosBajoStock.length > 0" class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-amber-800">
                                        {{ productosBajoStock.length }} producto(s) bajo stock mínimo
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="agregarProductosBajoStock"
                                class="inline-flex items-center px-3 py-1.5 bg-amber-600 text-white text-sm font-medium rounded-md hover:bg-amber-700 transition-colors"
                            >
                                Agregar Todos
                            </button>
                        </div>
                    </div>

                    <!-- Selección de productos -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Productos a Cotizar *</h3>
                        
                        <!-- Buscador -->
                        <div class="relative mb-4">
                            <input
                                type="text"
                                v-model="busquedaProducto"
                                placeholder="Buscar producto por nombre o código..."
                                class="w-full rounded-md border-gray-300 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            
                            <!-- Dropdown de resultados -->
                            <div v-if="busquedaProducto && productosFiltrados.length" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                <button
                                    v-for="producto in productosFiltrados"
                                    :key="producto.id"
                                    type="button"
                                    @click="agregarProducto(producto)"
                                    class="w-full px-4 py-2 text-left hover:bg-gray-50 flex justify-between items-center text-sm"
                                >
                                    <div>
                                        <span class="font-medium text-gray-900">{{ producto.nombre }}</span>
                                        <span class="text-gray-500 ml-2">({{ producto.codigo }})</span>
                                    </div>
                                    <span class="text-xs text-gray-400">Stock: {{ producto.stock_actual }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Lista de productos seleccionados -->
                        <div v-if="form.productos.length" class="space-y-3">
                            <div
                                v-for="(producto, index) in form.productos"
                                :key="producto.producto_id"
                                class="flex items-center gap-4 p-4 bg-gray-50 border border-gray-200 rounded-lg"
                            >
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ producto.nombre }}</p>
                                    <p class="text-xs text-gray-500">{{ producto.codigo }}</p>
                                </div>
                                <div class="w-28">
                                    <label class="block text-xs text-gray-500 mb-1">Cantidad</label>
                                    <input
                                        type="number"
                                        v-model="producto.cantidad_sugerida"
                                        min="1"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Observaciones</label>
                                    <input
                                        type="text"
                                        v-model="producto.observaciones"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Notas..."
                                    />
                                </div>
                                <button
                                    type="button"
                                    @click="removerProducto(index)"
                                    class="text-red-500 hover:text-red-700 p-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 border border-dashed border-gray-300 rounded-lg">
                            <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No hay productos seleccionados</p>
                            <p class="text-xs text-gray-400">Use el buscador para agregar productos</p>
                        </div>
                    </div>

                    <!-- Selección de proveedores -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Proveedores a Invitar *</h3>
                            <button
                                type="button"
                                @click="seleccionarTodosProveedores"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                Seleccionar todos
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <label
                                v-for="proveedor in proveedores"
                                :key="proveedor.id"
                                class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                :class="form.proveedores.includes(proveedor.id) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'"
                            >
                                <input
                                    type="checkbox"
                                    :checked="form.proveedores.includes(proveedor.id)"
                                    @change="toggleProveedor(proveedor.id)"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ proveedor.razon_social }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ proveedor.email || proveedor.telefono || 'Sin contacto' }}
                                    </p>
                                </div>
                            </label>
                        </div>
                        <p v-if="form.proveedores.length" class="mt-3 text-sm text-gray-600">
                            {{ form.proveedores.length }} proveedor(es) seleccionado(s)
                        </p>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                        <Link
                            :href="route('solicitudes-cotizacion.index')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.productos.length || !form.proveedores.length"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                        >
                            <span v-if="form.processing">Creando...</span>
                            <span v-else>Crear Solicitud</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
