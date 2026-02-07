<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import ExportDropdown from '@/Components/ExportDropdown.vue';
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
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: (ctx) => '  ' + formatCurrency(ctx.raw)
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            title: { display: true, text: 'Importe ($)', font: { size: 11 }, color: '#6B7280' },
            grid: { color: 'rgba(0,0,0,0.04)' },
            ticks: {
                callback: (value) => '$' + formatNumber(value),
                font: { size: 11 },
                color: '#6B7280',
                maxTicksLimit: 6
            }
        },
        x: {
            title: { display: true, text: 'Día del mes', font: { size: 11 }, color: '#6B7280' },
            grid: { display: false },
            ticks: { font: { size: 11 } }
        }
    }
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '55%',
    plugins: {
        legend: {
            position: 'right',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 14,
                font: { size: 12 },
                generateLabels: (chart) => {
                    const data = chart.data;
                    const total = data.datasets[0].data.reduce((a, b) => a + Number(b), 0);
                    return data.labels.map((label, i) => {
                        const value = data.datasets[0].data[i];
                        const pct = total > 0 ? ((value / total) * 100).toFixed(0) : 0;
                        return {
                            text: `${label} (${pct}%)`,
                            fillStyle: data.datasets[0].backgroundColor[i],
                            strokeStyle: data.datasets[0].backgroundColor[i],
                            hidden: false,
                            index: i,
                            pointStyle: 'circle',
                        };
                    });
                },
            }
        },
        tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: (ctx) => {
                    const total = ctx.dataset.data.reduce((a, b) => a + Number(b), 0);
                    const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                    return `  ${formatCurrency(ctx.raw)} (${pct}%)`;
                },
                footer: (tooltipItems) => {
                    const total = tooltipItems[0].dataset.data.reduce((a, b) => a + Number(b), 0);
                    return `Total: ${formatCurrency(total)}`;
                }
            }
        }
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
                        Resumen de entradas y salidas del período
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

                        <ExportDropdown
                            :exportUrl="route('reportes.mensual.exportar')"
                            :params="{ mes: form.mes, anio: form.anio }"
                        />
                    </div>
                </div>

                <!-- RESUMEN MENSUAL -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <!-- Encabezado -->
                    <div class="bg-gray-800 text-white px-6 py-4 text-center">
                        <h3 class="text-lg font-bold uppercase tracking-wider">
                            Resumen Mensual
                        </h3>
                        <p class="text-gray-300 text-sm mt-1">{{ periodo.nombre }}</p>
                    </div>

                    <!-- Cabecera de tabla -->
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/2">Concepto</th>
                                <th class="px-6 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/4">Operaciones</th>
                                <th class="px-6 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/4">Importe</th>
                            </tr>
                        </thead>
                    </table>

                    <div class="divide-y divide-gray-200">
                        <!-- ENTRADAS -->
                        <div class="bg-emerald-50">
                            <div class="px-6 py-3 bg-emerald-100 border-b border-emerald-200">
                                <h4 class="text-sm font-bold text-emerald-800 uppercase tracking-wider">Entradas</h4>
                            </div>
                            <table class="min-w-full">
                                <tbody>
                                    <tr v-for="(item, index) in planilla.entradas" :key="'e-' + index" class="border-b border-emerald-100 hover:bg-emerald-100/50 transition-colors">
                                        <td class="px-6 py-3 text-sm text-gray-700 w-1/2">{{ item.concepto }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-500 text-center w-1/4">
                                            <span v-if="item.cantidad != null">{{ formatNumber(item.cantidad) }}</span>
                                        </td>
                                        <td class="px-6 py-3 text-sm font-medium text-emerald-700 text-right w-1/4">
                                            {{ formatCurrency(item.total) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-emerald-200/60 font-bold">
                                        <td class="px-6 py-3 text-sm text-emerald-900" colspan="2">TOTAL ENTRADAS</td>
                                        <td class="px-6 py-3 text-sm text-emerald-900 text-right">{{ formatCurrency(planilla.total_entradas) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- SALIDAS -->
                        <div class="bg-red-50">
                            <div class="px-6 py-3 bg-red-100 border-b border-red-200">
                                <h4 class="text-sm font-bold text-red-800 uppercase tracking-wider">Salidas</h4>
                            </div>
                            <table v-if="planilla.salidas.length > 0" class="min-w-full">
                                <tbody>
                                    <tr v-for="(item, index) in planilla.salidas" :key="'s-' + index" class="border-b border-red-100 hover:bg-red-100/50 transition-colors">
                                        <td class="px-6 py-3 text-sm text-gray-700 w-1/2">{{ item.concepto }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-500 text-center w-1/4">
                                            <span v-if="item.cantidad != null">{{ formatNumber(item.cantidad) }}</span>
                                        </td>
                                        <td class="px-6 py-3 text-sm font-medium text-red-700 text-right w-1/4">
                                            {{ formatCurrency(item.total) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-red-200/60 font-bold">
                                        <td class="px-6 py-3 text-sm text-red-900" colspan="2">TOTAL SALIDAS</td>
                                        <td class="px-6 py-3 text-sm text-red-900 text-right">{{ formatCurrency(planilla.total_salidas) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                            <div v-else class="px-6 py-3 text-sm text-gray-500 italic">
                                No se registraron salidas en el período.
                            </div>
                        </div>

                        <!-- RESULTADO -->
                        <div
                            class="px-6 py-5"
                            :class="planilla.balance >= 0 ? 'bg-blue-100' : 'bg-orange-100'"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-lg font-bold" :class="planilla.balance >= 0 ? 'text-blue-900' : 'text-orange-900'">
                                        RESULTADO DEL MES
                                    </span>
                                    <span
                                        class="ml-3 text-xs font-medium px-2.5 py-1 rounded-full"
                                        :class="planilla.balance >= 0 ? 'bg-blue-200 text-blue-800' : 'bg-orange-200 text-orange-800'"
                                    >
                                        {{ planilla.balance >= 0 ? 'Superávit' : 'Déficit' }}
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
                    </div>
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
                                    v-if="graficos.evolucion && graficos.evolucion.datasets[0].data.some(v => v > 0)" 
                                    :key="'line-' + form.tipo_grafico"
                                    :data="graficos.evolucion" 
                                    :options="lineChartOptions" 
                                />
                                <div v-else class="h-full flex items-center justify-center text-sm text-gray-400">
                                    Sin movimientos en el período
                                </div>
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
                                    :key="'doughnut-' + form.tipo_grafico"
                                    :data="graficos.distribucion" 
                                    :options="doughnutOptions" 
                                />
                                <div v-else class="h-full flex items-center justify-center text-sm text-gray-400">
                                    Sin movimientos en el período
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
