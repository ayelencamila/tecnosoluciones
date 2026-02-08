<template>
    <AppLayout title="Nueva Recepción Directa">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('recepciones.historial')" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Nueva Recepción de Mercadería (Sin Orden de Compra)
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <!-- Info Banner -->
                <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div class="text-sm text-blue-700">
                            <p class="font-medium">Recepcion directa de mercaderia</p>
                            <p class="mt-1">Registre la recepcion de productos sin una Orden de Compra previa. El stock se actualizara automaticamente al guardar.</p>
                        </div>
                    </div>
                </div>

                <!-- Error general -->
                <div v-if="form.errors.error" class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <p class="text-sm text-red-700 font-medium">{{ form.errors.error }}</p>
                </div>

                <form @submit.prevent="enviar" class="space-y-6">
                    <!-- Proveedor -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Proveedor</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Seleccionar Proveedor
                            </label>
                            <select 
                                v-model="form.proveedor_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option :value="null">-- Seleccione un proveedor --</option>
                                <option v-for="proveedor in proveedores" :key="proveedor.id" :value="proveedor.id">
                                    {{ proveedor.razon_social }} ({{ proveedor.cuit }})
                                </option>
                            </select>
                            <p v-if="form.errors.proveedor_id" class="mt-1 text-sm text-red-600">{{ form.errors.proveedor_id }}</p>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Productos a Recibir</h3>
                        
                        <!-- Buscador de productos -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar Producto</label>
                            <div class="relative">
                                <input
                                    v-model="busqueda"
                                    type="text"
                                    placeholder="Escriba para buscar productos..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-10"
                                    @focus="mostrarResultados = true"
                                />
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-2.5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                                
                                <!-- Dropdown de resultados -->
                                <div 
                                    v-if="mostrarResultados && productosFiltrados.length > 0"
                                    class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto"
                                >
                                    <div 
                                        v-for="producto in productosFiltrados" 
                                        :key="producto.id"
                                        @click="agregarProducto(producto)"
                                        class="px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-0"
                                    >
                                        <div class="font-medium text-gray-900">{{ producto.nombre }}</div>
                                        <div class="text-sm text-gray-500">
                                            Código: {{ producto.codigo || '-' }} | Stock actual: {{ producto.stock_actual || 0 }}
                                            <span v-if="producto.precio_costo" class="ml-2 text-indigo-600">
                                                | Último costo: ${{ formatNumber(producto.precio_costo) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de productos agregados -->
                        <div v-if="form.productos.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">Cantidad</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-40">Precio Unitario</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Subtotal</th>
                                        <th class="px-4 py-3 w-16"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in form.productos" :key="index">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ item.nombre }}</div>
                                            <div class="text-sm text-gray-500">Código: {{ item.codigo || '-' }}</div>
                                            <div v-if="item.costo_anterior" class="text-xs text-indigo-500 mt-0.5">
                                                Costo anterior: ${{ formatNumber(item.costo_anterior) }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input
                                                v-model.number="item.cantidad"
                                                type="number"
                                                min="1"
                                                class="w-full text-center rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="relative">
                                                <span class="absolute left-3 top-2 text-gray-500">$</span>
                                                <input
                                                    v-model.number="item.precio_unitario"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="w-full text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-7"
                                                />
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium">
                                            ${{ formatNumber(item.cantidad * item.precio_unitario) }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button
                                                type="button"
                                                @click="eliminarProducto(index)"
                                                class="text-red-600 hover:text-red-800"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right font-medium text-gray-700">Total:</td>
                                        <td class="px-4 py-3 text-right font-bold text-gray-900">${{ formatNumber(total) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div v-else class="text-center py-8 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p>No hay productos agregados</p>
                            <p class="text-sm">Use el buscador para agregar productos a la recepcion</p>
                        </div>

                        <p v-if="form.errors.productos" class="mt-2 text-sm text-red-600">{{ form.errors.productos }}</p>
                    </div>

                    <!-- Tipo de recepción -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Tipo de Recepción</h3>
                        <div class="flex gap-4">
                            <label 
                                class="flex-1 relative flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer transition-colors"
                                :class="form.tipo === 'total' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'"
                            >
                                <input type="radio" v-model="form.tipo" value="total" class="sr-only" />
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" :class="form.tipo === 'total' ? 'text-green-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="font-medium" :class="form.tipo === 'total' ? 'text-green-800' : 'text-gray-700'">Total</p>
                                    <p class="text-xs" :class="form.tipo === 'total' ? 'text-green-600' : 'text-gray-500'">Se reciben todos los productos de esta compra</p>
                                </div>
                            </label>
                            <label 
                                class="flex-1 relative flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer transition-colors"
                                :class="form.tipo === 'parcial' ? 'border-amber-500 bg-amber-50' : 'border-gray-200 hover:border-gray-300'"
                            >
                                <input type="radio" v-model="form.tipo" value="parcial" class="sr-only" />
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" :class="form.tipo === 'parcial' ? 'text-amber-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="font-medium" :class="form.tipo === 'parcial' ? 'text-amber-800' : 'text-gray-700'">Parcial</p>
                                    <p class="text-xs" :class="form.tipo === 'parcial' ? 'text-amber-600' : 'text-gray-500'">Faltan productos por recibir de esta compra</p>
                                </div>
                            </label>
                        </div>
                        <p v-if="form.errors.tipo" class="mt-1 text-sm text-red-600">{{ form.errors.tipo }}</p>
                    </div>

                    <!-- Observaciones -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Observaciones <span class="text-red-500">*</span>
                        </h3>
                        <textarea
                            v-model="form.observaciones"
                            rows="3"
                            class="w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :class="form.errors.observaciones ? 'border-red-300' : 'border-gray-300'"
                            placeholder="Indique el motivo de la recepción directa (ej: compra urgente, reposición de faltante, etc.)"
                        ></textarea>
                        <p v-if="form.errors.observaciones" class="mt-1 text-sm text-red-600">{{ form.errors.observaciones }}</p>
                        <p v-else class="mt-1 text-xs text-gray-500">Obligatorio — Documente el motivo de esta recepción sin orden de compra.</p>
                    </div>

                    <!-- Acciones -->
                    <div class="flex justify-end gap-3">
                        <Link 
                            :href="route('recepciones.historial')"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || form.productos.length === 0 || !form.observaciones.trim()"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Registrar Recepcion
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    proveedores: Array,
    productos: Array,
});

const form = useForm({
    proveedor_id: null,
    productos: [],
    tipo: 'total',
    observaciones: '',
});

const busqueda = ref('');
const mostrarResultados = ref(false);

const productosFiltrados = computed(() => {
    if (!busqueda.value || busqueda.value.length < 2) return [];
    const term = busqueda.value.toLowerCase();
    return props.productos
        .filter(p => 
            (p.nombre && p.nombre.toLowerCase().includes(term)) ||
            (p.codigo && p.codigo.toLowerCase().includes(term))
        )
        .filter(p => !form.productos.some(item => item.producto_id === p.id))
        .slice(0, 10);
});

const total = computed(() => {
    return form.productos.reduce((sum, item) => sum + (item.cantidad * item.precio_unitario), 0);
});

function agregarProducto(producto) {
    form.productos.push({
        producto_id: producto.id,
        nombre: producto.nombre,
        codigo: producto.codigo,
        cantidad: 1,
        precio_unitario: producto.precio_costo ? parseFloat(producto.precio_costo) : 0,
        costo_anterior: producto.precio_costo ? parseFloat(producto.precio_costo) : null,
    });
    busqueda.value = '';
    mostrarResultados.value = false;
}

function eliminarProducto(index) {
    form.productos.splice(index, 1);
}

function formatNumber(value) {
    return Number(value || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function enviar() {
    form.post(route('recepciones.store-directo'), {
        preserveScroll: true,
    });
}

// Cerrar dropdown al hacer click fuera
document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
        mostrarResultados.value = false;
    }
});
</script>
