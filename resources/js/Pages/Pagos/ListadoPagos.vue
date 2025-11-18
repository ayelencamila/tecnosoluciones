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
    clientes_filtro: Array,
});

const form = ref({
    search: props.filters.search || '',
    cliente_id: props.filters.cliente_id || '',
    fecha_desde: props.filters.fecha_desde || '',
    fecha_hasta: props.filters.fecha_hasta || '',
});

// --- CORRECCIÓN: Formato para tu SelectInput (Kendall) ---
const clientesOptions = computed(() => [
    { value: '', label: 'Todos los Clientes' },
    ...props.clientes_filtro.map(c => ({
        value: c.clienteID,
        label: `${c.apellido}, ${c.nombre}`
    }))
]);

// ... (el resto de tu script setup está perfecto) ...
watch(form, debounce(() => {
    router.get(route('pagos.index'), form.value, {
        preserveState: true,
        replace: true,
    });
}, 300), { deep: true });
const resetFilters = () => {
    form.value = { search: '', cliente_id: '', fecha_desde: '', fecha_hasta: '' };
};
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-AR', {
        year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute:'2-digit'
    });
};
</script>

<template>
    <Head title="Listado de Pagos" />

    <AppLayout> <!-- <-- CORREGIDO -->
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Cobranzas</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                        <div class="flex-1 w-full">
                            <TextInput v-model="form.search" placeholder="Buscar N° Recibo..." class="w-full" />
                        </div>
                        <Link :href="route('pagos.create')">
                            <PrimaryButton>+ Registrar Pago</PrimaryButton>
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        
                        <!-- CORRECCIÓN: Usando SelectInput con :options -->
                        <SelectInput 
                            v-model="form.cliente_id" 
                            class="w-full"
                            :options="clientesOptions"
                        />
                        
                        <TextInput type="date" v-model="form.fecha_desde" class="w-full" placeholder="Desde" />
                        <TextInput type="date" v-model="form.fecha_hasta" class="w-full" placeholder="Hasta" />
                        <button @click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 underline">
                            Limpiar Filtros
                        </button>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-if="pagos.data.length === 0" class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No se encontraron pagos</h3>
                        <p class="mt-1 text-sm text-gray-500">No hay pagos registrados con los filtros actuales.</p>
                        <div class="mt-6">
                            <Link :href="route('pagos.create')">
                                <PrimaryButton>+ Registrar Primer Pago</PrimaryButton>
                            </Link>
                        </div>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Recibo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="pago in pagos.data" :key="pago.pago_id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(pago.fecha_pago) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ pago.numero_recibo }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ pago.cliente?.apellido }}, {{ pago.cliente?.nombre }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                        {{ formatCurrency(pago.monto) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                        {{ pago.metodo_pago }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="pago.anulado" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Anulado
                                        </span>
                                        <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Confirmado
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('pagos.show', pago.pago_id)" class="text-indigo-600 hover:text-indigo-900">
                                            Ver Recibo
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Paginación -->
                        <div v-if="pagos.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                            <div class="flex flex-col sm:flex-row items-center justify-between">
                                <div class="text-sm text-gray-700 mb-2 sm:mb-0">
                                    Mostrando 
                                    <span class="font-medium">{{ pagos.from }}</span>
                                    a 
                                    <span class="font-medium">{{ pagos.to }}</span>
                                    de 
                                    <span class="font-medium">{{ pagos.total }}</span>
                                    resultados
                                </div>
                                <div class="flex space-x-1">
                                    <template v-for="(link, index) in pagos.links" :key="index">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            class="px-3 py-2 text-sm font-medium rounded-md"
                                            :class="[
                                                link.active 
                                                    ? 'bg-indigo-600 text-white' 
                                                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                            ]"
                                            v-html="link.label"
                                        />
                                        <span
                                            v-else
                                            class="px-3 py-2 text-sm font-medium text-gray-400 cursor-not-allowed"
                                            v-html="link.label"
                                        />
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>