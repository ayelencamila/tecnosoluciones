<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    gastos: Object,
    categorias: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const categoriaId = ref(props.filters.categoria_id || '');
const tipo = ref(props.filters.tipo || '');
const mes = ref(props.filters.mes || new Date().getMonth() + 1);
const anio = ref(props.filters.anio || new Date().getFullYear());
const estado = ref(props.filters.estado || '');

const aniosDisponibles = computed(() => {
    const anioActual = new Date().getFullYear();
    const anios = [];
    for (let i = anioActual; i >= anioActual - 5; i--) {
        anios.push(i);
    }
    return anios;
});

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

const applyFilters = debounce(() => {
    router.get(route('gastos.index'), {
        search: search.value,
        categoria_id: categoriaId.value,
        tipo: tipo.value,
        mes: mes.value,
        anio: anio.value,
        estado: estado.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, categoriaId, tipo, mes, anio, estado], applyFilters);

const confirmAnular = (gasto) => {
    if (confirm(`¿Está seguro de anular este gasto de $${formatNumber(gasto.monto)}?`)) {
        router.patch(route('gastos.anular', gasto.gasto_id));
    }
};

const confirmDelete = (gasto) => {
    if (confirm(`¿Está seguro de eliminar permanentemente este gasto?`)) {
        router.delete(route('gastos.destroy', gasto.gasto_id));
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value || 0);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-AR').format(value || 0);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-AR');
};

const getTipoBadge = (tipo) => {
    return tipo === 'gasto' 
        ? 'bg-blue-100 text-blue-800' 
        : 'bg-red-100 text-red-800';
};
</script>

<template>
    <AppLayout>
        <Head title="Gastos y Pérdidas" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Gastos y Pérdidas
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Registro y control de gastos operativos y pérdidas
                    </p>
                </div>
                <Link
                    :href="route('gastos.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Registrar Gasto
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Filtros -->
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-2">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por descripción o comprobante..."
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                        <div>
                            <select
                                v-model="categoriaId"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="">Todas las categorías</option>
                                <option v-for="cat in categorias" :key="cat.categoria_gasto_id" :value="cat.categoria_gasto_id">
                                    {{ cat.nombre }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <select
                                v-model="tipo"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="">Todos los tipos</option>
                                <option value="gasto">Gastos</option>
                                <option value="perdida">Pérdidas</option>
                            </select>
                        </div>
                        <div>
                            <select
                                v-model="mes"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="m in meses" :key="m.value" :value="m.value">{{ m.label }}</option>
                            </select>
                        </div>
                        <div>
                            <select
                                v-model="anio"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="a in aniosDisponibles" :key="a" :value="a">{{ a }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Categoría
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Descripción
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Comprobante
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Monto
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr 
                                v-for="gasto in gastos.data" 
                                :key="gasto.gasto_id" 
                                class="hover:bg-gray-50"
                                :class="{ 'bg-red-50 opacity-60': gasto.anulado }"
                            >
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(gasto.fecha) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span 
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                            :class="getTipoBadge(gasto.categoria?.tipo)"
                                        >
                                            {{ gasto.categoria?.tipo === 'perdida' ? 'Pérdida' : 'Gasto' }}
                                        </span>
                                        <span class="text-sm text-gray-900">{{ gasto.categoria?.nombre }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-900">{{ gasto.descripcion }}</span>
                                    <span v-if="gasto.anulado" class="ml-2 text-xs text-red-600 font-medium">(ANULADO)</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ gasto.comprobante || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span 
                                        class="text-sm font-semibold"
                                        :class="gasto.categoria?.tipo === 'perdida' ? 'text-red-600' : 'text-orange-600'"
                                    >
                                        {{ formatCurrency(gasto.monto) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2" v-if="!gasto.anulado">
                                        <Link
                                            :href="route('gastos.edit', gasto.gasto_id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            title="Editar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="confirmAnular(gasto)"
                                            class="text-orange-600 hover:text-orange-900"
                                            title="Anular"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400">Sin acciones</span>
                                </td>
                            </tr>
                            <tr v-if="gastos.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    No se encontraron gastos para los filtros seleccionados
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <div v-if="gastos.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">
                                Mostrando {{ gastos.from }} a {{ gastos.to }} de {{ gastos.total }} registros
                            </span>
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in gastos.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    class="px-3 py-1 text-sm rounded-md"
                                    :class="link.active ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                    v-html="link.label"
                                    :disabled="!link.url"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
