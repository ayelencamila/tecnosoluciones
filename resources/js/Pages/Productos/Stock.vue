<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { debounce } from 'lodash';

const props = defineProps({
    stocks: Object,
    categorias: Array,
    depositos: Array,
    filters: Object,
    stats: Object,
});

// --- FILTROS ---
const form = ref({
    search: props.filters.search || '',
    categoria_id: props.filters.categoria_id || '',
    deposito_id: props.filters.deposito_id || '',
    stock_bajo: props.filters.stock_bajo === 'true',
});

const categoriasOptions = computed(() => [{ value: '', label: 'Todas las Categorías' }, ...props.categorias.map(c => ({ value: c.id, label: c.nombre }))]);
const depositosOptions = computed(() => [{ value: '', label: 'Todos los Depósitos' }, ...props.depositos.map(d => ({ value: d.deposito_id, label: d.nombre }))]);

watch(form, debounce(() => {
    router.get(route('productos.stock'), form.value, { preserveState: true, replace: true });
}, 300), { deep: true });

const resetFilters = () => { form.value = { search: '', categoria_id: '', deposito_id: '', stock_bajo: false }; };

// --- LÓGICA DE AJUSTE (MOVIMIENTOS) ---
const showingAdjustmentModal = ref(false);
const stockToAdjust = ref(null);

const adjustmentForm = useForm({
    stock_id: '',
    tipoMovimiento: 'AJUSTE',
    cantidad: '',
    motivo: '',
});

const openAdjustmentModal = (stock) => {
    stockToAdjust.value = stock;
    adjustmentForm.stock_id = stock.stock_id;
    adjustmentForm.tipoMovimiento = 'AJUSTE';
    adjustmentForm.cantidad = ''; 
    adjustmentForm.motivo = '';
    adjustmentForm.clearErrors();
    adjustmentForm.processing = false; 
    showingAdjustmentModal.value = true;
};

const closeAdjustmentModal = () => {
    showingAdjustmentModal.value = false;
    stockToAdjust.value = null;
    adjustmentForm.reset();
};

const submitAdjustment = () => {
    if (!adjustmentForm.cantidad || adjustmentForm.cantidad <= 0) {
        adjustmentForm.setError('cantidad', 'La cantidad debe ser mayor a 0');
        return;
    }
    if (!adjustmentForm.motivo) {
        adjustmentForm.setError('motivo', 'El motivo es obligatorio');
        return;
    }
    adjustmentForm.post(route('stock.movimiento.store'), {
        onSuccess: () => closeAdjustmentModal(),
        preserveScroll: true,
        onFinish: () => adjustmentForm.reset('cantidad', 'motivo'),
    });
};

// --- LÓGICA DE CONFIGURACIÓN (MINIMOS) ---
const showingConfigModal = ref(false);
const stockToConfig = ref(null);

const configForm = useForm({
    stock_minimo: 0,
});

const openConfigModal = (stock) => {
    stockToConfig.value = stock;
    configForm.stock_minimo = stock.stock_minimo;
    configForm.clearErrors();
    configForm.processing = false;
    showingConfigModal.value = true;
};

const closeConfigModal = () => {
    showingConfigModal.value = false;
    stockToConfig.value = null;
    configForm.reset();
};

const submitConfig = () => {
    configForm.put(route('stock.update', stockToConfig.value.stock_id), {
        onSuccess: () => closeConfigModal(),
        preserveScroll: true,
    });
};

const movementTypes = [
    { value: 'ENTRADA', label: 'Entrada (Suma)' },
    { value: 'SALIDA', label: 'Salida (Resta)' },
    { value: 'AJUSTE', label: 'Ajuste (Suma/Corrección)' },
];

// --- PAGINACIÓN ---
const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Consulta de Stock" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Stock</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-blue-500 text-center">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Registros</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.totalItems }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-red-500 text-center">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Stock Bajo / Crítico</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.stockBajo }}</p>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-1">
                            <TextInput v-model="form.search" placeholder="Buscar Producto..." class="w-full" />
                        </div>
                        <SelectInput v-model="form.categoria_id" class="w-full" :options="categoriasOptions" />
                        <SelectInput v-model="form.deposito_id" class="w-full" :options="depositosOptions" />
                        <div class="flex items-center justify-between">
                            <label class="flex items-center text-sm text-gray-700 cursor-pointer select-none">
                                <input type="checkbox" v-model="form.stock_bajo" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                <span class="ml-2 font-medium">Ver Stock Bajo</span>
                            </label>
                            <button @click="resetFilters" class="text-sm text-gray-500 hover:text-gray-900 underline">Limpiar</button>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Categoría</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Depósito</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Disponible</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Mínimo</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="stock in stocks.data" :key="stock.stock_id" class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900">{{ stock.producto?.nombre }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ stock.producto?.codigo }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ stock.producto?.categoria?.nombre }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ stock.deposito?.nombre }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-lg font-bold" :class="stock.cantidad_disponible <= stock.stock_minimo ? 'text-red-600' : 'text-green-600'">
                                            {{ stock.cantidad_disponible }}
                                        </span>
                                        <span class="text-xs text-gray-400 ml-1">{{ stock.producto?.unidadMedida }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-500 font-mono">
                                        {{ stock.stock_minimo }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="stock.cantidad_disponible <= 0" class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Sin Stock</span>
                                        <span v-else-if="stock.cantidad_disponible <= stock.stock_minimo" class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Bajo</span>
                                        <span v-else class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Normal</span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <div class="flex justify-end space-x-2">
                                            <button @click="openConfigModal(stock)" class="text-gray-500 hover:text-indigo-600 transition p-1 border border-gray-200 rounded-md bg-gray-50" title="Configurar Alertas">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </button>

                                            <button @click="openAdjustmentModal(stock)" class="text-indigo-600 hover:text-indigo-900 transition p-1 border border-gray-200 rounded-md bg-gray-50" title="Movimiento de Stock">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="stocks.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                        No hay registros de stock que coincidan con la búsqueda.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in stocks.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    class="px-3 py-1 min-w-[32px] text-center border rounded text-sm font-medium transition-all duration-150"
                                    :class="link.active 
                                        ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500 border-indigo-600' 
                                        : 'bg-white text-gray-600 border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'"
                                >
                                    <span v-html="getPaginationLabel(link.label, k, stocks.links.length)"></span>
                                </Link>
                                <span 
                                    v-else 
                                    class="px-3 py-1 min-w-[32px] text-center border rounded text-sm bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed" 
                                    v-html="getPaginationLabel(link.label, k, stocks.links.length)"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingAdjustmentModal" @close="closeAdjustmentModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Ajuste Manual de Stock</h2>
                <div v-if="stockToAdjust" class="mb-4 p-3 bg-gray-50 rounded border border-gray-200 text-sm text-gray-600">
                    Producto: <strong>{{ stockToAdjust.producto?.nombre }}</strong> <br>
                    Depósito: <strong>{{ stockToAdjust.deposito?.nombre }}</strong> <br>
                    Actual: <strong>{{ stockToAdjust.cantidad_disponible }}</strong> {{ stockToAdjust.producto?.unidadMedida }}
                </div>

                <form @submit.prevent="submitAdjustment" class="space-y-4">
                    <div>
                        <InputLabel for="tipoMovimiento" value="Tipo de Movimiento" />
                        <select id="tipoMovimiento" v-model="adjustmentForm.tipoMovimiento" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option v-for="type in movementTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                        <InputError :message="adjustmentForm.errors.tipoMovimiento" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="cantidad" value="Cantidad (Positiva)" />
                        <TextInput id="cantidad" type="number" v-model="adjustmentForm.cantidad" class="mt-1 block w-full" placeholder="Ej: 10" />
                        <InputError :message="adjustmentForm.errors.cantidad" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="motivo" value="Motivo (Requerido)" />
                        <textarea id="motivo" v-model="adjustmentForm.motivo" rows="2" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Ej: Ajuste inicial..."></textarea>
                        <InputError :message="adjustmentForm.errors.motivo" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeAdjustmentModal"> Cancelar </SecondaryButton>
                        <PrimaryButton class="ms-3" :class="{ 'opacity-25': adjustmentForm.processing }" :disabled="adjustmentForm.processing">
                            Guardar Movimiento
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showingConfigModal" @close="closeConfigModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Configurar Alertas de Stock</h2>
                <div v-if="stockToConfig" class="mb-4 text-sm text-gray-600">
                    Configurando: <strong>{{ stockToConfig.producto?.nombre }}</strong> <br>
                    En: <strong>{{ stockToConfig.deposito?.nombre }}</strong>
                </div>

                <form @submit.prevent="submitConfig" class="space-y-4">
                    <div>
                        <InputLabel for="stock_minimo" value="Stock Mínimo (Punto de Pedido)" />
                        <TextInput id="stock_minimo" type="number" v-model="configForm.stock_minimo" class="mt-1 block w-full" min="0" />
                        <p class="text-xs text-gray-500 mt-1">El sistema avisará cuando el stock baje de esta cantidad.</p>
                        <InputError :message="configForm.errors.stock_minimo" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton @click="closeConfigModal"> Cancelar </SecondaryButton>
                        <PrimaryButton class="ms-3" :class="{ 'opacity-25': configForm.processing }" :disabled="configForm.processing">
                            Guardar Configuración
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
