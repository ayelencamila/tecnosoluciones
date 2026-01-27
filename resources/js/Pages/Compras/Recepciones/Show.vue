<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    recepcion: Object,
});

// Formatear fecha
const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Formatear moneda
const formatearMoneda = (valor) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
    }).format(valor || 0);
};

// Badge de tipo
const tipoBadge = (tipo) => {
    const badges = {
        'total': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'parcial': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return badges[tipo] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

// Calcular total de la recepción
const calcularTotalRecepcion = () => {
    return props.recepcion.detalles?.reduce((acc, detalle) => {
        const precioUnitario = detalle.detalle_orden?.precio_unitario || 0;
        return acc + (detalle.cantidad_recibida * precioUnitario);
    }, 0) || 0;
};
</script>

<template>
    <Head :title="`Recepción ${recepcion.numero_recepcion}`" />

    <AppLayout>
        <!-- HEADER -->
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Gestión de Compras
                    </h2>
                    <p class="text-sm font-medium text-indigo-800 dark:text-indigo-300 tracking-wide mt-1">
                        Recepciones › Detalle de Recepción
                    </p>
                </div>
                <Link
                    :href="route('recepciones.index')"
                    class="px-5 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Título -->
                <div class="mb-2">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        RECEPCIÓN: {{ recepcion.numero_recepcion }}
                    </h1>
                </div>

                <!-- Información general -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Información de la Recepción
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Número de Recepción</p>
                                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ recepcion.numero_recepcion }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Fecha de Recepción</p>
                                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ formatearFecha(recepcion.fecha_recepcion) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tipo de Recepción</p>
                                <p class="mt-1">
                                    <span :class="tipoBadge(recepcion.tipo)" class="px-3 py-1 text-sm font-medium rounded-full capitalize">
                                        {{ recepcion.tipo }}
                                    </span>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Recibido por</p>
                                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ recepcion.usuario?.name || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos de la Orden de Compra -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                            </svg>
                            Orden de Compra Asociada
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Número de OC</p>
                                <p class="mt-1">
                                    <Link 
                                        :href="route('ordenes.show', recepcion.orden_compra_id)"
                                        class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                    >
                                        {{ recepcion.orden_compra?.numero_oc }}
                                    </Link>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Proveedor</p>
                                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ recepcion.orden_compra?.proveedor?.nombre }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">CUIT: {{ recepcion.orden_compra?.proveedor?.cuit }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Fecha de la OC</p>
                                <p class="mt-1 font-medium text-gray-900 dark:text-white">
                                    {{ recepcion.orden_compra?.fecha_emision ? new Date(recepcion.orden_compra.fecha_emision).toLocaleDateString('es-AR') : '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div v-if="recepcion.observaciones" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            Observaciones
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ recepcion.observaciones }}</p>
                    </div>
                </div>

                <!-- Detalle de productos recibidos -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                            </svg>
                            Productos Recibidos
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Producto
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                        Cantidad Recibida
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">
                                        Precio Unitario
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">
                                        Subtotal
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Observación
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="detalle in recepcion.detalles" :key="detalle.id">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ detalle.detalle_orden?.producto?.nombre }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Código: {{ detalle.detalle_orden?.producto?.codigo }} | {{ detalle.detalle_orden?.producto?.unidad_medida }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded">
                                            {{ detalle.cantidad_recibida }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatearMoneda(detalle.detalle_orden?.precio_unitario) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatearMoneda(detalle.cantidad_recibida * (detalle.detalle_orden?.precio_unitario || 0)) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ detalle.observacion_item || '-' }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-white">
                                        Total Recepción:
                                    </td>
                                    <td class="px-6 py-4 text-right text-lg font-bold text-gray-900 dark:text-white">
                                        {{ formatearMoneda(calcularTotalRecepcion()) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex justify-end space-x-3">
                    <Link
                        :href="route('ordenes.show', recepcion.orden_compra_id)"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 rounded-md hover:bg-indigo-100 dark:hover:bg-indigo-900/50"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Ver Orden de Compra
                    </Link>
                    <Link
                        :href="route('recepciones.index')"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        Volver al Listado
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
