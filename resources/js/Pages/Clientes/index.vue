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

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-4 gap-4">
              <button 
                @click="setQuickFilter('todos')"
                :class="quickFilter === 'todos' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="flex items-center justify-between px-4 py-3 rounded-lg text-sm font-medium transition-colors"
              >
                <div class="flex items-center">
                  <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h-4v-7H7V4m0 0L4 7m3-3l3 3m10 3a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                  Total Clientes
                </div>
                <span class="text-lg font-semibold">{{ props.counts.total }}</span>
              </button>

              <button 
                @click="setQuickFilter('activos')"
                :class="quickFilter === 'activos' ? 'bg-green-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="flex items-center justify-between px-4 py-3 rounded-lg text-sm font-medium transition-colors"
              >
                <div class="flex items-center">
                  <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  Activos
                </div>
                <span class="text-lg font-semibold">{{ props.counts.activos }}</span>
              </button>

              <button
                @click="setQuickFilter('mayoristas')"
                :class="quickFilter === 'mayoristas' ? 'bg-purple-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="flex items-center justify-between px-4 py-3 rounded-lg text-sm font-medium transition-colors"
              >
                <div class="flex items-center">
                  <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                  Mayoristas
                </div>
                <span class="text-lg font-semibold">{{ props.counts.mayoristas }}</span>
              </button>

              <button
                @click="setQuickFilter('minoristas')"
                :class="quickFilter === 'minoristas' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="flex items-center justify-between px-4 py-3 rounded-lg text-sm font-medium transition-colors"
              >
                <div class="flex items-center">
                  <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354l.011.001 3.535 3.536A4 4 0 0116.5 9h-3a1 1 0 00-1 1v3.586a1 1 0 00.293.707l3.535 3.536A4 4 0 0119 16.5v-3a1 1 0 00-1-1h-3.586a1 1 0 00-.707.293l-3.536 3.535A4 4 0 017.5 19h3a1 1 0 001-1v-3.586a1 1 0 00-.293-.707l-3.535-3.536A4 4 0 014 7.5v3a1 1 0 001 1h3.586a1 1 0 00.707-.293z"></path></svg>
                  Minoristas
                </div>
                <span class="text-lg font-semibold">{{ props.counts.minoristas }}</span>
              </button>
            </div>

          </div>
        </div>

        <div class="bg-white shadow rounded-lg">
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

          <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 text-xs font-medium text-gray-500 uppercase tracking-wider">
              <div class="col-span-2 flex items-center cursor-pointer hover:text-gray-700" @click="sortBy('nombre')">
                CLIENTE
                <svg v-if="sort.column === 'nombre' && sort.direction === 'asc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                <svg v-if="sort.column === 'nombre' && sort.direction === 'desc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                <svg v-if="sort.column !== 'nombre'" class="ml-1 h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
              </div>
              <div class="flex items-center cursor-pointer hover:text-gray-700" @click="sortBy('tipo_cliente_id')">
                TIPO
                <svg v-if="sort.column === 'tipo_cliente_id' && sort.direction === 'asc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                <svg v-if="sort.column === 'tipo_cliente_id' && sort.direction === 'desc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                <svg v-if="sort.column !== 'tipo_cliente_id'" class="ml-1 h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
              </div>
              <div class="flex items-center cursor-pointer hover:text-gray-700" @click="sortBy('estado_cliente_id')">
                ESTADO
                <svg v-if="sort.column === 'estado_cliente_id' && sort.direction === 'asc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                <svg v-if="sort.column === 'estado_cliente_id' && sort.direction === 'desc'" class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                <svg v-if="sort.column !== 'estado_cliente_id'" class="ml-1 h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
              </div>
              <div class="text-center">CUENTA</div>
              <div class="text-center">ACCIONES</div>
              <div></div>
            </div>
          </div>

          <div class="divide-y divide-gray-200" v-if="clientesDisplay.length">
            <div v-for="cliente in clientesDisplay" :key="cliente.clienteID" class="px-6 py-4 hover:bg-gray-50">
              <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-center">
                <div class="col-span-2 flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full flex items-center justify-center text-white font-medium"
                         :style="{ backgroundColor: getAvatarColor(cliente.nombre) }">
                      {{ getInitials(cliente.nombre, cliente.apellido) }}
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ cliente.nombre }} {{ cliente.apellido }}
                    </div>
                    <div class="text-sm text-gray-500">
                      DNI: {{ cliente.DNI }}<br>
                      WhatApp: {{ cliente.whatsapp }}<br>
                      Email: {{ cliente.mail }}<br>
                      <span v-if="cliente.direccion?.localidad?.provincia?.nombre">
                        Provincia: {{ cliente.direccion.localidad.provincia.nombre }}
                      </span>
                    </div>
                  </div>
                </div>

                <div>
                  <span :class="getTipoBadgeClass(cliente.tipo_cliente?.nombreTipo)" 
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                    {{ cliente.tipo_cliente?.nombreTipo }}
                  </span>
                </div>

                <div>
                  <span :class="getEstadoBadgeClass(cliente.estado_cliente?.nombreEstado)" 
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                    {{ cliente.estado_cliente?.nombreEstado }}
                  </span>
                </div>

                <div class="text-center">
                  <span v-if="cliente.cuenta_corriente_id" class="text-blue-600 hover:text-blue-800 cursor-pointer font-medium">
                    Activa
                  </span>
                  <span v-else class="text-gray-500">
                    Inactiva
                  </span>
                </div>

                <div class="text-center flex justify-center space-x-2">
                  <Link 
                    :href="route('clientes.show', cliente.clienteID)"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                  >
                    Ver
                  </Link>
                  <Link 
                    :href="route('clientes.edit', cliente.clienteID)"
                    class="text-green-600 hover:text-green-800 text-sm font-medium"
                  >
                    Editar
                  </Link>
                  <button @click="confirmDelete(cliente.clienteID)"
                          class="text-red-600 hover:text-red-800 text-sm font-medium">
                    Eliminar
                  </button>
                </div>

                <div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" :checked="cliente.activo" @change="toggleClienteActivo(cliente.clienteID, $event.target.checked)" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-10 text-gray-500">
            <p>No se encontraron clientes que coincidan con los filtros.</p>
            <button @click="clearFilters" class="mt-4 text-blue-600 hover:underline">Limpiar filtros</button>
          </div>

          <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center" v-if="clientes.data.length && clientes.last_page > 1">
            <div class="text-sm text-gray-700">
              Mostrando {{ clientes.from }} a {{ clientes.to }} de {{ clientes.total }} clientes
            </div>
            <div class="flex space-x-1">
              <button v-for="(link, index) in clientes.links" :key="index"
                      @click="goToPage(link.url)"
                      :disabled="!link.url"
                      :class="{ 'bg-blue-600 text-white': link.active, 'bg-gray-200 text-gray-700 hover:bg-gray-300': !link.active && link.url }"
                      class="px-3 py-1 rounded-md text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      v-html="link.label">
              </button>
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
    provincia_id: props.filters.provincia_id || '' // Añadido filtro por provincia
})

const quickFilter = ref('todos') // Para resaltar el botón de filtro rápido activo
const isLoading = ref(false) // Nuevo: estado de carga para feedback visual

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
    isLoading.value = true; // Activar el spinner de carga
    timeout = setTimeout(() => {
        router.get('/clientes', { ...newFilters, sort_column: sort.column, sort_direction: sort.direction }, {
            preserveState: true,
            replace: true,
            onFinish: () => { isLoading.value = false; } // Desactivar el spinner al terminar
        })
    }, 300) // Espera 300ms antes de enviar la petición
}, { deep: true }) // Observa cambios profundos en el objeto formFilters

// Watch para el ordenamiento (también dispara una petición a Inertia)
watch(sort, (newSort) => {
    isLoading.value = true;
    router.get('/clientes', { ...formFilters, sort_column: newSort.column, sort_direction: newSort.direction }, {
        preserveState: true,
        replace: true,
        onFinish: () => { isLoading.value = false; }
    });
}, { deep: true });


// Método para aplicar los filtros rápidos (actualiza formFilters y dispara el watch)
const setQuickFilter = (filter) => {
    quickFilter.value = filter; // Resalta el botón
    // Resetear todos los filtros de estado/tipo/provincia antes de aplicar el rápido
    formFilters.estado_cliente_id = '';
    formFilters.tipo_cliente_id = '';
    formFilters.provincia_id = '';

    if (filter === 'todos') {
        // Nada más que hacer, los filtros ya se resetearon
    } else if (filter === 'activos') {
        const estadoActivo = props.estadosCliente.find(e => e.nombreEstado === 'Activo');
        formFilters.estado_cliente_id = estadoActivo ? estadoActivo.estadoClienteID : '';
    } else if (filter === 'morosos') {
        const estadoMoroso = props.estadosCliente.find(e => e.nombreEstado === 'Moroso');
        formFilters.estado_cliente_id = estadoMoroso ? estadoMoroso.estadoClienteID : '';
    } else if (filter === 'convenio') {
        const estadoConvenio = props.estadosCliente.find(e => e.nombreEstado === 'Convenio');
        formFilters.estado_cliente_id = estadoConvenio ? estadoConvenio.estadoClienteID : '';
    } else if (filter === 'mayoristas') {
        const tipoMayorista = props.tiposCliente.find(t => t.nombreTipo === 'Mayorista');
        formFilters.tipo_cliente_id = tipoMayorista ? tipoMayorista.tipoClienteID : '';
    } else if (filter === 'minoristas') {
        const tipoMinorista = props.tiposCliente.find(t => t.nombreTipo === 'Consumidor Final'); // Ajusta según el nombre real de tu tipo minorista
        formFilters.tipo_cliente_id = tipoMinorista ? tipoMinorista.tipoClienteID : '';
    }
    // El `watch(formFilters)` se encargará de enviar la petición a Inertia
}

// Método para limpiar todos los filtros
const clearFilters = () => {
    formFilters.search = '';
    formFilters.estado_cliente_id = '';
    formFilters.tipo_cliente_id = '';
    formFilters.provincia_id = '';
    quickFilter.value = 'todos'; // Restablece el filtro rápido
    sort.column = 'nombre'; // Restablece el ordenamiento
    sort.direction = 'asc';
    // El `watch` se encargará de enviar la petición con los filtros limpios
}

// Método para ordenar
const sortBy = (column) => {
    if (sort.column === column) {
        sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sort.column = column;
        sort.direction = 'asc'; // Por defecto ascendente cuando se cambia de columna
    }
    // El `watch(sort)` se encargará de enviar la petición a Inertia
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

// Funciones para la paginación (si usas la paginación de Laravel, props.clientes.links, props.clientes.current_page, etc.)
const goToPage = (url) => {
    // router.get(url, formFilters, { preserveState: true, replace: true });
    // Al hacer click en la paginación, queremos mantener los filtros actuales y el ordenamiento
    router.get(url, { ...formFilters, sort_column: sort.column, sort_direction: sort.direction }, { preserveState: true, replace: true });
}

// Función para togglear el estado activo/inactivo del cliente
const toggleClienteActivo = (clienteID, isChecked) => {
    // Aquí puedes llamar a una ruta de Inertia/Laravel para actualizar el estado 'activo' del cliente
    // Por ejemplo:
    router.post(route('clientes.toggleActivo', clienteID), { activo: isChecked }, {
        preserveScroll: true,
        onSuccess: () => {
            // Manejar éxito, por ejemplo, recargando la página o actualizando el cliente en la UI
            router.reload({ preserveState: true });
        },
        onError: (errors) => {
            console.error('Error al actualizar estado activo:', errors);
            // Revertir el estado del toggle en caso de error
            // También podrías mostrar un mensaje de error al usuario
            alert('Hubo un error al cambiar el estado del cliente.');
            router.reload({ preserveState: true }); // Para asegurar que la UI se sincronice
        }
    });
}


// Función para confirmar la eliminación
const confirmDelete = (clienteID) => {
    if (confirm('¿Estás seguro de que quieres eliminar este cliente? Esta acción no se puede deshacer.')) {
        router.delete(route('clientes.destroy', clienteID), {
            onSuccess: () => {
                // Mensaje de éxito o recargar la lista
                alert('Cliente eliminado exitosamente.');
            },
            onError: (errors) => {
                console.error('Error al eliminar cliente:', errors);
                alert('Hubo un error al eliminar el cliente.');
            }
        });
    }
};

// Implementar el método para dar de baja, que es diferente a eliminar
const darDeBajaCliente = (clienteID) => {
    // Esta función debería redirigir a una página de confirmación con motivo
    // o abrir un modal para solicitar el motivo antes de llamar a la ruta darDeBaja.
    // Por ahora, solo redirigimos a la página de confirmación.
    router.get(route('clientes.confirmDelete', clienteID));
};

</script>