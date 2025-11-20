<script setup>
import { ref, watch, computed } from 'vue'; 
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { debounce } from 'lodash';

const props = defineProps({
    pagos: Object,
    filters: Object,
    // FIX: Valor por defecto para evitar el error de 'map' undefined
    clientes_filtro: {
        type: Array,
        default: () => [], 
    },
});

// Estado del formulario de filtros
const form = ref({
    search: props.filters.search || '',
    cliente_id: props.filters.cliente_id || '',
    fecha_desde: props.filters.fecha_desde || '',
    fecha_hasta: props.filters.fecha_hasta || '',
});

// Opciones para el select de clientes (Protegido contra undefined)
const clientesOptions = computed(() => {
    const defaultOption = { value: '', label: 'Todos los Clientes' };
    if (!props.clientes_filtro || !Array.isArray(props.clientes_filtro)) {
        return [defaultOption];
    }
    return [
        defaultOption,
        ...props.clientes_filtro.map(c => ({ value: c.clienteID, label: `${c.apellido}, ${c.nombre}` }))
    ];
});

// Watcher
watch(form, debounce(() => {
    router.get(route('pagos.index'), form.value, { preserveState: true, replace: true });
}, 300), { deep: true });

const resetFilters = () => {
    form.value = { search: '', cliente_id: '', fecha_desde: '', fecha_hasta: '' };
};

// --- HELPERS VISUALES ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-AR', {
        year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute:'2-digit'
    });
};

const getMetodoPagoBadgeClass = (metodo) => {
    switch (metodo?.toLowerCase()) {
        case 'efectivo': return 'bg-green-100 text-green-800';
        case 'transferencia': return 'bg-blue-100 text-blue-800';
        case 'cheque': return 'bg-yellow-100 text-yellow-800';
        case 'tarjeta': return 'bg-purple-100 text-purple-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;'; 
    if (index === totalLinks - 1) return '&raquo;'; 
    return label; 
};
</script>

<template>
    <Head title="Gestión de Pagos" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Pagos</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                        <div class="flex-1 w-full">
                            <TextInput v-model="form.search" placeholder="Buscar por N° Recibo, Cliente o DNI..." class="w-full" />
                        </div>
                        <Link :href="route('pagos.create')">
                            <PrimaryButton>+ Registrar Pago</PrimaryButton>
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <SelectInput v-model="form.cliente_id" class="w-full" :options="clientesOptions" />
                        <TextInput type="date" v-model="form.fecha_desde" class="w-full" placeholder="Desde" />
                        <TextInput type="date" v-model="form.fecha_hasta" class="w-full" placeholder="Hasta" />
                        
                        <div class="flex justify-end items-center">
                            <button @click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 underline text-right">
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha / Recibo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="pago in pagos.data" :key="pago.pago_id" class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ formatDate(pago.fecha_pago) }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ pago.numero_recibo }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 font-medium">{{ pago.cliente?.apellido }}, {{ pago.cliente?.nombre }}</div>
                                        <div class="text-xs text-gray-500">DNI: {{ pago.cliente?.DNI }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getMetodoPagoBadgeClass(pago.metodo_pago)">
                                            {{ pago.metodo_pago.toUpperCase() }}
                                        </span>
                                        <div v-if="pago.anulado" class="text-xs text-red-600 font-bold mt-1">ANULADO</div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="text-sm font-bold text-gray-900">{{ formatCurrency(pago.monto) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('pagos.show', pago.pago_id)" class="text-indigo-600 hover:text-indigo-900 font-bold" title="Ver Recibo">
                                            Ver Detalle &rarr;
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="pagos.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <span class="text-lg font-medium">No se encontraron pagos registrados</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200" v-if="pagos.links.length > 3">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in pagos.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-medium transition-all duration-150"
                                    :class="link.active 
                                        ? 'bg-indigo-600 text-white shadow-md ring-2 ring-indigo-300' 
                                        : 'bg-white text-gray-600 border border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'"
                                >
                                    <span v-html="getPaginationLabel(link.label, k, pagos.links.length)"></span>
                                </Link>
                                <span 
                                    v-else 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm text-gray-300 border border-gray-200 cursor-not-allowed" 
                                    v-html="getPaginationLabel(link.label, k, pagos.links.length)"
                                ></span>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>