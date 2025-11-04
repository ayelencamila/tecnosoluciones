<template>
  <AppLayout>
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-4">
          <Link 
            href="/productos"
            class="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al Catálogo
          </Link>
        </div>
      </div>

      <div>
        <h1 class="text-3xl font-bold text-gray-900">Registrar Nuevo Producto</h1>
        <p class="mt-1 text-sm text-gray-600">
          Complete los datos del producto para agregarlo al catálogo
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
                placeholder="Ej: NB-LEN-T14"
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
                placeholder="Ej: Lenovo ThinkPad T14"
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
                placeholder="Ej: Lenovo"
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
                :class="{ 'border-red-500': errors.unidadMedida }"
              >
                <option value="">Seleccione...</option>
                <option value="UNIDAD">Unidad</option>
                <option value="KG">Kilogramo</option>
                <option value="METRO">Metro</option>
                <option value="LITRO">Litro</option>
                <option value="CAJA">Caja</option>
              </select>
              <p v-if="errors.unidadMedida" class="mt-1 text-sm text-red-600">{{ errors.unidadMedida }}</p>
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
                :class="{ 'border-red-500': errors.categoriaProductoID }"
              >
                <option value="">Seleccione una categoría...</option>
                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                  {{ cat.nombre }} ({{ cat.tipoProducto }})
                </option>
              </select>
              <p v-if="errors.categoriaProductoID" class="mt-1 text-sm text-red-600">{{ errors.categoriaProductoID }}</p>
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
                :class="{ 'border-red-500': errors.estadoProductoID }"
              >
                <option value="">Seleccione un estado...</option>
                <option v-for="est in estados" :key="est.id" :value="est.id">
                  {{ est.nombre }}
                </option>
              </select>
              <p v-if="errors.estadoProductoID" class="mt-1 text-sm text-red-600">{{ errors.estadoProductoID }}</p>
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
                placeholder="Descripción detallada del producto..."
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
            <!-- Stock Actual -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Stock Actual
              </label>
              <input
                v-model.number="form.stockActual"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="0"
              >
              <p class="mt-1 text-xs text-gray-500">Cantidad disponible en depósito</p>
            </div>

            <!-- Stock Mínimo -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Stock Mínimo
              </label>
              <input
                v-model.number="form.stockMinimo"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="0"
              >
              <p class="mt-1 text-xs text-gray-500">Nivel de alerta para reposición</p>
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
          <p class="mt-1 text-sm text-gray-600">Debe ingresar al menos un precio</p>
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
                    placeholder="0.00"
                  >
                </div>
              </div>
            </div>
          </div>
          <p v-if="errors.precios" class="mt-2 text-sm text-red-600">{{ errors.precios }}</p>
        </div>
      </div>

      <!-- Botones -->
      <div class="flex justify-end space-x-3">
        <Link 
          href="/productos"
          class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Cancelar
        </Link>
        <button
          type="submit"
          :disabled="processing"
          class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <span v-if="processing">Registrando...</span>
          <span v-else>Registrar Producto</span>
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  categorias: Array,
  estados: Array,
  tiposCliente: Array,
  errors: Object,
});

const form = useForm({
  codigo: '',
  nombre: '',
  descripcion: '',
  marca: '',
  unidadMedida: '',
  categoriaProductoID: '',
  estadoProductoID: props.estados.find(e => e.nombre === 'Activo')?.id || '',
  stockActual: 0,
  stockMinimo: 0,
  precios: {},
});

const processing = ref(false);

const submitForm = () => {
  // Convertir precios a array para el backend
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

  processing.value = true;
  
  form.transform((data) => ({
    ...data,
    precios: preciosArray
  })).post('/productos', {
    onFinish: () => {
      processing.value = false;
    },
  });
};
</script>
