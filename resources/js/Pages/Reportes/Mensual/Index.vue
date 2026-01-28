<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Line, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    ArcElement,
    Filler
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, ArcElement, Filler);

const props = defineProps({
    filters: Object,
    periodo: Object,
    planilla: Object,
    gastosPorCategoria: Array,
    graficos: Object,
    tiposGrafico: Array,
});

// Filtros
const form = ref({
    mes: props.filters.mes,
    anio: props.filters.anio,
    tipo_grafico: props.filters.tipo_grafico,
});

// Años disponibles
const aniosDisponibles = (() => {
    const anioActual = new Date().getFullYear();
    const anios = [];
    for (let i = anioActual; i >= anioActual - 5; i--) {
        anios.push(i);
    }
    return anios;
})();

// Meses
const meses = [
    { value: 1, label: 'Enero' },
    { value: 2, label: 'Febrero' },
    { value: 3, label: 'Marzo' },
    { value: 4, label: 'Abril' },
    { value: 5, label: 'Mayo' },
    { value: 6, label: 'Junio' },
    { value: 7, label: 'Julio' },
    { value: 8, label: 'Agosto' },
    { value: 9, label: 'Septiembre' },
    { value: 10, label: 'Octubre' },
    { value: 11, label: 'Noviembre' },
    { value: 12, label: 'Diciembre' },
];

// Watch para recargar cuando cambian filtros
watch(() => [form.value.mes, form.value.anio, form.value.tipo_grafico], () => {
    router.get(route('reportes.mensual'), form.value, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
});

// Exportar
const exportar = () => {
    const params = new URLSearchParams({
        mes: form.value.mes,
        anio: form.value.anio,
    }).toString();
    window.location.href = route('reportes.mensual.exportar') + '?' + params;
};

// Formateo
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value || 0);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-AR').format(value || 0);
};

// Opciones para gráficos
const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: (value) => '$' + formatNumber(value)
            }
        }
    }
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
        },
    },
};

// Título del gráfico de distribución
const tituloDistribucion = {
    ventas: 'por Vendedor',
    reparaciones: 'por Técnico',
    compras: 'por Proveedor',
    gastos: 'por Categoría',
    pagos: 'por Medio de Pago',
};
</script>

<template>
    <AppLayout>
        <Head title="Reporte Mensual" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Reporte Mensual
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Estado de resultados del período
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Filtros y Exportar -->
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="flex flex-wrap items-end gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                            <select
                                v-model="form.mes"
                                class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="mes in meses" :key="mes.value" :value="mes.value">
                                    {{ mes.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                            <select
                                v-model="form.anio"
                                class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="anio in aniosDisponibles" :key="anio" :value="anio">
                                    {{ anio }}
                                </option>
                            </select>
                        </div>

                        <div class="flex-1"></div>

                        <button
                            @click="exportar"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Exportar Excel
                        </button>
                    </div>
                </div>

                <!-- PLANILLA DE RESULTADOS -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <!-- Encabezado -->
                    <div class="bg-gray-800 text-white px-6 py-4 text-center">
                        <h3 class="text-lg font-bold uppercase tracking-wider">
                            Estado de Resultados
                        </h3>
                        <p class="text-gray-300 text-sm mt-1">{{ periodo.nombre }}</p>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <!-- SECCIÓN ENTRADAS -->
                        <div class="bg-emerald-50">
                            <div class="px-6 py-3 bg-emerald-100 border-b border-emerald-200">
                                <h4 class="text-sm font-bold text-emerald-800 uppercase tracking-wider">Entradas</h4>
                            </div>
                            <table class="min-w-full">
                                <tbody>
                                    <tr v-for="(item, index) in planilla.entradas" :key="index" class="border-b border-emerald-100">
                                        <td class="px-6 py-3 text-sm text-gray-700 w-1/2">{{ item.concepto }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-500 text-center w-1/4">
                                            <span v-if="item.cantidad !== null">{{ item.cantidad }} operaciones</span>
                                        </td>
                                        <td class="px-6 py-3 text-sm font-medium text-emerald-700 text-right w-1/4">
                                            {{ formatCurrency(item.total) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-emerald-100 font-bold">
                                        <td class="px-6 py-3 text-sm text-emerald-900" colspan="2">TOTAL ENTRADAS</td>
                                        <td class="px-6 py-3 text-sm text-emerald-900 text-right">{{ formatCurrency(planilla.total_entradas) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- SECCIÓN SALIDAS -->
                        <div class="bg-red-50">
                            <div class="px-6 py-3 bg-red-100 border-b border-red-200">
                                <h4 class="text-sm font-bold text-red-800 uppercase tracking-wider">Salidas</h4>
                            </div>
                            <table class="min-w-full">
                                <tbody>
                                    <tr v-for="(item, index) in planilla.salidas" :key="index" class="border-b border-red-100">
                                        <td class="px-6 py-3 text-sm text-gray-700 w-1/2">{{ item.concepto }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-500 text-center w-1/4">
                                            <span v-if="item.cantidad !== null">{{ item.cantidad }} operaciones</span>
                                        </td>
                                        <td class="px-6 py-3 text-sm font-medium text-red-700 text-right w-1/4">
                                            {{ formatCurrency(item.total) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-red-100 font-bold">
                                        <td class="px-6 py-3 text-sm text-red-900" colspan="2">TOTAL SALIDAS</td>
                                        <td class="px-6 py-3 text-sm text-red-900 text-right">{{ formatCurrency(planilla.total_salidas) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- BALANCE -->
                        <div 
                            class="px-6 py-4"
                            :class="planilla.balance >= 0 ? 'bg-blue-100' : 'bg-orange-100'"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-lg font-bold" :class="planilla.balance >= 0 ? 'text-blue-900' : 'text-orange-900'">
                                        BALANCE DEL MES
                                    </span>
                                    <span 
                                        class="ml-3 text-xs font-medium px-2 py-1 rounded-full"
                                        :class="planilla.balance >= 0 ? 'bg-blue-200 text-blue-800' : 'bg-orange-200 text-orange-800'"
                                    >
                                        {{ planilla.balance >= 0 ? 'Positivo' : 'Negativo' }}
                                    </span>
                                </div>
                                <span 
                                    class="text-2xl font-bold"
                                    :class="planilla.balance >= 0 ? 'text-blue-700' : 'text-orange-700'"
                                >
                                    {{ formatCurrency(planilla.balance) }}
                                </span>
                            </div>
                        </div>

                        <!-- PAGOS RECIBIDOS (Información adicional) -->
                        <div class="px-6 py-4 bg-gray-50">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 text-gray-600">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>Pagos recibidos de clientes ({{ planilla.pagos_recibidos.cantidad }} cobros)</span>
                                </div>
                                <span class="font-semibold text-gray-900">{{ formatCurrency(planilla.pagos_recibidos.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DETALLE DE GASTOS POR CATEGORÍA (si hay gastos) -->
                <div v-if="gastosPorCategoria.length > 0" class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Detalle de Gastos y Pérdidas</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="gasto in gastosPorCategoria" :key="gasto.nombre" class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ gasto.nombre }}</td>
                                <td class="px-6 py-3 text-center">
                                    <span 
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                        :class="gasto.tipo === 'gasto' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'"
                                    >
                                        {{ gasto.tipo === 'gasto' ? 'Gasto' : 'Pérdida' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500 text-center">{{ gasto.cantidad }}</td>
                                <td class="px-6 py-3 text-sm font-medium text-right" :class="gasto.tipo === 'gasto' ? 'text-blue-600' : 'text-red-600'">
                                    {{ formatCurrency(gasto.total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- GRÁFICOS ESTADÍSTICOS -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Estadísticas</h3>
                        <div>
                            <label class="text-sm text-gray-600 mr-2">Mostrar:</label>
                            <select
                                v-model="form.tipo_grafico"
                                class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="tipo in tiposGrafico" :key="tipo.value" :value="tipo.value">
                                    {{ tipo.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Gráfico de Evolución -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-4">Evolución Diaria</h4>
                            <div class="h-64">
                                <Line 
                                    v-if="graficos.evolucion" 
                                    :data="graficos.evolucion" 
                                    :options="lineChartOptions" 
                                />
                            </div>
                        </div>

                        <!-- Gráfico de Distribución -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-4">
                                Distribución {{ tituloDistribucion[form.tipo_grafico] }}
                            </h4>
                            <div class="h-64">
                                <Doughnut 
                                    v-if="graficos.distribucion && graficos.distribucion.labels.length > 0" 
                                    :data="graficos.distribucion" 
                                    :options="doughnutOptions" 
                                />
                                <div v-else class="h-full flex items-center justify-center text-gray-500">
                                    No hay datos para mostrar
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
