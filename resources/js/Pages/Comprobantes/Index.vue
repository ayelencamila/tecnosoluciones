<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

const props = defineProps({
    comprobantes: Object,
    filtros: Object,
    tipos: Array,
    estados: Array,
});

const buscar = ref(props.filtros?.buscar || '');
const tipo = ref(props.filtros?.tipo || '');
const estado = ref(props.filtros?.estado || '');
const fechaDesde = ref(props.filtros?.fecha_desde || '');
const fechaHasta = ref(props.filtros?.fecha_hasta || '');

const aplicarFiltros = debounce(() => {
    const params = {};
    if (buscar.value) params.buscar = buscar.value;
    if (tipo.value) params.tipo = tipo.value;
    if (estado.value) params.estado = estado.value;
    if (fechaDesde.value) params.fecha_desde = fechaDesde.value;
    if (fechaHasta.value) params.fecha_hasta = fechaHasta.value;
    
    router.get(route('comprobantes.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

watch([buscar, tipo, estado, fechaDesde, fechaHasta], aplicarFiltros);

const limpiar = () => {
    buscar.value = '';
    tipo.value = '';
    estado.value = '';
    fechaDesde.value = '';
    fechaHasta.value = '';
    router.get(route('comprobantes.index'));
};

const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const formatearMonto = (monto) => {
    if (!monto) return '-';
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
    }).format(monto);
};

const estadoClase = (nombre) => ({
    'EMITIDO': 'bg-green-100 text-green-800',
    'ANULADO': 'bg-red-100 text-red-800',
    'REEMPLAZADO': 'bg-yellow-100 text-yellow-800',
}[nombre] || 'bg-gray-100 text-gray-800');

const tipoIcono = (codigo) => ({
    'TICKET': { bg: 'bg-blue-100', text: 'text-blue-600' },
    'RECIBO_PAGO': { bg: 'bg-green-100', text: 'text-green-600' },
    'INGRESO_REPARACION': { bg: 'bg-purple-100', text: 'text-purple-600' },
    'ENTREGA_REPARACION': { bg: 'bg-orange-100', text: 'text-orange-600' },
    'NOTA_CREDITO_INTERNA': { bg: 'bg-red-100', text: 'text-red-600' },
    'ORDEN_COMPRA': { bg: 'bg-indigo-100', text: 'text-indigo-600' },
}[codigo] || { bg: 'bg-gray-100', text: 'text-gray-600' });
</script>

<template>
    <AppLayout title="Comprobantes Internos">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <!-- Icono documento -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Comprobantes Internos
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Filtros mejorados -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                                <!-- Buscar por número -->
                                <div class="lg:col-span-2">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Buscar por número</label>
                                    <input
                                        v-model="buscar"
                                        type="text"
                                        placeholder="Ej: TK-000123"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                    />
                                </div>
                                
                                <!-- Tipo -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
                                    <select v-model="tipo" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                        <option value="">Todos</option>
                                        <option v-for="t in tipos" :key="t.tipo_id" :value="t.tipo_id">{{ t.nombre }}</option>
                                    </select>
                                </div>
                                
                                <!-- Estado -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
                                    <select v-model="estado" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                        <option value="">Todos</option>
                                        <option v-for="e in estados" :key="e.estado_id" :value="e.estado_id">{{ e.nombre }}</option>
                                    </select>
                                </div>
                                
                                <!-- Fecha desde -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
                                    <input
                                        v-model="fechaDesde"
                                        type="date"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                    />
                                </div>
                                
                                <!-- Fecha hasta -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
                                    <input
                                        v-model="fechaHasta"
                                        type="date"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                    />
                                </div>
                            </div>
                            
                            <!-- Botón limpiar -->
                            <div class="mt-3 flex justify-end">
                                <button 
                                    @click="limpiar" 
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-md transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Limpiar filtros
                                </button>
                            </div>
                        </div>

                        <!-- Info de resultados -->
                        <div class="mb-4 text-sm text-gray-500">
                            {{ comprobantes.total }} comprobante(s) encontrado(s)
                        </div>

                        <!-- Tabla -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="c in comprobantes.data" :key="c.comprobante_id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="font-mono font-medium text-gray-900">{{ c.numero_correlativo }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span 
                                                :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', tipoIcono(c.tipo_comprobante?.codigo).bg, tipoIcono(c.tipo_comprobante?.codigo).text]"
                                            >
                                                {{ c.tipo_comprobante?.nombre }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                            {{ c.cliente_nombre || '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                            {{ formatearMonto(c.monto) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatearFecha(c.fecha_emision) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                            {{ c.usuario?.name || 'Sistema' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span :class="['px-2 py-1 text-xs rounded-full font-medium', estadoClase(c.estado_comprobante?.nombre)]">
                                                {{ c.estado_comprobante?.nombre }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <!-- Ver detalle -->
                                                <Link 
                                                    :href="route('comprobantes.show', c.comprobante_id)" 
                                                    class="text-indigo-600 hover:text-indigo-900 p-1 rounded hover:bg-indigo-50"
                                                    title="Ver detalle"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </Link>
                                                <!-- Imprimir -->
                                                <a 
                                                    v-if="c.url_impresion"
                                                    :href="c.url_impresion" 
                                                    target="_blank"
                                                    class="text-green-600 hover:text-green-900 p-1 rounded hover:bg-green-50"
                                                    title="Imprimir"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="comprobantes.data.length === 0">
                                        <td colspan="8" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center text-gray-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p class="text-lg font-medium">No se encontraron comprobantes</p>
                                                <p class="text-sm">Prueba cambiando los filtros de búsqueda</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div v-if="comprobantes.last_page > 1" class="mt-6 flex justify-center gap-1">
                            <template v-for="link in comprobantes.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="['px-3 py-2 text-sm rounded-md transition-colors', link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100']"
                                    preserve-state
                                />
                                <span 
                                    v-else 
                                    v-html="link.label" 
                                    class="px-3 py-2 text-sm text-gray-400"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
