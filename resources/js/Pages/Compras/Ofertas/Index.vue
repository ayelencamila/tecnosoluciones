<script setup>
/**
 * CU-21 Pantalla 1: Panel Principal de Gestión de Ofertas
 * 
 * Diseño basado en Kendall & Kendall (8va Ed.) Cap. 11 - Diseño de Salida Efectiva:
 * - "Tablero de control" o lista de tareas pendientes ("to-do list")
 * - Formato tabular para lectura rápida de qué productos necesitan gestión
 * - Muestra claramente el estado y la acción requerida
 */
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios';
import { debounce } from 'lodash';

const props = defineProps({
    solicitudesPendientes: {
        type: Object,
        default: () => ({ data: [] })
    },
    estadosSolicitud: {
        type: Array,
        default: () => []
    },
    productosConOfertas: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    counts: {
        type: Object,
        default: () => ({})
    }
});

// Filtros locales
const localFilters = ref({
    search: props.filters?.search || '',
    estado: props.filters?.estado || '',
});

// Estado para el buscador de productos
const searchQuery = ref(props.filters?.search || '');
const searchResults = ref([]);
const showDropdown = ref(false);
const isSearching = ref(false);
const searchContainer = ref(null);

// Estado para eliminación
const confirmingDeletion = ref(false);
const ofertaToDelete = ref(null);

// Funciones de eliminación
const confirmDelete = (oferta) => {
    ofertaToDelete.value = oferta;
    confirmingDeletion.value = true;
};

const closeModal = () => {
    confirmingDeletion.value = false;
    ofertaToDelete.value = null;
};

const deleteOferta = () => {
    if (ofertaToDelete.value) {
        router.delete(route('ofertas.destroy', ofertaToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        });
    }
};

// Buscar productos con debounce
const buscarProductos = debounce(async (query) => {
    if (!query || query.length < 2) {
        searchResults.value = [];
        showDropdown.value = false;
        return;
    }
    
    isSearching.value = true;
    try {
        const response = await axios.get(route('api.productos.buscar'), {
            params: { q: query, limit: 10 }
        });
        searchResults.value = response.data;
        showDropdown.value = searchResults.value.length > 0;
    } catch (error) {
        console.error('Error buscando productos:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
}, 300);

// Watch para búsqueda en tiempo real
watch(searchQuery, (newValue) => {
    if (newValue === '') {
        // Si se borra el texto, limpiar filtro
        localFilters.value.search = '';
        aplicarFiltros();
    } else {
        buscarProductos(newValue);
    }
});

// Seleccionar producto del dropdown
const seleccionarProducto = (producto) => {
    searchQuery.value = producto.nombre;
    localFilters.value.search = producto.nombre;
    showDropdown.value = false;
    aplicarFiltros();
};

// Limpiar búsqueda
const limpiarBusqueda = () => {
    searchQuery.value = '';
    localFilters.value.search = '';
    searchResults.value = [];
    showDropdown.value = false;
    aplicarFiltros();
};

// Cerrar dropdown al hacer clic fuera
const handleClickOutside = (event) => {
    if (searchContainer.value && !searchContainer.value.contains(event.target)) {
        showDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Aplicar filtros
const aplicarFiltros = debounce(() => {
    router.get(route('ofertas.index'), {
        search: localFilters.value.search || undefined,
        estado: localFilters.value.estado || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

// Filtrar por estado inmediatamente
const filtrarPorEstado = () => {
    router.get(route('ofertas.index'), {
        search: localFilters.value.search || undefined,
        estado: localFilters.value.estado || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Formatear fecha
const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

// Obtener label y clase para estado (usa el estado REAL de la BD)
const getEstadoInfo = (solicitud) => {
    const estadoNombre = solicitud.estado?.nombre || 'Sin estado';
    const ofertas = solicitud.ofertas_count || 0;
    
    // Mapeo de estados con sus colores
    const estadosConfig = {
        'Pendiente': { class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' },
        'Enviada': { class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' },
        'Abierta': { class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' },
        'Completada': { class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' },
        'Cancelada': { class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' },
    };
    
    const config = estadosConfig[estadoNombre] || { class: 'bg-gray-100 text-gray-600' };
    
    // Agregar contador de ofertas si hay
    const label = ofertas > 0 
        ? `${estadoNombre} (${ofertas} oferta${ofertas > 1 ? 's' : ''})` 
        : estadoNombre;
    
    return {
        label,
        class: config.class,
    };
};

// Ir a comparar por producto
const compararPorProducto = (productoId) => {
    router.get(route('ofertas.comparar', { producto_id: productoId }));
};

// Ver ofertas de una solicitud
const verOfertas = (solicitud) => {
    // Comparar ofertas del primer producto de la solicitud
    if (solicitud.detalles && solicitud.detalles.length > 0) {
        const primerProducto = solicitud.detalles[0].producto;
        if (primerProducto) {
            router.get(route('ofertas.comparar', { producto_id: primerProducto.id }));
        }
    }
};

// Traducir labels de paginación (sin hardcodeo de idioma en backend)
const traducirPaginacion = (label) => {
    const traducciones = {
        'Previous': '« Anterior',
        'Next': 'Siguiente »',
        '&laquo; Previous': '« Anterior',
        'Next &raquo;': 'Siguiente »',
    };
    return traducciones[label] || label;
};
</script>

<template>
    <Head title="Gestión de Ofertas de Compra" />

    <AppLayout>
        <!-- HEADER -->
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Gestión de Compras
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- ============================================== -->
                <!-- TÍTULO Y BOTÓN PRINCIPAL (alineados izquierda) -->
                <!-- ============================================== -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">
                        GESTIÓN DE OFERTAS DE COMPRA
                    </h1>

                    <Link :href="route('ofertas.create')"
                          class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md shadow-sm transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Registrar Oferta (Sin Solicitud Previa)
                    </Link>
                </div>

                <!-- ============================================== -->
                <!-- ALERTA: PRODUCTOS CON MÚLTIPLES OFERTAS       -->
                <!-- ============================================== -->
                <div v-if="productosConOfertas && productosConOfertas.length > 0" 
                     class="bg-purple-50 dark:bg-purple-900/20 border border-purple-300 dark:border-purple-700 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-purple-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-purple-800 dark:text-purple-200">
                                Productos con ofertas disponibles:
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button v-for="prod in productosConOfertas" :key="prod.id"
                                        @click="compararPorProducto(prod.id)"
                                        class="inline-flex items-center px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded transition-colors">
                                    {{ prod.nombre }}
                                    <span class="ml-1.5 bg-white/20 px-1.5 rounded text-xs">{{ prod.ofertas_count }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- SECCIÓN PRINCIPAL: SOLICITUDES Y NECESIDADES  -->
                <!-- CU-21 Paso 2: listado de productos con         -->
                <!-- necesidad de compra o solicitudes pendientes   -->
                <!-- ============================================== -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    
                    <!-- Encabezado de sección -->
                    <div class="bg-gray-100 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                        <h2 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Solicitudes de Cotización Pendientes
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Seleccione una solicitud para registrar ofertas de proveedores, o use el botón superior para registrar sin solicitud previa.
                        </p>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-750 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                            <!-- Filtrar por Estado -->
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">Filtrar por:</label>
                                <select 
                                    v-model="localFilters.estado"
                                    @change="filtrarPorEstado"
                                    class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-2 px-3 min-w-[180px]">
                                    <option value="">Todos los Estados</option>
                                    <option value="pendientes">Pendientes de Gestión</option>
                                    <option disabled class="text-gray-400">──────────────</option>
                                    <option v-for="estado in estadosSolicitud" :key="estado.id" :value="estado.nombre">
                                        {{ estado.nombre }}
                                    </option>
                                </select>
                            </div>
                            
                            <!-- Buscar Producto (Autocomplete) -->
                            <div class="flex-1 relative" ref="searchContainer">
                                <div class="relative">
                                    <input 
                                        type="text"
                                        v-model="searchQuery"
                                        placeholder="Buscar producto por nombre..."
                                        class="w-full sm:w-80 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-2 pl-10 pr-10"
                                        @focus="searchQuery.length >= 2 && searchResults.length > 0 && (showDropdown = true)"
                                    />
                                    <!-- Icono de búsqueda -->
                                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <!-- Spinner de carga -->
                                    <svg v-if="isSearching" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <!-- Botón limpiar -->
                                    <button 
                                        v-else-if="searchQuery"
                                        @click="limpiarBusqueda"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Dropdown de resultados -->
                                <div 
                                    v-if="showDropdown && searchResults.length > 0"
                                    class="absolute z-50 mt-1 w-full sm:w-80 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto"
                                >
                                    <button
                                        v-for="producto in searchResults"
                                        :key="producto.id"
                                        @click="seleccionarProducto(producto)"
                                        class="w-full px-4 py-2 text-left text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 focus:bg-indigo-50 dark:focus:bg-indigo-900/30 focus:outline-none border-b border-gray-100 dark:border-gray-600 last:border-b-0"
                                    >
                                        <div class="font-medium text-gray-900 dark:text-white">{{ producto.nombre }}</div>
                                        <div v-if="producto.codigo" class="text-xs text-gray-500 dark:text-gray-400">
                                            Código: {{ producto.codigo }}
                                        </div>
                                    </button>
                                </div>
                                
                                <!-- Mensaje "sin resultados" -->
                                <div 
                                    v-if="showDropdown && searchQuery.length >= 2 && searchResults.length === 0 && !isSearching"
                                    class="absolute z-50 mt-1 w-full sm:w-80 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg p-3"
                                >
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No se encontraron productos</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Indicador de filtro activo -->
                        <div v-if="localFilters.search" class="mt-3 flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Filtrando por:</span>
                            <span class="inline-flex items-center px-2 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-medium rounded">
                                {{ localFilters.search }}
                                <button @click="limpiarBusqueda" class="ml-1.5 hover:text-indigo-900 dark:hover:text-indigo-100">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </span>
                        </div>
                    </div>
                    
                    <!-- TABLA -->
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-indigo-600">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider w-32">
                                        ID Solic.
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Producto / Ítem
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider w-24">
                                        Cant.
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider w-28">
                                        Fecha Límite
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider w-36">
                                        Estado
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider w-40">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template v-for="solicitud in solicitudesPendientes.data" :key="solicitud.id">
                                    <tr v-for="(detalle, idx) in solicitud.detalles" :key="`${solicitud.id}-${detalle.id}`"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        
                                        <!-- ID Solicitud -->
                                        <td class="px-4 py-3">
                                            <span v-if="idx === 0" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                                #{{ solicitud.codigo_solicitud }}
                                            </span>
                                        </td>
                                        
                                        <!-- Producto / Ítem -->
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-900 dark:text-white line-clamp-2" :title="detalle.producto?.nombre">
                                                {{ detalle.producto?.nombre || 'Producto no especificado' }}
                                            </div>
                                        </td>
                                        
                                        <!-- Cantidad Requerida -->
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ detalle.cantidad_sugerida }}
                                            </span>
                                        </td>
                                        
                                        <!-- Fecha Límite -->
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ formatDate(solicitud.fecha_vencimiento) }}
                                            </span>
                                        </td>
                                        
                                        <!-- Estado -->
                                        <td class="px-4 py-3 text-center">
                                            <span v-if="idx === 0" 
                                                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                  :class="getEstadoInfo(solicitud).class">
                                                {{ getEstadoInfo(solicitud).label }}
                                            </span>
                                        </td>
                                        
                                        <!-- Acciones -->
                                        <td class="px-4 py-3 text-center">
                                            <div v-if="idx === 0" class="flex items-center justify-center gap-2">
                                                <!-- Ver ofertas existentes -->
                                                <button
                                                    v-if="solicitud.ofertas_count > 0"
                                                    @click="verOfertas(solicitud)"
                                                    class="p-2 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:text-indigo-300 dark:hover:bg-indigo-900/30 rounded-md transition-colors"
                                                    title="Ver ofertas">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </button>
                                                
                                                <!-- Registrar nueva oferta -->
                                                <Link
                                                    :href="route('ofertas.create', { solicitud_id: solicitud.id })"
                                                    class="p-2 text-green-600 hover:text-green-800 hover:bg-green-50 dark:text-green-400 dark:hover:text-green-300 dark:hover:bg-green-900/30 rounded-md transition-colors"
                                                    title="Registrar oferta">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                
                                <!-- Estado vacío -->
                                <tr v-if="!solicitudesPendientes.data || solicitudesPendientes.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <p class="text-gray-500 dark:text-gray-400 font-medium">
                                            No hay solicitudes pendientes de cotización
                                        </p>
                                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">
                                            Puede registrar una oferta manualmente usando el botón superior
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Nota al pie -->
                    <div v-if="solicitudesPendientes.data && solicitudesPendientes.data.length > 0" 
                         class="px-6 py-3 bg-gray-50 dark:bg-gray-750 border-t border-gray-200 dark:border-gray-600">
                        <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                            (1) Indica que ya hay 1 oferta registrada para esta solicitud.
                        </p>
                    </div>

                    <!-- PAGINACIÓN -->
                    <div v-if="solicitudesPendientes.links && solicitudesPendientes.links.length > 3" 
                         class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Mostrando {{ solicitudesPendientes.from }} a {{ solicitudesPendientes.to }} 
                                de {{ solicitudesPendientes.total }} solicitudes
                            </p>
                            <div class="flex items-center space-x-1">
                                <template v-for="(link, k) in solicitudesPendientes.links" :key="k">
                                    <Link 
                                        v-if="link.url" 
                                        :href="link.url" 
                                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                        :class="link.active 
                                            ? 'bg-indigo-600 text-white' 
                                            : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-500'">
                                        {{ traducirPaginacion(link.label) }}
                                    </Link>
                                    <span 
                                        v-else 
                                        class="px-3 py-1.5 text-sm text-gray-400 dark:text-gray-500">
                                        {{ traducirPaginacion(link.label) }}
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal de confirmación de eliminación -->
        <Modal :show="confirmingDeletion" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white text-center mb-2">
                    ¿Está seguro de eliminar esta oferta?
                </h2>
                <p class="text-center text-sm text-gray-600 dark:text-gray-400 mb-1">
                    Oferta: <strong class="text-gray-900 dark:text-white">{{ ofertaToDelete?.codigo_oferta }}</strong>
                </p>
                <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Esta acción no se puede deshacer.
                </p>
                <div class="flex gap-3 justify-center">
                    <button
                        @click="closeModal"
                        class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button
                        @click="deleteOferta"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
