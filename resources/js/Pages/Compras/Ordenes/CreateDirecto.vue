<script setup>
/**
 * Vista: Crear Orden de Compra Directa (Sin Cotización)
 * 
 * Permite crear OC sin pasar por el proceso de solicitud de cotización.
 * Caso de uso: compras urgentes o de proveedores conocidos.
 */
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    proveedores: Array,
    productos: Array,
})

const form = useForm({
    proveedor_id: '',
    productos: [],
    observaciones: '',
})

const busquedaProducto = ref('')
const mostrarBuscador = ref(false)

const proveedorSeleccionado = computed(() => {
    return props.proveedores.find(p => p.id === form.proveedor_id)
})

// Filtrar productos
const productosFiltrados = computed(() => {
    if (!busquedaProducto.value || busquedaProducto.value.length < 2) return []
    const query = busquedaProducto.value.toLowerCase()
    return props.productos.filter(p => 
        p.nombre.toLowerCase().includes(query) || 
        p.codigo.toLowerCase().includes(query)
    ).slice(0, 10)
})

// Agregar producto al listado
function agregarProducto(producto) {
    if (form.productos.some(p => p.producto_id === producto.id)) {
        return
    }
    form.productos.push({
        producto_id: producto.id,
        nombre: producto.nombre,
        codigo: producto.codigo,
        cantidad: 1,
        precio_unitario: producto.precio_costo || 0,
    })
    busquedaProducto.value = ''
    mostrarBuscador.value = false
}

// Quitar producto del listado
function quitarProducto(index) {
    form.productos.splice(index, 1)
}

// Calcular total
const totalOrden = computed(() => {
    return form.productos.reduce((sum, p) => sum + (p.cantidad * p.precio_unitario), 0)
})

function submit() {
    form.post(route('ordenes.store-directo'))
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value || 0)
}
</script>

<template>
    <AppLayout title="Nueva Orden de Compra Directa">
        <template #header>
            <div>
                <h2 class="font-bold text-xl text-gray-900">Gestión de Compras</h2>
                <p class="text-sm text-gray-500 mt-0.5">Órdenes de Compra</p>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Header con titulo y acciones -->
                <div class="mb-6">
                    <div class="flex items-center gap-4 mb-2">
                        <Link :href="route('ordenes.index')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Nueva Orden de Compra Directa</h1>
                            <p class="text-sm text-gray-500">Crear orden sin proceso de cotización previo</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <!-- Layout horizontal: sidebar izquierdo + contenido principal -->
                    <div class="flex flex-col lg:flex-row gap-6">
                        
                        <!-- Sidebar izquierdo (sticky) -->
                        <div class="lg:w-80 lg:flex-shrink-0">
                            <div class="lg:sticky lg:top-6 space-y-4">
                                
                                <!-- Card Proveedor -->
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-white">
                                        <div class="flex items-center gap-2">
                                            <div class="p-1.5 bg-indigo-100 rounded-lg">
                                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                            </div>
                                            <h3 class="font-semibold text-gray-800">Proveedor</h3>
                                        </div>
                                    </div>
                                    <div class="p-5">
                                        <select
                                            v-model="form.proveedor_id"
                                            class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-gray-50 hover:bg-white transition-colors"
                                        >
                                            <option value="">Seleccionar proveedor...</option>
                                            <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">
                                                {{ prov.razon_social }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors.proveedor_id" class="mt-2" />

                                        <!-- Info del proveedor seleccionado -->
                                        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                                            <div v-if="proveedorSeleccionado" class="mt-4 p-4 bg-gradient-to-br from-indigo-50 to-blue-50 rounded-lg border border-indigo-100">
                                                <p class="font-semibold text-gray-900">{{ proveedorSeleccionado.razon_social }}</p>
                                                <p class="text-xs text-indigo-600 font-medium mt-0.5">{{ proveedorSeleccionado.cuit || 'Sin CUIT registrado' }}</p>
                                                <div class="mt-3 pt-3 border-t border-indigo-100/50 space-y-2">
                                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                        </svg>
                                                        <span class="truncate">{{ proveedorSeleccionado.email || 'Sin email' }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                        </svg>
                                                        <span>{{ proveedorSeleccionado.whatsapp || 'Sin WhatsApp' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>
                                    </div>
                                </div>

                                <!-- Card Observaciones -->
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div class="px-5 py-4 border-b border-gray-100">
                                        <div class="flex items-center gap-2">
                                            <div class="p-1.5 bg-gray-100 rounded-lg">
                                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </div>
                                            <h3 class="font-semibold text-gray-800">Observaciones</h3>
                                            <span class="text-xs text-gray-400">(opcional)</span>
                                        </div>
                                    </div>
                                    <div class="p-5">
                                        <textarea
                                            v-model="form.observaciones"
                                            rows="3"
                                            placeholder="Notas adicionales para la orden..."
                                            class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm resize-none bg-gray-50 hover:bg-white transition-colors"
                                        ></textarea>
                                    </div>
                                </div>

                                <!-- Card Resumen y Acción (desktop) -->
                                <div class="hidden lg:block bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-xl shadow-lg overflow-hidden">
                                    <div class="p-5">
                                        <h3 class="text-sm font-medium text-indigo-200 uppercase tracking-wide mb-4">Resumen de Orden</h3>
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-center">
                                                <span class="text-indigo-200 text-sm">Productos</span>
                                                <span class="text-white font-semibold">{{ form.productos.length }} items</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-indigo-200 text-sm">Unidades</span>
                                                <span class="text-white font-semibold">{{ form.productos.reduce((sum, p) => sum + (p.cantidad || 0), 0) }}</span>
                                            </div>
                                            <div class="pt-3 mt-3 border-t border-indigo-500/50">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-white font-medium">Total</span>
                                                    <span class="text-2xl font-bold text-white">{{ formatCurrency(totalOrden) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="submit"
                                            :disabled="form.processing || !form.proveedor_id || form.productos.length === 0"
                                            class="w-full mt-5 inline-flex items-center justify-center gap-2 px-4 py-3 text-sm font-semibold text-indigo-700 bg-white rounded-lg hover:bg-indigo-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
                                        >
                                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ form.processing ? 'Generando...' : 'Generar Orden de Compra' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contenido principal: Productos -->
                        <div class="flex-1 min-w-0">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <!-- Header de productos -->
                                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-emerald-100 rounded-lg">
                                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-900">Productos a Ordenar</h3>
                                                <p class="text-xs text-gray-500">Busque y agregue los productos para la orden</p>
                                            </div>
                                        </div>
                                        <span v-if="form.productos.length > 0" class="px-3 py-1 text-xs font-medium bg-emerald-100 text-emerald-700 rounded-full">
                                            {{ form.productos.length }} {{ form.productos.length === 1 ? 'producto' : 'productos' }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="p-6">
                                    <!-- Buscador de productos -->
                                    <div class="relative mb-6">
                                        <div class="relative">
                                            <input
                                                v-model="busquedaProducto"
                                                @focus="mostrarBuscador = true"
                                                type="text"
                                                placeholder="Buscar producto por nombre o código..."
                                                class="w-full px-4 py-3 pl-11 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-gray-50 hover:bg-white transition-colors"
                                            />
                                            <svg class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                        </div>

                                        <!-- Dropdown de resultados -->
                                        <transition enter-active-class="transition ease-out duration-100" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                                            <div v-if="mostrarBuscador && productosFiltrados.length > 0" class="absolute z-20 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-xl max-h-72 overflow-auto">
                                                <div 
                                                    v-for="producto in productosFiltrados" 
                                                    :key="producto.id"
                                                    @click="agregarProducto(producto)"
                                                    class="px-4 py-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-50 last:border-0 flex items-center justify-between group transition-colors"
                                                    :class="{ 'opacity-40 cursor-not-allowed hover:bg-gray-50': form.productos.some(p => p.producto_id === producto.id) }"
                                                >
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-900">{{ producto.nombre }}</p>
                                                            <p class="text-xs text-gray-500">
                                                                <span class="font-mono">{{ producto.codigo }}</span>
                                                                <span class="mx-1.5">•</span>
                                                                Stock: {{ producto.stock_actual || 0 }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="p-2 bg-indigo-100 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>
                                    </div>
                                    <InputError :message="form.errors.productos" class="mb-4" />

                                    <!-- Tabla de productos seleccionados -->
                                    <div v-if="form.productos.length > 0" class="overflow-hidden rounded-xl border border-gray-200">
                                        <table class="w-full">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Producto</th>
                                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Cantidad</th>
                                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Precio Unit.</th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Subtotal</th>
                                                    <th class="px-4 py-3 w-14"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-100">
                                                <tr v-for="(item, index) in form.productos" :key="item.producto_id" class="hover:bg-gray-50/50 transition-colors">
                                                    <td class="px-4 py-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <p class="font-medium text-gray-900 text-sm">{{ item.nombre }}</p>
                                                                <p class="text-xs text-gray-400 font-mono">{{ item.codigo }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-4">
                                                        <input
                                                            v-model.number="item.cantidad"
                                                            type="number"
                                                            min="1"
                                                            class="w-full px-3 py-2 text-center border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 hover:bg-white transition-colors"
                                                        />
                                                    </td>
                                                    <td class="px-4 py-4">
                                                        <div class="relative">
                                                            <span class="absolute left-3 top-2.5 text-gray-400 text-sm">$</span>
                                                            <input
                                                                v-model.number="item.precio_unitario"
                                                                type="number"
                                                                min="0"
                                                                step="0.01"
                                                                class="w-full px-3 py-2 pl-7 text-right border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 hover:bg-white transition-colors"
                                                            />
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-4 text-right">
                                                        <span class="font-semibold text-gray-900">{{ formatCurrency(item.cantidad * item.precio_unitario) }}</span>
                                                    </td>
                                                    <td class="px-4 py-4">
                                                        <button
                                                            type="button"
                                                            @click="quitarProducto(index)"
                                                            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <!-- Total en tabla (desktop) -->
                                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                                <tr>
                                                    <td colspan="3" class="px-4 py-4 text-right font-semibold text-gray-700">Total de la Orden:</td>
                                                    <td class="px-4 py-4 text-right">
                                                        <span class="text-lg font-bold text-indigo-600">{{ formatCurrency(totalOrden) }}</span>
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <!-- Empty state mejorado -->
                                    <div v-else class="py-16 text-center">
                                        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <h4 class="text-gray-900 font-medium mb-1">Sin productos agregados</h4>
                                        <p class="text-sm text-gray-500 max-w-sm mx-auto">
                                            Utilice el buscador de arriba para encontrar y agregar productos a esta orden de compra
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer móvil con total y acciones -->
                    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg p-4 z-30">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-gray-600 font-medium">Total:</span>
                            <span class="text-xl font-bold text-indigo-600">{{ formatCurrency(totalOrden) }}</span>
                        </div>
                        <div class="flex gap-3">
                            <Link :href="route('ordenes.index')" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 text-center transition-colors">
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.proveedor_id || form.productos.length === 0"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                {{ form.processing ? 'Generando...' : 'Generar OC' }}
                            </button>
                        </div>
                    </div>
                    
                    <!-- Spacer para el footer móvil -->
                    <div class="lg:hidden h-32"></div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
