<script setup>
/**
 * CU-22 Pantalla 1: Punto de Partida - Listado de Ofertas Elegidas
 * 
 * Contexto: El administrador necesita encontrar las ofertas que ya han sido
 * negociadas y seleccionadas (en el CU-21 anterior) y que están listas para
 * convertirse en Órdenes de Compra.
 * 
 * Principio K&K (Salida de Navegación): Uso de lista filtrada para mostrar
 * solo los elementos que requieren acción inmediata ("To-do list").
 * Los botones de acción deben ser claros.
 */
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash';

const props = defineProps({
    ofertas: {
        type: Object,
        default: () => ({ data: [] })
    },
    proveedores: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
});

// Filtros locales
const localFilters = ref({
    proveedor_id: props.filters?.proveedor_id || '',
    busqueda: props.filters?.busqueda || '',
});

// Aplicar filtros con debounce
const aplicarFiltros = debounce(() => {
    router.get(route('ordenes.index'), {
        proveedor_id: localFilters.value.proveedor_id || undefined,
        busqueda: localFilters.value.busqueda || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

// Watch para filtros
watch(() => localFilters.value.proveedor_id, () => {
    aplicarFiltros();
});

watch(() => localFilters.value.busqueda, () => {
    aplicarFiltros();
});

// Limpiar filtros
const limpiarFiltros = () => {
    localFilters.value = {
        proveedor_id: '',
        busqueda: '',
    };
};

// Formatear moneda
const formatCurrency = (value, moneda = 'ARS') => {
    const symbol = moneda === 'USD' ? 'USD ' : '$ ';
    return symbol + Number(value || 0).toLocaleString('es-AR', { minimumFractionDigits: 2 });
};

// Obtener producto principal de la oferta
const getProductoPrincipal = (oferta) => {
    if (oferta.detalles && oferta.detalles.length > 0) {
        const detalle = oferta.detalles[0];
        const nombre = detalle.producto?.nombre || 'Producto';
        const cantidad = detalle.cantidad_ofrecida || detalle.cantidad || 1;
        return `${nombre} (${cantidad}u)`;
    }
    if (oferta.producto) {
        return `${oferta.producto.nombre} (${oferta.cantidad || 1}u)`;
    }
    return '-';
};

// Traducir paginación
const traducirPaginacion = (label) => {
    const traducciones = {
        'Previous': 'Anterior',
        'Next': 'Siguiente',
        '&laquo; Previous': 'Anterior',
        'Next &raquo;': 'Siguiente',
        '&laquo;': '«',
        '&raquo;': '»',
    };
    return traducciones[label] || label;
};

// Total de resultados
const totalResultados = computed(() => props.ofertas?.total || props.ofertas?.data?.length || 0);

// Verificar si hay filtros activos
const hayFiltrosActivos = computed(() => {
    return localFilters.value.proveedor_id || localFilters.value.busqueda;
});
</script>

<template>
    <Head title="Ofertas Listas para Orden de Compra" />

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
                <!-- TÍTULO Y STATS                                 -->
                <!-- ============================================== -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">
                        OFERTAS LISTAS PARA ORDEN DE COMPRA
                    </h1>

                    <!-- Tarjeta informativa -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    Estas ofertas han sido marcadas como <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded font-semibold">"Elegida"</span> 
                                    y están listas para generar la Orden de Compra.
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    Seleccione "GENERAR OC" para crear la orden de compra correspondiente.
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="text-center px-4 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ totalResultados }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                                        {{ totalResultados === 1 ? 'Oferta' : 'Ofertas' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- PANEL PRINCIPAL CON FILTROS Y TARJETAS         -->
                <!-- ============================================== -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    
                    <!-- BARRA DE FILTROS -->
                    <div class="px-6 py-4 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex flex-col lg:flex-row gap-4 items-end">
                            <!-- Filtro por Proveedor -->
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wide">
                                    Filtrar por Proveedor:
                                </label>
                                <select 
                                    v-model="localFilters.proveedor_id"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-white transition-colors"
                                >
                                    <option value="">Todos los Proveedores</option>
                                    <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">
                                        {{ prov.razon_social }}
                                    </option>
                                </select>
                            </div>

                            <!-- Búsqueda por Código -->
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wide">
                                    Buscar por Código:
                                </label>
                                <div class="relative">
                                    <input 
                                        v-model="localFilters.busqueda"
                                        type="text"
                                        placeholder="Ej: OF-1025, SOL-120..."
                                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-white transition-colors"
                                    />
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón limpiar -->
                            <button 
                                v-if="hayFiltrosActivos"
                                @click="limpiarFiltros"
                                class="px-5 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors whitespace-nowrap"
                            >
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <!-- CONTENIDO: TARJETAS O TABLA SEGÚN VIEWPORT -->
                    <div class="p-6">
                        <!-- GRID DE TARJETAS (Mobile y Tablet) -->
                        <div class="block lg:hidden space-y-4">
                            <div v-for="oferta in ofertas.data" :key="oferta.id"
                                 class="bg-white dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                                
                                <!-- Header de la tarjeta -->
                                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-bold text-white">
                                            {{ oferta.codigo_oferta || `OF-${oferta.id}` }}
                                        </span>
                                        <span class="text-xs font-medium text-indigo-100">
                                            {{ oferta.moneda }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Contenido de la tarjeta -->
                                <div class="p-4 space-y-3">
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Proveedor</dt>
                                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ oferta.proveedor?.razon_social || '-' }}
                                        </dd>
                                    </div>
                                    
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Producto Principal</dt>
                                        <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                            {{ getProductoPrincipal(oferta) }}
                                        </dd>
                                    </div>
                                    
                                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Oferta</dt>
                                        <dd class="mt-1 text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ formatCurrency(oferta.total_estimado, oferta.moneda) }}
                                        </dd>
                                    </div>
                                </div>

                                <!-- Footer con acción -->
                                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                                    <Link 
                                        :href="route('ordenes.create', { oferta_id: oferta.id })"
                                        class="block w-full text-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold uppercase rounded-lg transition-colors shadow-sm"
                                    >
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        GENERAR OC
                                    </Link>
                                </div>
                            </div>

                            <!-- Sin resultados -->
                            <div v-if="!ofertas.data || ofertas.data.length === 0"
                                 class="text-center py-12">
                                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                                    No hay ofertas listas para generar OC
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                                    Las ofertas aparecen aquí cuando son marcadas como "Elegida" en la Gestión de Ofertas.
                                </p>
                                <Link 
                                    :href="route('ofertas.index')" 
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors"
                                >
                                    Ir a Gestión de Ofertas
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </Link>
                            </div>
                        </div>

                        <!-- TABLA (Desktop) -->
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-indigo-600">
                                        <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                            ID Oferta
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                            Proveedor
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                            Producto Principal
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">
                                            Total Oferta
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider w-40">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="oferta in ofertas.data" :key="oferta.id"
                                        class="hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-colors">
                                        <!-- ID Oferta -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 rounded-full">
                                                <svg class="w-3 h-3 mr-1.5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                                                    {{ oferta.codigo_oferta || `OF-${oferta.id}` }}
                                                </span>
                                            </span>
                                        </td>
                                        
                                        <!-- Proveedor -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ oferta.proveedor?.razon_social || '-' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <!-- Producto Principal -->
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ getProductoPrincipal(oferta) }}
                                            </div>
                                        </td>
                                        
                                        <!-- Total -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <div class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ formatCurrency(oferta.total_estimado, oferta.moneda) }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ oferta.moneda }}
                                            </div>
                                        </td>
                                        
                                        <!-- Acción -->
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <Link 
                                                :href="route('ordenes.create', { oferta_id: oferta.id })"
                                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-xs font-bold uppercase rounded-lg transition-all shadow-sm hover:shadow-md"
                                            >
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                GENERAR OC
                                            </Link>
                                        </td>
                                    </tr>

                                    <!-- Sin resultados -->
                                    <tr v-if="!ofertas.data || ofertas.data.length === 0">
                                        <td colspan="5" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                                    No hay ofertas listas para generar OC
                                                </p>
                                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                                                    Las ofertas aparecen aquí cuando son marcadas como "Elegida" en la Gestión de Ofertas.
                                                </p>
                                                <Link 
                                                    :href="route('ofertas.index')" 
                                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors"
                                                >
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                                    </svg>
                                                    Ir a Gestión de Ofertas
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- PIE: Paginación -->
                    <div v-if="ofertas.links && ofertas.links.length > 3" 
                         class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                            <!-- Contador -->
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Mostrando 
                                <span class="font-semibold text-gray-900 dark:text-white">{{ ofertas.from || 0 }}</span>
                                a 
                                <span class="font-semibold text-gray-900 dark:text-white">{{ ofertas.to || 0 }}</span>
                                de 
                                <span class="font-semibold text-gray-900 dark:text-white">{{ totalResultados }}</span>
                                {{ totalResultados === 1 ? 'resultado' : 'resultados' }}
                            </p>

                            <!-- Paginación -->
                            <nav class="flex items-center space-x-1">
                                <template v-for="(link, index) in ofertas.links" :key="index">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        :class="[
                                            'px-3 py-2 text-sm font-medium rounded-lg transition-all',
                                            link.active 
                                                ? 'bg-indigo-600 text-white shadow-sm' 
                                                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600'
                                        ]"
                                        preserve-scroll
                                    >
                                        {{ traducirPaginacion(link.label) }}
                                    </Link>
                                    <span
                                        v-else
                                        :class="[
                                            'px-3 py-2 text-sm font-medium rounded-lg',
                                            'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed'
                                        ]"
                                    >
                                        {{ traducirPaginacion(link.label) }}
                                    </span>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
