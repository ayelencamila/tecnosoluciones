<template>
  <AppLayout>
    <!-- Header Section Mejorado -->
    <div class="mb-8">
      <!-- Breadcrumb y título principal -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <nav class="flex items-center text-sm text-gray-500 mb-2">
            <Link href="/" class="hover:text-gray-700 transition-colors">Inicio</Link>
            <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
            <span class="text-gray-900 font-medium">Clientes</span>
          </nav>
          <div class="flex items-center space-x-3">
            <div class="flex items-center">
              <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
              <div>
                <h1 class="text-3xl font-bold text-gray-900">Gestión de Clientes</h1>
                <p class="text-gray-600 mt-1">Administra la información de todos los clientes registrados en el sistema</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Botón de acción principal -->
        <Link 
          href="/clientes/create"
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg flex items-center shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Nuevo Cliente
        </Link>
      </div>

      <!-- Métricas y KPIs Mejorados -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total Clientes</p>
              <p class="text-2xl font-bold text-gray-900">{{ clienteStore.stats.total || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Activos</p>
              <p class="text-2xl font-bold text-gray-900">{{ clienteStore.stats.activos || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Mayoristas</p>
              <p class="text-2xl font-bold text-gray-900">{{ clienteStore.stats.mayoristas || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Minoristas</p>
              <p class="text-2xl font-bold text-gray-900">{{ clienteStore.stats.minoristas || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel de Filtros Avanzados -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"></path>
            </svg>
            Filtros de Búsqueda
          </h3>
          <div class="flex items-center space-x-4">
            <span v-if="activeFiltersCount > 0" class="text-sm text-gray-600">
              {{ activeFiltersCount }} filtro{{ activeFiltersCount === 1 ? '' : 's' }} activo{{ activeFiltersCount === 1 ? '' : 's' }}
            </span>
            <button 
              v-if="hasActiveFilters"
              @click="clearAllFilters"
              class="text-sm text-gray-600 hover:text-gray-900 flex items-center transition-colors"
            >
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
              Limpiar filtros
            </button>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Búsqueda general -->
          <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Cliente</label>
            <div class="relative">
              <input
                v-model="localFilters.search"
                @input="debouncedFilter"
                type="text"
                placeholder="Nombre, DNI o WhatsApp..."
                class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
              >
              <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
              <div v-if="localFilters.search" class="absolute right-3 top-3.5">
                <button @click="clearSearch" class="text-gray-400 hover:text-gray-600 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Tipo de Cliente -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Cliente</label>
            <select
              v-model="localFilters.tipo_cliente_id"
              @change="applyFilters"
              class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
            >
              <option value="">Todos los tipos</option>
              <option v-for="tipo in tiposCliente" :key="tipo.tipoClienteID" :value="tipo.tipoClienteID">
                {{ tipo.nombreTipo }}
              </option>
            </select>
          </div>

          <!-- Estado -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
            <select
              v-model="localFilters.estado_cliente_id"
              @change="applyFilters"
              class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
            >
              <option value="">Todos los estados</option>
              <option v-for="estado in estadosCliente" :key="estado.estadoClienteID" :value="estado.estadoClienteID">
                {{ estado.nombreEstado }}
              </option>
            </select>
          </div>
        </div>

        <!-- Filtros adicionales expandibles -->
        <div v-if="showAdvancedFilters" class="mt-6 pt-6 border-t border-gray-200">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Provincia -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Provincia</label>
              <select
                v-model="localFilters.provincia_id"
                @change="onProvinciaFilterChange"
                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
              >
                <option value="">Todas las provincias</option>
                <option v-for="provincia in provincias" :key="provincia.provinciaID" :value="provincia.provinciaID">
                  {{ provincia.nombre }}
                </option>
              </select>
            </div>

            <!-- Localidad -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Localidad</label>
              <select
                v-model="localFilters.localidad_id"
                @change="applyFilters"
                :disabled="!localFilters.provincia_id || loadingLocalidades"
                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors disabled:bg-gray-100"
              >
                <option value="">{{ loadingLocalidades ? 'Cargando...' : 'Todas las localidades' }}</option>
                <option v-for="localidad in localidadesFiltro" :key="localidad.localidadID" :value="localidad.localidadID">
                  {{ localidad.nombre }}
                </option>
              </select>
            </div>

            <!-- Ordenar por -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Ordenar por</label>
              <select
                v-model="localFilters.sortBy"
                @change="applyFilters"
                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
              >
                <option value="nombre">Nombre A-Z</option>
                <option value="apellido">Apellido A-Z</option>
                <option value="created_at_desc">Más recientes</option>
                <option value="created_at_asc">Más antiguos</option>
                <option value="tipo_cliente">Tipo de cliente</option>
                <option value="estado">Estado</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Toggle para filtros avanzados -->
        <div class="mt-4 text-center">
          <button 
            @click="showAdvancedFilters = !showAdvancedFilters"
            class="text-sm text-indigo-600 hover:text-indigo-700 flex items-center mx-auto transition-colors"
          >
            <span>{{ showAdvancedFilters ? 'Ocultar' : 'Mostrar' }} filtros avanzados</span>
            <svg 
              class="w-4 h-4 ml-1 transition-transform duration-200"
              :class="{ 'rotate-180': showAdvancedFilters }"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Tabla de Clientes Mejorada -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <!-- Header de la tabla con herramientas -->
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900">Lista de Clientes</h3>
            <span class="text-sm text-gray-500">
              Mostrando {{ clienteStore.clientes.length }} de {{ clienteStore.stats.total || 0 }} clientes
            </span>
          </div>
          
          <div class="flex items-center space-x-3">
            <!-- Selector de registros por página -->
            <div class="flex items-center space-x-2">
              <label class="text-sm text-gray-600">Mostrar:</label>
              <select
                v-model="perPage"
                @change="changePerPage"
                class="px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <span class="text-sm text-gray-600">por página</span>
            </div>

            <!-- Botón de actualizar -->
            <button 
              @click="refreshData"
              :disabled="loading"
              class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-50"
              title="Actualizar datos"
            >
              <svg 
                class="w-5 h-5"
                :class="{ 'animate-spin': loading }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla responsive -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                <button 
                  @click="toggleSort('nombre')"
                  class="flex items-center space-x-1 hover:text-indigo-600 transition-colors"
                >
                  <span>Cliente</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                  </svg>
                </button>
              </th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Contacto
              </th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                <button 
                  @click="toggleSort('tipo_cliente')"
                  class="flex items-center space-x-1 hover:text-indigo-600 transition-colors"
                >
                  <span>Tipo</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                  </svg>
                </button>
              </th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                <button 
                  @click="toggleSort('estado')"
                  class="flex items-center space-x-1 hover:text-indigo-600 transition-colors"
                >
                  <span>Estado</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                  </svg>
                </button>
              </th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Localidad
              </th>
              <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <!-- Loading state -->
            <tr v-if="loading">
              <td colspan="6" class="px-6 py-12 text-center">
                <div class="flex items-center justify-center">
                  <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span class="ml-3 text-gray-600">Cargando clientes...</span>
                </div>
              </td>
            </tr>

            <!-- Empty state -->
            <tr v-else-if="clienteStore.clientes.length === 0">
              <td colspan="6" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                  <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron clientes</h3>
                  <p class="text-gray-600 mb-6">
                    {{ hasActiveFilters ? 'Intenta ajustar los filtros de búsqueda' : 'Comienza agregando tu primer cliente' }}
                  </p>
                  <Link 
                    href="/clientes/create"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors"
                  >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nuevo Cliente
                  </Link>
                </div>
              </td>
            </tr>

            <!-- Filas de clientes -->
            <tr 
              v-else 
              v-for="cliente in clienteStore.clientes" 
              :key="cliente.clienteID"
              class="hover:bg-gray-50 transition-colors"
            >
              <!-- Cliente info -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-12 w-12">
                    <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center">
                      <span class="text-sm font-medium text-indigo-800">
                        {{ getInitials(cliente.nombre, cliente.apellido) }}
                      </span>
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

              <!-- Contacto -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  <div v-if="cliente.mail" class="flex items-center mb-1">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    {{ cliente.mail }}
                  </div>
                  <div v-if="cliente.telefono" class="flex items-center mb-1">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    {{ cliente.telefono }}
                  </div>
                  <div v-if="cliente.whatsapp" class="flex items-center">
                    <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                    </svg>
                    {{ cliente.whatsapp }}
                  </div>
                  <div v-if="!cliente.mail && !cliente.telefono && !cliente.whatsapp" class="text-gray-400 italic">
                    Sin contacto
                  </div>
                </div>
              </td>

              <!-- Tipo -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  class="inline-flex px-3 py-1 text-xs font-semibold rounded-full"
                  :class="getTipoBadgeClass(cliente.tipo_cliente?.nombreTipo)"
                >
                  {{ cliente.tipo_cliente?.nombreTipo || 'N/A' }}
                </span>
              </td>

              <!-- Estado -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  class="inline-flex px-3 py-1 text-xs font-semibold rounded-full"
                  :class="getEstadoBadgeClass(cliente.estado_cliente?.nombreEstado)"
                >
                  {{ cliente.estado_cliente?.nombreEstado || 'N/A' }}
                </span>
              </td>

              <!-- Localidad -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div v-if="cliente.direccion?.localidad">
                  <div class="font-medium">{{ cliente.direccion.localidad.nombre }}</div>
                  <div class="text-xs text-gray-500">{{ cliente.direccion.localidad.provincia?.nombre }}</div>
                </div>
                <div v-else class="text-gray-400 italic">No disponible</div>
              </td>

              <!-- Acciones -->
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <!-- Ver -->
                  <Link 
                    :href="`/clientes/${cliente.clienteID}`"
                    class="text-indigo-600 hover:text-indigo-900 p-2 rounded-lg hover:bg-indigo-50 transition-colors"
                    title="Ver detalles"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                  </Link>

                  <!-- Editar -->
                  <Link 
                    :href="`/clientes/${cliente.clienteID}/edit`"
                    class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition-colors"
                    title="Editar cliente"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </Link>

                  <!-- Dar de baja -->
                  <Link 
                    :href="`/clientes/${cliente.clienteID}/confirm-delete`"
                    class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50 transition-colors"
                    title="Dar de baja"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación mejorada -->
      <div v-if="clienteStore.meta && clienteStore.meta.last_page > 1" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Mostrando 
            <span class="font-medium">{{ clienteStore.meta.from || 0 }}</span>
            a 
            <span class="font-medium">{{ clienteStore.meta.to || 0 }}</span>
            de 
            <span class="font-medium">{{ clienteStore.meta.total || 0 }}</span>
            resultados
          </div>

          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <!-- Botón anterior -->
            <button
              @click="goToPage(clienteStore.meta.current_page - 1)"
              :disabled="clienteStore.meta.current_page === 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>

            <!-- Números de página -->
            <template v-for="page in getVisiblePages()" :key="page">
              <button
                v-if="page !== '...'"
                @click="goToPage(page)"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  page === clienteStore.meta.current_page
                    ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                ]"
              >
                {{ page }}
              </button>
              <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                ...
              </span>
            </template>

            <!-- Botón siguiente -->
            <button
              @click="goToPage(clienteStore.meta.current_page + 1)"
              :disabled="clienteStore.meta.current_page === clienteStore.meta.last_page"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </nav>
        </div>
      </div>
    </div>
  </AppLayout>
</template>