<template>
  <AppLayout>
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-4">
          <Link 
            :href="`/productos/${producto.id}`"
            class="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al Producto
          </Link>
        </div>
      </div>

      <div>
        <h1 class="text-3xl font-bold text-gray-900">Modificar Producto</h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ producto.codigo }} - {{ producto.nombre }}
        </p>
      </div>
    </div>

    <!-- Formulario -->
    <form @submit.prevent="submitForm" class="space-y-6">
      <!-- Información Básica -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            Información Básica
          </h2>
        </div>
        <div class="px-6 py-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Código/SKU -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Código/SKU <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.codigo"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.codigo }"
              >
              <p v-if="errors.codigo" class="mt-1 text-sm text-red-600">{{ errors.codigo }}</p>
            </div>

            <!-- Nombre -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Nombre del Producto <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.nombre"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="{ 'border-red-500': errors.nombre }"
              >
              <p v-if="errors.nombre" class="mt-1 text-sm text-red-600">{{ errors.nombre }}</p>
            </div>

            <!-- Marca -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Marca
              </label>
              <input
                v-model="form.marca"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              >
            </div>

            <!-- Unidad de Medida -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Unidad de Medida <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.unidadMedida"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="UNIDAD">Unidad</option>
                <option value="KG">Kilogramo</option>
                <option value="METRO">Metro</option>
                <option value="LITRO">Litro</option>
                <option value="CAJA">Caja</option>
              </select>
            </div>

            <!-- Categoría -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Categoría <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.categoriaProductoID"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                  {{ cat.nombre }} ({{ cat.tipoProducto }})
                </option>
              </select>
            </div>

            <!-- Estado -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Estado <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.estadoProductoID"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option v-for="est in estados" :key="est.id" :value="est.id">
                  {{ est.nombre }}
                </option>
              </select>
            </div>

            <!-- Descripción -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Descripción
              </label>
              <textarea
                v-model="form.descripcion"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              ></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Stock -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
            </svg>
            Control de Stock
          </h2>
        </div>
        <div class="px-6 py-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Stock Actual
              </label>
              <input
                v-model.number="form.stockActual"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Stock Mínimo
              </label>
              <input
                v-model.number="form.stockMinimo"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Precios -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Precios por Tipo de Cliente
          </h2>
        </div>
        <div class="px-6 py-4">
          <div class="space-y-4">
            <div v-for="tipoCliente in tiposCliente" :key="tipoCliente.id" class="flex items-center space-x-4">
              <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ tipoCliente.nombre }}
                </label>
                <div class="relative">
                  <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                  <input
                    v-model.number="form.precios[tipoCliente.id]"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Motivo de Modificación -->
      <div class="bg-yellow-50 rounded-lg shadow-sm border border-yellow-200">
        <div class="px-6 py-4 border-b border-yellow-200">
          <h2 class="text-xl font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            Motivo de la Modificación
          </h2>
        </div>
        <div class="px-6 py-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Explique brevemente el motivo de los cambios <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.motivo"
            required
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
            :class="{ 'border-red-500': errors.motivo }"
            placeholder="Ej: Actualización de precio por inflación, corrección de stock por inventario..."
          ></textarea>
          <p v-if="errors.motivo" class="mt-1 text-sm text-red-600">{{ errors.motivo }}</p>
          <p class="mt-1 text-xs text-gray-500">Mínimo 5 caracteres. Este motivo quedará registrado en el historial de auditoría.</p>
        </div>
      </div>

      <!-- Botones -->
      <div class="flex justify-end space-x-3">
        <Link 
          :href="`/productos/${producto.id}`"
          class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          Cancelar
        </Link>
        <button
          type="submit"
          :disabled="processing"
          class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
        >
          <span v-if="processing">Guardando...</span>
          <span v-else>Guardar Cambios</span>
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  producto: Object,
  categorias: Array,
  estados: Array,
  tiposCliente: Array,
  errors: Object,
});

// Inicializar precios desde los datos del producto
const preciosIniciales = {};
props.producto.precios?.forEach(precio => {
  preciosIniciales[precio.tipo_cliente_id || precio.tipoClienteID] = parseFloat(precio.precio);
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

const processing = ref(false);

const submitForm = () => {
  const preciosArray = Object.entries(form.precios)
    .filter(([_, precio]) => precio && precio > 0)
    .map(([tipoClienteID, precio]) => ({
      tipoClienteID: parseInt(tipoClienteID),
      precio: parseFloat(precio)
    }));

  if (preciosArray.length === 0) {
    alert('Debe ingresar al menos un precio');
    return;
  }

  if (!form.motivo || form.motivo.length < 5) {
    alert('Debe ingresar un motivo de modificación (mínimo 5 caracteres)');
    return;
  }

  processing.value = true;
  
  form.transform((data) => ({
    ...data,
    precios: preciosArray
  })).put(`/productos/${props.producto.id}`, {
    onFinish: () => {
      processing.value = false;
    },
  });
};
</script>
