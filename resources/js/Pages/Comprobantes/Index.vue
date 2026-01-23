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

const aplicarFiltros = debounce(() => {
    const params = {};
    if (buscar.value) params.buscar = buscar.value;
    if (tipo.value) params.tipo = tipo.value;
    if (estado.value) params.estado = estado.value;
    
    router.get(route('comprobantes.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

watch([buscar, tipo, estado], aplicarFiltros);

const limpiar = () => {
    buscar.value = '';
    tipo.value = '';
    estado.value = '';
    router.get(route('comprobantes.index'));
};

const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const estadoClase = (nombre) => ({
    'EMITIDO': 'bg-green-100 text-green-800',
    'ANULADO': 'bg-red-100 text-red-800',
    'REEMPLAZADO': 'bg-yellow-100 text-yellow-800',
}[nombre] || 'bg-gray-100 text-gray-800');
</script>

<template>
    <AppLayout title="Comprobantes">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Comprobantes</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Filtros en línea -->
                        <div class="flex flex-wrap gap-4 mb-6">
                            <input
                                v-model="buscar"
                                type="text"
                                placeholder="Buscar por número..."
                                class="flex-1 min-w-[200px] border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <select v-model="tipo" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Todos los tipos</option>
                                <option v-for="t in tipos" :key="t.tipo_id" :value="t.tipo_id">{{ t.nombre }}</option>
                            </select>
                            <select v-model="estado" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Todos los estados</option>
                                <option v-for="e in estados" :key="e.estado_id" :value="e.estado_id">{{ e.nombre }}</option>
                            </select>
                            <button @click="limpiar" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                                Limpiar
                            </button>
                        </div>

                        <!-- Tabla -->
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Número</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="c in comprobantes.data" :key="c.comprobante_id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ c.numero_correlativo }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ c.tipo_comprobante?.nombre }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ formatearFecha(c.fecha_emision) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ c.usuario?.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 py-1 text-xs rounded-full', estadoClase(c.estado_comprobante?.nombre)]">
                                            {{ c.estado_comprobante?.nombre }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <Link :href="route('comprobantes.show', c.comprobante_id)" class="text-indigo-600 hover:text-indigo-900">
                                            Ver
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="comprobantes.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        No se encontraron comprobantes.
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Paginación -->
                        <div v-if="comprobantes.last_page > 1" class="mt-4 flex justify-center gap-1">
                            <template v-for="link in comprobantes.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="['px-3 py-1 text-sm rounded', link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100']"
                                    preserve-state
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
