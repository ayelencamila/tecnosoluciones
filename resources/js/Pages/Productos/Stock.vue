<template>
  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Encabezado -->
        <div class="sm:flex sm:items-center sm:justify-between">
          <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Consulta de Stock</h1>
            <p class="mt-2 text-sm text-gray-700">Consulta el stock actual de productos por categoría o búsqueda.</p>
          </div>
          <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <Link 
              href="/productos"
              class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Volver a Productos
            </Link>
          </div>
        </div>

        <!-- Filtros de búsqueda -->
        <div class="mt-6 bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Criterios de Búsqueda</h3>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <!-- Búsqueda por nombre/código -->
            <div>
              <label for="search" class="block text-sm font-medium text-gray-700">Código/SKU o Nombre</label>
              <input
                type="text"
                id="search"
                v-model="formFilters.search"
                placeholder="Buscar producto..."
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
            </div>

            <!-- Filtro por categoría -->
            <div>
              <label for="categoria" class="block text-sm font-medium text-gray-700">Categoría</label>
              <select
                id="categoria"
                v-model="formFilters.categoria_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="">Todas las categorías</option>
                <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
                  {{ categoria.nombre }}
                </option>
              </select>
            </div>

            <!-- Filtro stock bajo -->
            <div class="flex items-end">
              <label class="inline-flex items-center">
                <input
                  type="checkbox"
                  v-model="formFilters.stock_bajo"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-700">Solo productos con stock bajo</span>
              </label>
            </div>
          </div>

          <!-- Botón limpiar filtros -->
          <div class="mt-4 flex justify-end" v-if="hasActiveFilters">
            <button
              @click="clearFilters"
              type="button"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
              Limpiar filtros
            </button>
          </div>
        </div>

        <!-- Estadísticas -->
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-3">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Productos</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ stats?.total || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Con Stock</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ stats?.activos || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Stock Bajo</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ stats?.stockBajo || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla de Stock -->
        <div class="mt-6 bg-white shadow rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Código/SKU
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Producto
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Categoría
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Stock Actual
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Stock Mínimo
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Unidad
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Estado
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="producto in productos?.data || []" :key="producto.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ producto.codigo }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ producto.nombre }}</div>
                  <div class="text-sm text-gray-500">{{ producto.marca }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ producto.categoria?.nombre }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="producto.stockActual <= producto.stockMinimo ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ producto.stockActual }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ producto.stockMinimo }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ producto.unidadMedida }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="getEstadoBadgeClass(producto.estado?.nombre)"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ producto.estado?.nombre }}
                  </span>
                </td>
              </tr>
              <tr v-if="(!productos?.data || productos.data.length === 0)">
                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                  No se encontraron existencias de productos que coincidan con los criterios. Por favor, ajuste los criterios para una nueva búsqueda.
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Paginación -->
          <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
              <Link
                v-if="productos?.prev_page_url"
                :href="productos.prev_page_url"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
              >
                Anterior
              </Link>
              <Link
                v-if="productos?.next_page_url"
                :href="productos.next_page_url"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
              >
                Siguiente
              </Link>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Mostrando
                  <span class="font-medium">{{ productos?.from || 0 }}</span>
                  a
                  <span class="font-medium">{{ productos?.to || 0 }}</span>
                  de
                  <span class="font-medium">{{ productos?.total || 0 }}</span>
                  productos
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                  <template v-for="link in productos?.links || []" :key="link.label">
                    <Link
                      v-if="link.url"
                      :href="link.url"
                      :class="[
                        link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                      ]"
                      v-html="link.label"
                    />
                    <span
                      v-else
                      :class="[
                        'bg-white border-gray-300 text-gray-400 cursor-not-allowed',
                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                      ]"
                      v-html="link.label"
                    ></span>
                  </template>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  productos: Object,
  categorias: Array,
  estados: Array,
  filters: Object,
  stats: Object,
});

const formFilters = ref({
  search: props.filters?.search || '',
  categoria_id: props.filters?.categoria_id || '',
  stock_bajo: props.filters?.stock_bajo || false,
});

const hasActiveFilters = computed(() => {
  return formFilters.value.search || 
         formFilters.value.categoria_id || 
         formFilters.value.stock_bajo;
});

watch(formFilters, (newFilters) => {
  router.get('/productos-stock', newFilters, {
    preserveState: true,
    preserveScroll: true,
  });
}, { deep: true });

const clearFilters = () => {
  formFilters.value = {
    search: '',
    categoria_id: '',
    stock_bajo: false,
  };
};

const getEstadoBadgeClass = (estado) => {
  const classes = {
    'Activo': 'bg-green-100 text-green-800',
    'Inactivo': 'bg-gray-100 text-gray-800',
    'Descontinuado': 'bg-red-100 text-red-800',
  };
  return classes[estado] || 'bg-gray-100 text-gray-800';
};
</script>
