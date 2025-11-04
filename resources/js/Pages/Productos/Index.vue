<template>
  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Encabezado -->
        <div class="sm:flex sm:items-center sm:justify-between">
          <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Catálogo de Productos</h1>
            <p class="mt-2 text-sm text-gray-700">Administra productos, precios y stock del sistema.</p>
          </div>
          <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none space-x-3">
            <Link 
              href="/productos-stock"
              class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
              Consultar Stock
            </Link>
            <Link 
              href="/productos/create"
              class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Nuevo Producto
            </Link>
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
                    <dt class="text-sm font-medium text-gray-500 truncate">Activos</dt>
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

        <!-- Filtros -->
        <div class="bg-white shadow rounded-lg mb-6 mt-6">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Filtros de Búsqueda</h3>
          </div>
          <div class="px-6 py-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
              <div class="sm:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar Producto</label>
                <div class="relative">
                  <input
                    id="search"
                    v-model="formFilters.search"
                    type="text"
                    placeholder="Código, nombre o marca"
                    class="block w-full rounded-md border-gray-300 pl-10 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  />
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                  </div>
                </div>
              </div>

              <div>
                <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                <select
                  id="categoria_id"
                  v-model="formFilters.categoria_id"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                  <option value="">Todas las categorías</option>
                  <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                    {{ cat.nombre }}
                  </option>
                </select>
              </div>

              <div>
                <label for="estado_id" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select
                  id="estado_id"
                  v-model="formFilters.estado_id"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                  <option value="">Todos los estados</option>
                  <option v-for="est in estados" :key="est.id" :value="est.id">
                    {{ est.nombre }}
                  </option>
                </select>
              </div>

              <div class="flex items-end">
                <label class="flex items-center">
                  <input
                    type="checkbox"
                    v-model="formFilters.stock_bajo"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                  />
                  <span class="ml-2 text-sm text-gray-700">Solo stock bajo</span>
                </label>
              </div>

              <div class="flex items-end justify-end">
                <button 
                  @click="clearFilters" 
                  v-if="hasActiveFilters"
                  class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md text-sm transition-colors"
                >
                  Limpiar Filtros
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla de Productos -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Código
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Producto
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Categoría
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Stock
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Estado
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Acciones
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
                    {{ producto.stockActual }} {{ producto.unidadMedida }}
                  </span>
                  <span v-if="producto.stockActual <= producto.stockMinimo" class="ml-1 text-xs text-red-600">
                    (Mín: {{ producto.stockMinimo }})
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="getEstadoBadgeClass(producto.estado?.nombre)"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ producto.estado?.nombre }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <Link 
                    :href="`/productos/${producto.id}`"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Ver
                  </Link>
                  <Link 
                    :href="`/productos/${producto.id}/edit`"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Editar
                  </Link>
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
  estado_id: props.filters?.estado_id || '',
  stock_bajo: props.filters?.stock_bajo || false,
  sort_column: props.filters?.sort_column || 'nombre',
  sort_direction: props.filters?.sort_direction || 'asc',
});

const hasActiveFilters = computed(() => {
  return formFilters.value.search || 
         formFilters.value.categoria_id || 
         formFilters.value.estado_id ||
         formFilters.value.stock_bajo;
});

watch(formFilters, (newFilters) => {
  router.get('/productos', newFilters, {
    preserveState: true,
    preserveScroll: true,
  });
}, { deep: true });

const clearFilters = () => {
  formFilters.value = {
    search: '',
    categoria_id: '',
    estado_id: '',
    stock_bajo: false,
    sort_column: 'nombre',
    sort_direction: 'asc',
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
