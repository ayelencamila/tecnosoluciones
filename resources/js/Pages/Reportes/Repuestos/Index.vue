<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import ExportDropdown from '@/Components/ExportDropdown.vue';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    ArcElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler
} from 'chart.js';
import axios from 'axios';
import { debounce } from 'lodash';

ChartJS.register(Title, Tooltip, Legend, BarElement, ArcElement, LineElement, PointElement, CategoryScale, LinearScale, Filler);

const props = defineProps({
    detalles: Object,
    kpis: Object,
    graficos: Object,
    filters: Object,
    tecnicoSeleccionado: Object,
});

const form = ref({
    fecha_desde: props.filters.fecha_desde || '',
    fecha_hasta: props.filters.fecha_hasta || '',
    tecnico_id: props.filters.tecnico_id || '',
});

// Buscador de técnicos
const searchTecnico = ref('');
const tecnicosResultados = ref([]);
const showTecnicoDropdown = ref(false);
const buscandoTecnico = ref(false);
const tecnicoActual = ref(props.tecnicoSeleccionado || null);

onMounted(() => {
    if (props.tecnicoSeleccionado) {
        searchTecnico.value = props.tecnicoSeleccionado.name;
    }
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (e) => {
    if (!e.target.closest('.tecnico-search-container')) {
        showTecnicoDropdown.value = false;
    }
};

const buscarTecnicos = debounce(async () => {
    if (searchTecnico.value.length < 2) {
        tecnicosResultados.value = [];
        showTecnicoDropdown.value = false;
        return;
    }
    buscandoTecnico.value = true;
    try {
        const response = await axios.get(route('api.usuarios.buscar'), {
            params: { q: searchTecnico.value, tecnicos: true }
        });
        tecnicosResultados.value = response.data;
        showTecnicoDropdown.value = true;
    } catch (error) {
        console.error('Error buscando técnicos:', error);
    } finally {
        buscandoTecnico.value = false;
    }
}, 300);

const seleccionarTecnico = (tecnico) => {
    tecnicoActual.value = tecnico;
    searchTecnico.value = tecnico.name;
    form.value.tecnico_id = tecnico.id;
    showTecnicoDropdown.value = false;
};

const limpiarTecnico = () => {
    tecnicoActual.value = null;
    searchTecnico.value = '';
    form.value.tecnico_id = '';
    tecnicosResultados.value = [];
};

watch(() => [form.value.fecha_desde, form.value.fecha_hasta, form.value.tecnico_id], () => {
    router.get(route('reportes.repuestos'), form.value, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value || 0);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-AR').format(value || 0);
};

// Chart options — profesionales y estratégicos
const barTopOptions = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            titleFont: { size: 13 },
            bodyFont: { size: 12 },
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: (ctx) => `  ${formatNumber(ctx.raw)} unidades`
            }
        }
    },
    scales: {
        x: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.04)' },
            ticks: { font: { size: 11 }, color: '#6B7280' }
        },
        y: {
            grid: { display: false },
            ticks: {
                font: { size: 11, weight: '500' },
                color: '#374151',
                callback: function(value) {
                    const label = this.getLabelForValue(value);
                    return label.length > 20 ? label.substring(0, 18) + '…' : label;
                }
            }
        }
    }
};

const doughnutTecnicoOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '60%',
    plugins: {
        legend: {
            position: 'right',
            labels: {
                padding: 16,
                usePointStyle: true,
                pointStyle: 'circle',
                font: { size: 12, weight: '500' }
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
                }
            }
        }
    }
};

const lineEvolucionOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            position: 'top',
            labels: { usePointStyle: true, pointStyle: 'circle', padding: 20, font: { size: 12 } }
        },
        tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: (ctx) => {
                    if (ctx.datasetIndex === 1) return `  ${ctx.dataset.label}: ${formatCurrency(ctx.raw)}`;
                    return `  ${ctx.dataset.label}: ${formatNumber(ctx.raw)}`;
                }
            }
        }
    },
    scales: {
        x: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 11 } } },
        y: {
            type: 'linear',
            display: true,
            position: 'left',
            beginAtZero: true,
            title: { display: true, text: 'Unidades', font: { size: 12, weight: '500' }, color: '#BE185D' },
            grid: { color: 'rgba(0,0,0,0.04)' },
            ticks: { font: { size: 11 }, color: '#BE185D' }
        },
        y1: {
            type: 'linear',
            display: true,
            position: 'right',
            beginAtZero: true,
            title: { display: true, text: 'Costo ($)', font: { size: 12, weight: '500' }, color: '#7C3AED' },
            grid: { drawOnChartArea: false },
            ticks: { font: { size: 11 }, color: '#7C3AED', callback: (v) => '$' + formatNumber(v) }
        }
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Reporte de Uso de Repuestos" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Reporte de Uso de Repuestos
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Análisis de materiales consumidos en reparaciones</p>
                </div>
                <span class="text-sm text-gray-500">
                    {{ new Date(form.fecha_desde).toLocaleDateString() }} — {{ new Date(form.fecha_hasta).toLocaleDateString() }}
                </span>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- KPIs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-pink-600">
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider">Total Unidades</div>
                        <div class="text-3xl font-bold text-gray-900 mt-2">{{ formatNumber(kpis.total_unidades) }}</div>
                        <div class="text-xs text-pink-600 mt-1 font-medium">Repuestos consumidos</div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-purple-600">
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider">Costo Total</div>
                        <div class="text-3xl font-bold text-gray-900 mt-2">{{ formatCurrency(kpis.costo_total) }}</div>
                        <div class="text-xs text-purple-600 mt-1 font-medium">Inversión en materiales</div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-indigo-500">
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider">Repuestos Distintos</div>
                        <div class="text-3xl font-bold text-gray-900 mt-2">{{ kpis.repuestos_distintos }}</div>
                        <div class="text-xs text-indigo-500 mt-1 font-medium">Variedad de artículos</div>
                    </div>
                    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-cyan-500">
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider">Reparaciones c/ Rep.</div>
                        <div class="text-3xl font-bold text-gray-900 mt-2">{{ kpis.reparaciones_con_repuestos }}</div>
                        <div class="text-xs text-cyan-600 mt-1 font-medium">Que usaron materiales</div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white shadow-sm rounded-xl p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Desde</label>
                            <input type="date" v-model="form.fecha_desde" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-pink-500 focus:ring-pink-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hasta</label>
                            <input type="date" v-model="form.fecha_hasta" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-pink-500 focus:ring-pink-500">
                        </div>

                        <!-- Buscador de Técnicos -->
                        <div class="relative tecnico-search-container">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Técnico</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    v-model="searchTecnico"
                                    @input="buscarTecnicos"
                                    @focus="searchTecnico.length >= 2 && (showTecnicoDropdown = true)"
                                    placeholder="Buscar técnico..."
                                    autocomplete="off"
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-pink-500 focus:ring-pink-500 pr-8"
                                />
                                <button
                                    v-if="tecnicoActual"
                                    @click="limpiarTecnico"
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                                <div v-if="buscandoTecnico" class="absolute inset-y-0 right-0 flex items-center pr-2">
                                    <svg class="animate-spin h-4 w-4 text-pink-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div
                                v-if="showTecnicoDropdown && tecnicosResultados.length > 0"
                                class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto"
                            >
                                <ul class="py-1">
                                    <li
                                        v-for="tecnico in tecnicosResultados"
                                        :key="tecnico.id"
                                        @click="seleccionarTecnico(tecnico)"
                                        class="px-3 py-2 hover:bg-pink-50 cursor-pointer text-sm"
                                    >
                                        <div class="font-medium text-gray-900">{{ tecnico.name }}</div>
                                        <div class="text-xs text-gray-500">{{ tecnico.rol?.nombre || 'Sin rol' }}</div>
                                    </li>
                                </ul>
                            </div>
                            <div
                                v-if="showTecnicoDropdown && tecnicosResultados.length === 0 && searchTecnico.length >= 2 && !buscandoTecnico"
                                class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg p-3 text-sm text-gray-500"
                            >
                                No se encontraron técnicos
                            </div>
                        </div>

                        <ExportDropdown
                            :exportUrl="route('reportes.repuestos.exportar')"
                            :params="form"
                        />
                    </div>
                </div>

                <!-- Gráficos -->
                <div v-if="kpis.total_unidades > 0" class="space-y-6">
                    <!-- Fila 1: Top Repuestos + Distribución Técnicos -->
                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                        <div class="lg:col-span-3 bg-white rounded-xl shadow-sm p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-base font-bold text-gray-800">Top 10 Repuestos Más Usados</h3>
                                <span class="text-xs px-2.5 py-1 bg-pink-100 text-pink-700 rounded-full font-semibold">Por Cantidad</span>
                            </div>
                            <div class="h-80 relative">
                                <Bar :data="graficos.top_repuestos" :options="barTopOptions" />
                            </div>
                        </div>

                        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-base font-bold text-gray-800">Costo por Técnico</h3>
                                <span class="text-xs px-2.5 py-1 bg-purple-100 text-purple-700 rounded-full font-semibold">Distribución</span>
                            </div>
                            <div class="h-80 relative flex justify-center">
                                <Doughnut :data="graficos.tecnicos" :options="doughnutTecnicoOptions" />
                            </div>
                        </div>
                    </div>

                    <!-- Fila 2: Evolución temporal -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-bold text-gray-800">Evolución de Consumo</h3>
                            <span class="text-xs px-2.5 py-1 bg-indigo-100 text-indigo-700 rounded-full font-semibold">Unidades + Costo</span>
                        </div>
                        <div class="h-72 relative">
                            <Line :data="graficos.evolucion" :options="lineEvolucionOptions" />
                        </div>
                    </div>
                </div>

                <!-- Tabla de detalle -->
                <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Detalle de Repuestos Usados</h3>
                        <span class="text-xs text-gray-500" v-if="detalles.from">{{ detalles.from }}–{{ detalles.to }} de {{ detalles.total }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Reparación</th>
                                    <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Repuesto</th>
                                    <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Cantidad</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Precio Unit.</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Subtotal</th>
                                    <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Técnico</th>
                                    <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden xl:table-cell">Cliente</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in detalles.data" :key="item.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-3 whitespace-nowrap text-sm font-bold text-pink-600">
                                        #{{ item.reparacion?.codigo_reparacion ?? item.reparacion_id }}
                                    </td>
                                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-600">
                                        {{ item.reparacion?.fecha_ingreso ? new Date(item.reparacion.fecha_ingreso).toLocaleDateString() : '—' }}
                                    </td>
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ item.producto?.nombre || 'Producto eliminado' }}</div>
                                        <div class="text-xs text-gray-400">{{ item.producto?.categoria?.nombre || '' }}</div>
                                    </td>
                                    <td class="px-5 py-3 whitespace-nowrap text-center text-sm font-semibold text-gray-900">
                                        {{ item.cantidad }}
                                    </td>
                                    <td class="px-5 py-3 whitespace-nowrap text-right text-sm text-gray-600">
                                        {{ formatCurrency(item.precio_unitario) }}
                                    </td>
                                    <td class="px-5 py-3 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                        {{ formatCurrency(item.subtotal) }}
                                    </td>
                                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-600 hidden lg:table-cell">
                                        {{ item.reparacion?.tecnico?.name || 'Sin asignar' }}
                                    </td>
                                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-600 hidden xl:table-cell">
                                        {{ item.reparacion?.cliente?.apellido ? `${item.reparacion.cliente.apellido}, ${item.reparacion.cliente.nombre}` : '—' }}
                                    </td>
                                </tr>
                                <tr v-if="detalles.data.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="h-10 w-10 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                            </svg>
                                            <p class="text-sm font-medium">No se encontraron repuestos usados para los filtros seleccionados.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-200 bg-gray-50" v-if="detalles.links">
                        <Pagination :links="detalles.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
