<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref, watch } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';

// Registrar componentes de gráficos Chart.js
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
    stocks: Object,
    kpis: Object,
    graficos: Object,
    filters: Object,
    depositos: Array
});

// Estado reactivo para los filtros
const form = ref({
    deposito_id: props.filters.deposito_id || '',
    bajo_stock: props.filters.bajo_stock === "true" || props.filters.bajo_stock === true || false,
});

// Función para exportar a Excel
const exportar = () => {
    const params = new URLSearchParams({
        deposito_id: form.value.deposito_id,
        bajo_stock: form.value.bajo_stock ? '1' : '0'
    }).toString();
    
    // Redirección directa al endpoint de exportación
    window.location.href = route('reportes.stock.exportar') + '?' + params;
};

// Observar cambios en los filtros para recargar la página (AJAX con Inertia)
watch(form, () => {
    router.get(route('reportes.stock'), form.value, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, { deep: true });
</script>

<template>
    <AppLayout>
        <Head title="Reporte de Stock" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Reporte de Stock e Inventario
                </h2>
                <span class="text-sm text-gray-500">
                    Actualizado: {{ new Date().toLocaleDateString() }}
                </span>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b-4 border-indigo-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-gray-500 text-sm font-medium uppercase tracking-wider">Total Unidades</div>
                                <div class="text-3xl font-bold text-gray-900 mt-2">{{ kpis.total_unidades }}</div>
                                <div class="text-xs text-gray-400 mt-1">En todos los depósitos</div>
                            </div>
                            <div class="p-3 bg-indigo-50 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b-4 border-red-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-gray-500 text-sm font-medium uppercase tracking-wider">Stock Crítico</div>
                                <div class="text-3xl font-bold text-red-600 mt-2">{{ kpis.productos_criticos }}</div>
                                <div class="text-xs text-red-400 mt-1">Productos bajo el mínimo</div>
                            </div>
                            <div class="p-3 bg-red-50 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b-4"
                         :class="kpis.productos_criticos > 0 ? 'border-yellow-500' : 'border-green-500'">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-gray-500 text-sm font-medium uppercase tracking-wider">Estado Salud</div>
                                <div class="text-xl font-bold mt-2" :class="kpis.productos_criticos > 0 ? 'text-yellow-600' : 'text-green-600'">
                                    {{ kpis.productos_criticos > 0 ? 'Requiere Atención' : 'Inventario Óptimo' }}
                                </div>
                                <div class="text-xs text-gray-400 mt-1">Diagnóstico automático</div>
                            </div>
                            <div class="p-3 rounded-full" :class="kpis.productos_criticos > 0 ? 'bg-yellow-50' : 'bg-green-50'">
                                <svg v-if="kpis.productos_criticos > 0" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div class="flex flex-col md:flex-row gap-4 items-center w-full md:w-auto">
                        <div class="relative w-full md:w-64">
                            <select v-model="form.deposito_id" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">Todos los Depósitos</option>
                                <option v-for="d in depositos" :key="d.id || d.deposito_id" :value="d.id || d.deposito_id">
                                    {{ d.nombre }}
                                </option>
                            </select>
                        </div>
                        
                        <label class="flex items-center cursor-pointer select-none px-3 py-2 bg-gray-50 rounded-md hover:bg-gray-100 transition">
                            <input type="checkbox" v-model="form.bajo_stock" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-4 w-4">
                            <span class="ml-2 text-sm text-gray-700 font-medium">Ver solo stock crítico</span>
                        </label>
                    </div>

                    <PrimaryButton @click="exportar" class="w-full md:w-auto justify-center bg-green-600 hover:bg-green-700 focus:ring-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Descargar Reporte Excel
                    </PrimaryButton>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" v-if="stocks.data.length > 0 || kpis.total_unidades > 0">
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800">Productos con Mayor Riesgo</h3>
                            <span class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded-full font-semibold">Top 5 Críticos</span>
                        </div>
                        <div class="h-64 relative w-full">
                             <Bar :data="graficos.riesgo" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </div>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800">Distribución por Depósito</h3>
                            <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full font-semibold">Global</span>
                        </div>
                        <div class="h-64 relative w-full flex justify-center">
                            <Doughnut :data="graficos.depositos" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-md font-semibold text-gray-700">Detalle de Inventario</h3>
                        <span class="text-xs text-gray-500">Mostrando {{ stocks.from }}-{{ stocks.to }} de {{ stocks.total }} registros</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Categoría</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Depósito</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Punto Reposición</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in stocks.data" :key="item.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-bold text-gray-900">{{ item.producto?.nombre || 'Producto Eliminado' }}</div>
                                                <div class="text-xs text-gray-500">Cód: {{ item.producto?.codigo || '---' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 hidden md:table-cell">
                                        <span class="px-2 py-1 bg-gray-100 rounded text-gray-600 text-xs font-medium">
                                            {{ item.producto?.categoria?.nombre || 'Sin Categoría' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 hidden sm:table-cell">
                                        {{ item.deposito?.nombre || 'General' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-base font-bold" :class="item.cantidad_disponible <= item.stock_minimo ? 'text-red-600' : 'text-gray-900'">
                                            {{ item.cantidad_disponible }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 hidden sm:table-cell">
                                        {{ item.stock_minimo }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span v-if="item.cantidad_disponible <= item.stock_minimo" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 border border-red-200">
                                            Crítico
                                        </span>
                                        <span v-else class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                                            Normal
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="stocks.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <p class="text-gray-500 text-sm font-medium">No se encontraron productos con los filtros seleccionados.</p>
                                            <button @click="form.bajo_stock = false; form.deposito_id = ''" class="mt-2 text-indigo-600 hover:text-indigo-800 text-sm underline font-medium">
                                                Limpiar filtros
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        <Pagination :links="stocks.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>