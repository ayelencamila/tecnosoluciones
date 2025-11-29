<script setup>
import { ref, watch, computed } from 'vue'; 
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { debounce } from 'lodash';

const props = defineProps({
    productos: Object,
    categorias: Array,
    estados: Array,
    proveedores: Array,
    filters: Object,
    stats: Object,
});

const form = ref({
    search: props.filters.search || '',
    categoria_id: props.filters.categoria_id || '',
    estado_id: props.filters.estado_id || '',
    proveedor_id: props.filters.proveedor_id || '',
    stock_bajo: props.filters.stock_bajo === 'true',
    sort_column: props.filters.sort_column || 'nombre',
    sort_direction: props.filters.sort_direction || 'asc',
});

// --- LÓGICA DE BAJA ---
const confirmingDeletion = ref(false);
const productToDelete = ref(null);
const deleteForm = useForm({ motivo: '' });

const confirmDelete = (producto) => {
    productToDelete.value = producto;
    confirmingDeletion.value = true;
};

const closeModal = () => {
    confirmingDeletion.value = false;
    productToDelete.value = null;
    deleteForm.reset();
    deleteForm.clearErrors();
};

const deleteProducto = () => {
    deleteForm.post(route('productos.darDeBaja', productToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

// --- SELECTS ---
const categoriasOptions = computed(() => [{ value: '', label: 'Todas las Categorías' }, ...props.categorias.map(c => ({ value: c.id, label: c.nombre }))]);
const estadosOptions = computed(() => [{ value: '', label: 'Todos los Estados' }, ...props.estados.map(e => ({ value: e.id, label: e.nombre }))]);
const proveedoresOptions = computed(() => [{ value: '', label: 'Todos los Proveedores' }, ...props.proveedores.map(p => ({ value: p.id, label: p.razon_social }))]);

// --- WATCHERS & FILTROS ---
watch(form, debounce(() => {
    router.get(route('productos.index'), form.value, { preserveState: true, replace: true });
}, 300), { deep: true });

const sortBy = (column) => {
    form.value.sort_column = column;
    form.value.sort_direction = form.value.sort_direction === 'asc' ? 'desc' : 'asc';
};

const resetFilters = () => {
    form.value = { search: '', categoria_id: '', estado_id: '', proveedor_id: '', stock_bajo: false, sort_column: 'nombre', sort_direction: 'asc' };
};

// --- HELPERS ---
const getEstadoBadgeClass = (estado) => {
    const nombre = estado?.toLowerCase() || '';
    if (nombre === 'activo') return 'bg-green-100 text-green-800';
    if (nombre === 'inactivo') return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-800';
};

const getPrecioMinorista = (precios) => {
    // Asumimos ID 2 = Minorista (ajustar si cambia)
    const precio = precios.find(p => p.tipoClienteID === 2); 
    return precio ? new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(precio.precio) : '-';
};

const calcularStockTotal = (stocks) => {
    if (!stocks || stocks.length === 0) return 0;
    return stocks.reduce((acc, stock) => acc + stock.cantidad_disponible, 0);
};

const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Catálogo de Productos" />
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Catálogo de Productos</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-center">
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-indigo-500">
                        <p class="text-sm font-medium text-gray-500">Total Items</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-green-500">
                        <p class="text-sm font-medium text-gray-500">Activos</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.activos }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-yellow-500">
                        <p class="text-sm font-medium text-gray-500">Stock Bajo</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.stockBajo }}</p>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                        <div class="flex-1 w-full">
                            <TextInput v-model="form.search" placeholder="Buscar por Código, Nombre o Marca..." class="w-full" />
                        </div>
                        <Link :href="route('productos.create')">
                            <PrimaryButton>+ Nuevo Producto</PrimaryButton>
                        </Link>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <SelectInput v-model="form.categoria_id" class="w-full" :options="categoriasOptions" />
                        <SelectInput v-model="form.estado_id" class="w-full" :options="estadosOptions" />
                        <SelectInput v-model="form.proveedor_id" class="w-full" :options="proveedoresOptions" />

                        <label class="flex items-center p-2 border border-gray-200 rounded-md bg-white cursor-pointer hover:bg-gray-50 transition h-[42px]">
                            <input type="checkbox" v-model="form.stock_bajo" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span class="ml-2 text-sm text-gray-700 font-medium select-none">Stock Bajo</span>
                        </label>

                        <div class="flex justify-end items-center h-[42px]">
                            <button @click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 underline">Limpiar Filtros</button>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th @click="sortBy('nombre')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
                                        Producto <span v-if="form.sort_column === 'nombre'">{{ form.sort_direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio (Min)</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Stock</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="producto in productos.data" :key="producto.id" class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900">{{ producto.nombre }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ producto.codigo }}</div>
                                        <div v-if="producto.marca" class="text-xs text-gray-400 font-semibold">{{ producto.marca.nombre }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ producto.categoria?.nombre }}
                                        <div v-if="producto.proveedor_habitual" class="text-xs text-indigo-500 mt-1" title="Proveedor Habitual">
                                            Prov: {{ producto.proveedor_habitual.razon_social }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                        {{ getPrecioMinorista(producto.precios) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm font-bold px-2 py-1 rounded" :class="calcularStockTotal(producto.stocks) < (producto.stocks[0]?.stock_minimo || 0) ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'">
                                            {{ calcularStockTotal(producto.stocks) }}
                                        </span>
                                        <span class="text-xs text-gray-400 ml-1">{{ producto.unidad_medida?.abreviatura }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getEstadoBadgeClass(producto.estado?.nombre)">
                                            {{ producto.estado?.nombre }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-3 items-center">
                                            <Link :href="route('productos.show', producto.id)" class="text-indigo-600 hover:text-indigo-900 font-bold" title="Ver Detalle">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </Link>
                                            <Link :href="route('productos.edit', producto.id)" class="text-amber-500 hover:text-amber-700" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                            </Link>
                                            <button v-if="producto.estado?.nombre === 'Activo'" @click="confirmDelete(producto)" class="text-red-600 hover:text-red-900 transition" title="Dar de Baja">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="productos.data.length === 0"><td colspan="6" class="px-6 py-12 text-center text-gray-400">No se encontraron productos</td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="productos.links">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in productos.links" :key="k">
                                <Link v-if="link.url" :href="link.url" class="px-3 py-1 min-w-[32px] text-center border rounded text-sm font-medium transition-all duration-150" :class="link.active ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500 border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'">
                                    <span v-html="getPaginationLabel(link.label, k, productos.links.length)"></span>
                                </Link>
                                <span v-else class="px-3 py-1 min-w-[32px] text-center border rounded text-sm bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed" v-html="getPaginationLabel(link.label, k, productos.links.length)"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="confirmingDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">¿Dar de baja el producto "{{ productToDelete?.nombre }}"?</h2>
                <p class="mt-1 text-sm text-gray-600">Esta acción cambiará su estado a "Inactivo". Por favor, ingrese el motivo.</p>
                <div class="mt-6">
                    <InputLabel for="motivo" value="Motivo (Requerido)" />
                    <textarea id="motivo" v-model="deleteForm.motivo" rows="3" class="w-full mt-1 border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm" placeholder="Ej: Producto descontinuado..."></textarea>
                    <InputError :message="deleteForm.errors.motivo" class="mt-2" />
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                    <DangerButton class="ms-3" :class="{ 'opacity-25': deleteForm.processing }" :disabled="deleteForm.processing" @click="deleteProducto">Confirmar Baja</DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>