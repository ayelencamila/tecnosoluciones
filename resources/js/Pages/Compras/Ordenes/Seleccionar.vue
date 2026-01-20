<script setup>
/**
 * CU-22 - Pantalla 1: Listado de Ofertas Elegidas
 * (Vista de Selección según Kendall - Cap. 11 Salida)
 * 
 * Función: Mostrar ofertas listas para convertir en OC
 * Principio: Una pantalla, un objetivo
 * Contenido: Proveedor, Fecha, Total, Estado, Acción
 */
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    ofertas: {
        type: Array,
        required: true
    }
});

const formatMoneda = (valor) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2
    }).format(valor);
};

const formatFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const estadoClass = (estado) => {
    if (estado === 'Elegida') return 'bg-green-100 text-green-800';
    if (estado === 'Pre-aprobada') return 'bg-blue-100 text-blue-800';
    return 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Generar Orden de Compra" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Generar Orden de Compra
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Seleccione la oferta para generar la orden de compra
                    </p>
                </div>
                <Link :href="route('ordenes.index')" 
                      class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    ← Volver a Órdenes
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Sin ofertas disponibles -->
                <div v-if="ofertas.length === 0" 
                     class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                        No hay ofertas listas para convertir
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Para generar una orden de compra, primero debe marcar una oferta como "Elegida" o "Pre-aprobada".
                    </p>
                    <div class="mt-6">
                        <Link :href="route('ofertas.index')"
                              class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Ver Todas las Ofertas
                        </Link>
                    </div>
                </div>

                <!-- Lista de ofertas elegidas -->
                <div v-else class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <!-- Contador -->
                    <div class="px-6 py-4 bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-100 dark:border-indigo-800">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-medium text-indigo-900 dark:text-indigo-100">
                                {{ ofertas.length }} {{ ofertas.length === 1 ? 'oferta lista' : 'ofertas listas' }} para generar orden de compra
                            </span>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Proveedor
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Total
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="oferta in ofertas" :key="oferta.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ oferta.proveedor.razon_social }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            CUIT: {{ oferta.proveedor.cuit }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatFecha(oferta.fecha_recepcion) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white text-right">
                                        {{ formatMoneda(oferta.total_estimado) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full"
                                              :class="estadoClass(oferta.estado.nombre)">
                                            {{ oferta.estado.nombre }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <Link :href="route('ordenes.create', { oferta_id: oferta.id })"
                                              class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            Generar OC
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
