<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    historial: Array,
});

const getColorForAction = (accion) => {
    if (accion.includes('MODIFICAR')) return 'text-blue-600 bg-blue-50';
    if (accion.includes('CREAR')) return 'text-green-600 bg-green-50';
    return 'text-gray-600 bg-gray-50';
};

const formatValue = (value) => {
    if (value === null || value === undefined) return 'N/A';
    if (value === 'true') return '✓ Sí';
    if (value === 'false') return '✗ No';
    return value;
};
</script>

<template>
    <Head title="Historial de Configuración" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Historial de Cambios - Configuración
                </h2>
                <Link 
                    :href="route('configuracion.index')" 
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver a Configuración
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <!-- Info Header -->
                    <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-indigo-900">
                                <strong>CU-31:</strong> Este historial muestra todos los cambios realizados en los parámetros globales del sistema, incluyendo quién los modificó y cuándo.
                            </p>
                        </div>
                    </div>

                    <!-- Tabla de Historial -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Usuario
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Parámetro
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Valor Anterior
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Valor Nuevo
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Motivo
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="historial.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm">No hay cambios registrados en el historial.</p>
                                    </td>
                                </tr>
                                <tr 
                                    v-for="item in historial" 
                                    :key="item.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ item.fecha }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-xs mr-2">
                                                {{ item.usuario.charAt(0).toUpperCase() }}
                                            </div>
                                            <span class="text-sm font-medium text-gray-900">{{ item.usuario }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">
                                            {{ item.parametro }}
                                        </code>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 bg-red-50 text-red-700 rounded text-xs font-medium">
                                            {{ formatValue(item.valor_anterior) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 bg-green-50 text-green-700 rounded text-xs font-medium">
                                            {{ formatValue(item.valor_nuevo) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ item.motivo }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Info -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <p class="text-xs text-gray-500 text-center">
                            Mostrando los últimos 100 cambios. Para consultas específicas, contacte al administrador del sistema.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
