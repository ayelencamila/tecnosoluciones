<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue'; 

const props = defineProps({
  producto: Object,
  categorias: Array,
  estados: Array,
  tiposCliente: Array,
  errors: Object, 
});

// Inicializar precios desde los datos del producto
const preciosIniciales = {};
props.tiposCliente.forEach(tipo => {
    const precioActual = props.producto.precios.find(p => p.tipoClienteID == tipo.id && !p.fechaHasta);
    preciosIniciales[tipo.id] = precioActual ? parseFloat(precioActual.precio) : 0;
});

const form = useForm({
  codigo: props.producto.codigo,
  nombre: props.producto.nombre,
  descripcion: props.producto.descripcion || '',
  marca: props.producto.marca || '',
  unidadMedida: props.producto.unidadMedida,
  categoriaProductoID: props.producto.categoriaProductoID,
  estadoProductoID: props.producto.estadoProductoID,
  stockActual: props.producto.stockActual,
  stockMinimo: props.producto.stockMinimo,
  precios: preciosIniciales,
  motivo: '', 
});

// Computada para saber si hay errores de precios
const precioError = computed(() => {
    if (props.errors.precios) return props.errors.precios;
    if (props.errors['precios.*.precio']) return props.errors['precios.*.precio'];
    return null;
});

const submitForm = () => {
  const preciosArray = Object.entries(form.precios)
    .filter(([_, precio]) => precio && precio > 0)
    .map(([tipoClienteID, precio]) => ({
      tipoClienteID: parseInt(tipoClienteID),
      precio: parseFloat(precio)
    }));
  

  form.transform((data) => ({
    ...data,
    precios: preciosArray
  }))
  .put(route('productos.update', props.producto.id)); // Usar route()
};
</script>

<template>
    <Head :title="`Editar ${producto.nombre}`" />
    <AppLayout>
        <!-- Header -->
        <div class="mb-6">
            <Link :href="route('productos.show', producto.id)" class="flex items-center text-gray-600 hover:text-gray-900">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Volver al Producto
            </Link>
            <h1 class="text-3xl font-bold text-gray-900 mt-2">Modificar Producto</h1>
        </div>

        <!-- Formulario -->
        <form @submit.prevent="submitForm" class="space-y-6">
            
            <!-- CAMBIO KENDALL: Pedir Motivo (Requerido por CU-26 y UpdateProductoRequest) -->
            <div class="bg-yellow-50 rounded-lg shadow-sm border border-yellow-300">
                <div class="px-6 py-4 border-b border-yellow-200">
                    <h2 class="text-xl font-semibold text-yellow-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                        Motivo de la Modificación <span class="text-red-500 ml-1">*</span>
                    </h2>
                </div>
                <div class="px-6 py-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Explique brevemente el motivo de los cambios (mín. 5 caracteres)
                    </label>
                    <textarea
                        v-model="form.motivo"
                        required
                        rows="3"
                        class="w-full"
                        :class="{ 'border-red-500': form.errors.motivo }"
                        placeholder="Ej: Actualización de precio por inflación, corrección de stock..."
                    ></textarea>
                    <InputError :message="form.errors.motivo" class="mt-1" />
                </div>
            </div>

            <!-- Información Básica -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-xl font-semibold">Información Básica</h2></div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Código/SKU -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Código/SKU <span class="text-red-500">*</span></label>
                            <input v-model="form.codigo" type="text" required class="w-full" :class="{ 'border-red-500': form.errors.codigo }">
                            <InputError :message="form.errors.codigo" class="mt-1" />
                        </div>
                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre <span class="text-red-500">*</span></label>
                            <input v-model="form.nombre" type="text" required class="w-full" :class="{ 'border-red-500': form.errors.nombre }">
                            <InputError :message="form.errors.nombre" class="mt-1" />
                        </div>
                        <!-- Marca -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                            <input v-model="form.marca" type="text" class="w-full">
                            <InputError :message="form.errors.marca" class="mt-1" />
                        </div>
                        <!-- Unidad de Medida -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Unidad de Medida <span class="text-red-500">*</span></label>
                            <select v-model="form.unidadMedida" required class="w-full" :class="{ 'border-red-500': form.errors.unidadMedida }">
                                <option value="UNIDAD">Unidad</option>
                                <option value="KG">Kilogramo</option>
                                <option value="METRO">Metro</option>
                                <option value="LITRO">Litro</option>
                                <option value="CAJA">Caja</option>
                            </select>
                            <InputError :message="form.errors.unidadMedida" class="mt-1" />
                        </div>
                        <!-- Categoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Categoría <span class="text-red-500">*</span></label>
                            <select v-model="form.categoriaProductoID" required class="w-full" :class="{ 'border-red-500': form.errors.categoriaProductoID }">
                                <option value="">Seleccione...</option>
                                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                            </select>
                            <InputError :message="form.errors.categoriaProductoID" class="mt-1" />
                        </div>
                        <!-- Estado -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado <span class="text-red-500">*</span></label>
                            <select v-model="form.estadoProductoID" required class="w-full" :class="{ 'border-red-500': form.errors.estadoProductoID }">
                                <option v-for="est in estados" :key="est.id" :value="est.id">{{ est.nombre }}</option>
                            </select>
                            <InputError :message="form.errors.estadoProductoID" class="mt-1" />
                        </div>
                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                            <textarea v-model="form.descripcion" rows="3" class="w-full"></textarea>
                            <InputError :message="form.errors.descripcion" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-xl font-semibold">Control de Stock</h2></div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Stock Actual</label>
                            <input v-model.number="form.stockActual" type="number" min="0" class="w-full">
                            <InputError :message="form.errors.stockActual" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Stock Mínimo</label>
                            <input v-model.number="form.stockMinimo" type="number" min="0" class="w-full">
                            <InputError :message="form.errors.stockMinimo" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Precios -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold">Precios por Tipo de Cliente</h2>
                    <p class="mt-1 text-sm text-gray-600">Debe ingresar al menos un precio.</p>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div v-for="tipoCliente in tiposCliente" :key="tipoCliente.id">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ tipoCliente.nombre }}</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">$</span>
                                <input v-model.number="form.precios[tipoCliente.id]" type="number" step="0.01" min="0" class="w-full pl-8">
                            </div>
                        </div>
                    </div>
                    <!-- Error de precios (Kendall) -->
                    <InputError :message="precioError" class="mt-2" />
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3">
                <Link :href="route('productos.show', producto.id)" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                    Cancelar
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 flex items-center"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>{{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}</span>
                </button>
            </div>
        </form>
    </AppLayout>
</template>
