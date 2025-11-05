<template>
  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between">
          <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Gestión de Clientes</h1>
            <p class="mt-2 text-sm text-gray-700">Administra la información de todos los clientes registrados en el sistema.</p>
          </div>
          <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <Link 
              href="/clientes/create"
              class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto"
            >
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Nuevo Cliente
            </Link>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg mb-6 mt-6">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Filtros de Búsqueda</h3>
          </div>
          <div class="px-6 py-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
              <div class="sm:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar Cliente</label>
                <div class="relative">
                  <input
                    id="search"
                    v-model="formFilters.search"
                    type="text"
                    placeholder="Nombre, DNI o WhatsApp"
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
                <label for="tipo_cliente_id" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cliente</label>
                <select
                  id="tipo_cliente_id"
                  v-model="formFilters.tipo_cliente_id"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                  <option value="">Todos los tipos</option>
                  <option v-for="tipo in tiposCliente" :key="tipo.tipoClienteID" :value="tipo.tipoClienteID">
                    {{ tipo.nombreTipo }}
                  </option>
                </select>
              </div>

              <div>
                <label for="estado_cliente_id" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select
                  id="estado_cliente_id"
                  v-model="formFilters.estado_cliente_id"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                  <option value="">Todos los estados</option>
                  <option v-for="estado in estadosCliente" :key="estado.estadoClienteID" :value="estado.estadoClienteID">
                    {{ estado.nombreEstado }}
                  </option>
                </select>
              </div>

              <div>
                <label for="provincia_id" class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                <select
                  id="provincia_id"
                  v-model="formFilters.provincia_id"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                  <option value="">Todas las provincias</option>
                  <option v-for="provincia in provincias" :key="provincia.provinciaID" :value="provincia.provinciaID">
                    {{ provincia.nombre }}
                  </option>
                </select>
              </div>

               <div class="flex items-end justify-end sm:col-span-1">
                  <button @click="clearFilters" v-if="hasActiveFilters"
                      class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md text-sm transition-colors">
                      Limpiar Filtros
                  </button>
              </div>
            </div>



          </div>
        </div>

        <!-- Estadísticas -->
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-4">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Clientes</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ counts?.total || 0 }}</dd>
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
                    <dd class="text-lg font-semibold text-gray-900">{{ counts?.activos || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Mayoristas</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ counts?.mayoristas || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Minoristas</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ counts?.minoristas || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Lista de Clientes -->
        <div class="bg-white shadow rounded-lg mt-6 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Lista de Clientes</h3>
             <div v-if="isLoading" class="flex items-center text-blue-600">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Cargando...
            </div>
          </div>

          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('nombre')">
                  <div class="flex items-center">
                    Cliente
                    <svg v-if="sort.column === 'nombre' && sort.direction === 'asc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                    <svg v-else-if="sort.column === 'nombre' && sort.direction === 'desc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    <svg v-else class="ml-1 h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                  </div>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Contacto
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('tipo_cliente_id')">
                  <div class="flex items-center">
                    Tipo
                    <svg v-if="sort.column === 'tipo_cliente_id' && sort.direction === 'asc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                    <svg v-else-if="sort.column === 'tipo_cliente_id' && sort.direction === 'desc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    <svg v-else class="ml-1 h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                  </div>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('estado_cliente_id')">
                  <div class="flex items-center">
                    Estado
                    <svg v-if="sort.column === 'estado_cliente_id' && sort.direction === 'asc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                    <svg v-else-if="sort.column === 'estado_cliente_id' && sort.direction === 'desc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    <svg v-else class="ml-1 h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                  </div>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Ubicación
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="!clientesDisplay.length">
                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                  <div class="flex flex-col items-center">
                    <svg class="h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p class="text-sm font-medium">No se encontraron clientes</p>
                    <button @click="clearFilters" class="mt-2 text-blue-600 hover:underline text-sm">Limpiar filtros</button>
                  </div>
                </td>
              </tr>
              <tr v-for="cliente in clientesDisplay" :key="cliente.clienteID" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div class="h-10 w-10 rounded-full flex items-center justify-center text-white font-medium text-sm"
                           :style="{ backgroundColor: getAvatarColor(cliente.nombre) }">
                        {{ getInitials(cliente.nombre, cliente.apellido) }}
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">
                        {{ cliente.nombre }} {{ cliente.apellido }}
                      </div>
                      <div class="text-sm text-gray-500">
                        DNI: {{ cliente.DNI }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ cliente.whatsapp }}</div>
                  <div class="text-sm text-gray-500">{{ cliente.mail }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="getTipoBadgeClass(cliente.tipo_cliente?.nombreTipo)" 
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ cliente.tipo_cliente?.nombreTipo }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="getEstadoBadgeClass(cliente.estado_cliente?.nombreEstado)" 
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ cliente.estado_cliente?.nombreEstado }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div v-if="cliente.direccion?.localidad?.provincia?.nombre">
                    {{ cliente.direccion.localidad.provincia.nombre }}
                  </div>
                  <div v-else class="text-gray-400">-</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <Link 
                    :href="route('clientes.show', cliente.clienteID)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Ver
                  </Link>
                  <Link 
                    :href="route('clientes.edit', cliente.clienteID)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Editar
                  </Link>
                  <button 
                    @click="confirmDelete(cliente.clienteID)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Eliminar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Paginación -->
          <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
              <Link
                v-if="clientes?.prev_page_url"
                :href="clientes.prev_page_url"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
              >
                Anterior
              </Link>
              <Link
                v-if="clientes?.next_page_url"
                :href="clientes.next_page_url"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
              >
                Siguiente
              </Link>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Mostrando
                  <span class="font-medium">{{ clientes?.from || 0 }}</span>
                  a
                  <span class="font-medium">{{ clientes?.to || 0 }}</span>
                  de
                  <span class="font-medium">{{ clientes?.total || 0 }}</span>
                  clientes
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                  <template v-for="link in clientes?.links || []" :key="link.label">
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

        <div class="mt-6 text-center text-sm text-gray-500">
          © 2025 TecnoSoluciones. Sistema de Gestión Integral. v1.0.0 - Laravel v11.30.5
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

// Props que vienen de Laravel
const props = defineProps({
    clientes: Object, // Ahora es un objeto con data, links, etc., debido a la paginación
    estadosCliente: Array,
    tiposCliente: Array,
    provincias: Array, // Nuevo prop para provincias
    filters: Object, // Incluye search, tipo_cliente_id, estado_cliente_id, provincia_id
    counts: Object, // Nuevo prop para los contadores (lo implementaremos en el backend a continuación)
})

// Estado reactivo para los filtros
const formFilters = reactive({ // Renombrado a formFilters para evitar conflicto con props.filters
    search: props.filters.search || '',
    estado_cliente_id: props.filters.estado_cliente_id || '',
    tipo_cliente_id: props.filters.tipo_cliente_id || '',
    provincia_id: props.filters.provincia_id || ''
})

const isLoading = ref(false)

// Estado para el ordenamiento
const sort = reactive({
    column: props.filters.sort_column || 'nombre', // Usar el ordenamiento de los filtros si existe
    direction: props.filters.sort_direction || 'asc'
});

// Computed para clientes (ya no necesitamos clientesEjemplo, usamos props.clientes.data)
const clientesDisplay = computed(() => {
    return props.clientes.data
})

// Computed para verificar si hay filtros activos (para el botón "Limpiar Filtros")
const hasActiveFilters = computed(() => {
    return formFilters.search !== '' || 
           formFilters.estado_cliente_id !== '' || 
           formFilters.tipo_cliente_id !== '' || 
           formFilters.provincia_id !== '' || // Incluir provincia
           sort.column !== 'nombre' || // Considerar el ordenamiento por defecto
           sort.direction !== 'asc'; // Considerar el ordenamiento por defecto
});

// Debounce para la búsqueda y aplicación de filtros
let timeout = null
watch(formFilters, (newFilters) => {
    clearTimeout(timeout)
    isLoading.value = true
    timeout = setTimeout(() => {
        router.get('/clientes', { ...newFilters, sort_column: sort.column, sort_direction: sort.direction }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => { isLoading.value = false; }
        })
    }, 300)
}, { deep: true })

// Watch para el ordenamiento
watch(sort, (newSort) => {
    isLoading.value = true
    router.get('/clientes', { ...formFilters, sort_column: newSort.column, sort_direction: newSort.direction }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { isLoading.value = false; }
    })
}, { deep: true })

// Método para limpiar todos los filtros
const clearFilters = () => {
    formFilters.search = ''
    formFilters.estado_cliente_id = ''
    formFilters.tipo_cliente_id = ''
    formFilters.provincia_id = ''
    sort.column = 'nombre'
    sort.direction = 'asc'
}

// Método para ordenar
const sortBy = (column) => {
    if (sort.column === column) {
        sort.direction = sort.direction === 'asc' ? 'desc' : 'asc'
    } else {
        sort.column = column
        sort.direction = 'asc'
    }
}

// Métodos auxiliares
const getInitials = (nombre, apellido) => {
    return `${nombre?.charAt(0) || ''}${apellido?.charAt(0) || ''}`.toUpperCase()
}

const getAvatarColor = (nombre) => {
    const colors = ['#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5A2B', '#E7E5E4', '#93C5FD'] // Añadí más colores
    const index = nombre?.charCodeAt(0) % colors.length || 0
    return colors[index]
}

const getTipoBadgeClass = (tipo) => {
    const classes = {
        'Consumidor Final': 'bg-blue-100 text-blue-800', // Ajusté el nombre del tipo
        'Mayorista': 'bg-purple-100 text-purple-800',
        'Distribuidor': 'bg-yellow-100 text-yellow-800'
    }
    return classes[tipo] || 'bg-gray-100 text-gray-800'
}

const getEstadoBadgeClass = (estado) => {
    const classes = {
        'Activo': 'bg-green-100 text-green-800',
        'Inactivo': 'bg-gray-100 text-gray-800',
        'Moroso': 'bg-red-100 text-red-800',
        'Bloqueado': 'bg-gray-100 text-gray-800',
        'Convenio': 'bg-orange-100 text-orange-800', // Asumo que "Convenio" es un estado
    }
    return classes[estado] || 'bg-gray-100 text-gray-800'
}

// Función para confirmar la eliminación
const confirmDelete = (clienteID) => {
    if (confirm('¿Estás seguro de que quieres eliminar este cliente? Esta acción no se puede deshacer.')) {
        router.delete(route('clientes.destroy', clienteID), {
            onSuccess: () => {
                alert('Cliente eliminado exitosamente.')
            },
            onError: (errors) => {
                console.error('Error al eliminar cliente:', errors)
                alert('Hubo un error al eliminar el cliente.')
            }
        })
    }
}

</script>