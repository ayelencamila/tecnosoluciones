<script setup>
import { ref, watch, computed } from 'vue'; 
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { debounce } from 'lodash';

const props = defineProps({
    productos: Object, // Paginador
    categorias: Array,
    estados: Array,
    filters: Object,
    stats: Object,
});

const form = ref({
    search: props.filters.search || '',
    categoria_id: props.filters.categoria_id || '',
    estado_id: props.filters.estado_id || '',
    stock_bajo: props.filters.stock_bajo === 'true',
});

// Opciones para tus componentes SelectInput
const categoriasOptions = computed(() => [
    { value: '', label: 'Todas las Categorías' },
    ...props.categorias.map(c => ({ value: c.id, label: c.nombre }))
]);

const estadosOptions = computed(() => [
    { value: '', label: 'Todos los Estados' },
    ...props.estados.map(e => ({ value: e.id, label: e.nombre }))
]);

watch(form, debounce(() => {
    router.get(route('productos.index'), form.value, {
        preserveState: true,
        replace: true,
    });
}, 300), { deep: true });

const resetFilters = () => {
    form.value = { search: '', categoria_id: '', estado_id: '', stock_bajo: false };
};

// --- LÓGICA DE PRESENTACIÓN ---
const getEstadoBadgeClass = (estadoNombre) => {
    switch (estadoNombre?.toLowerCase()) {
        case 'activo': return 'bg-green-100 text-green-800 border border-green-200';
        case 'inactivo': return 'bg-red-100 text-red-800 border border-red-200';
        default: return 'bg-gray-100 text-gray-800 border border-gray-200';
    }
};

// Nuevo: Calcular stock sumando los depósitos (ya que 'stockActual' no existe en BD)
const calcularStockTotal = (stocks) => {
    if (!stocks || stocks.length === 0) return 0;
    return stocks.reduce((acc, stock) => acc + stock.cantidad_disponible, 0);
};

const esServicio = (producto) => {
    return producto.categoria?.nombre === 'Servicios Técnicos';
};

const tieneStockBajo = (producto) => {
    if (esServicio(producto)) return false;
    const total = calcularStockTotal(producto.stocks);
    // Usamos el mínimo del primer depósito como referencia
    const minimo = producto.stocks?.[0]?.stock_minimo || 0;
    return total <= minimo;
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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white p-4 shadow-sm sm:rounded-lg border-l-4 border-indigo-500 flex justify-between items-center">
                        <div>
                            <div class="text-gray-500 text-xs uppercase font-bold tracking-wider">Total Items</div>
                            <div class="text-2xl font-bold text-gray-800">{{ stats.total }}</div>
                        </div>
                        <div class="text-indigo-200">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                    </div>
                    <div class="bg-white p-4 shadow-sm sm:rounded-lg border-l-4 border-green-500 flex justify-between items-center">
                        <div>
                            <div class="text-gray-500 text-xs uppercase font-bold tracking-wider">Activos</div>
                            <div class="text-2xl font-bold text-gray-800">{{ stats.activos }}</div>
                        </div>
                        <div class="text-green-200">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <div class="bg-white p-4 shadow-sm sm:rounded-lg border-l-4 border-yellow-500 flex justify-between items-center">
                        <div>
                            <div class="text-gray-500 text-xs uppercase font-bold tracking-wider">Stock Bajo</div>
                            <div class="text-2xl font-bold text-gray-800">{{ stats.stockBajo }}</div>
                        </div>
                        <div class="text-yellow-200">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                        <div class="flex-1 w-full relative">
                             <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <TextInput 
                                v-model="form.search" 
                                placeholder="Buscar por Código, Nombre o Marca..." 
                                class="w-full pl-10" 
                            />
                        </div>
                        <Link :href="route('productos.create')">
                            <PrimaryButton class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Nuevo Producto
                            </PrimaryButton>
                        </Link>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <SelectInput 
                            v-model="form.categoria_id" 
                            class="w-full" 
                            :options="categoriasOptions" 
                        />
                        
                        <SelectInput 
                            v-model="form.estado_id" 
                            class="w-full" 
                            :options="estadosOptions"
                        />

                        <label class="flex items-center p-2 border border-gray-200 rounded-md bg-gray-50 cursor-pointer hover:bg-gray-100 transition h-[42px]">
                            <input type="checkbox" v-model="form.stock_bajo" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span class="ml-2 text-sm text-gray-700 font-medium select-none">Solo Stock Bajo</span>
                        </label>

                        <button @click="resetFilters" class="text-sm text-indigo-600 hover:text-indigo-800 underline text-right md:text-center transition">
                            Limpiar filtros
                        </button>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Categoría</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Stock Total</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="producto in productos.data" :key="producto.id" class="hover:bg-indigo-50 transition duration-150 ease-in-out">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-gray-900">{{ producto.nombre }}</span>
                                            <span class="text-xs text-gray-500 font-mono">{{ producto.codigo }}</span>
                                            <span v-if="producto.marca" class="text-xs text-gray-400">{{ producto.marca }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ producto.categoria?.nombre }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div v-if="esServicio(producto)">
                                            <span class="text-xs text-gray-400 italic bg-gray-100 px-2 py-1 rounded">Servicio</span>
                                        </div>
                                        <div v-else>
                                            <span class="text-sm font-bold" 
                                                  :class="tieneStockBajo(producto) ? 'text-red-600' : 'text-green-600'">
                                                {{ calcularStockTotal(producto.stocks) }}
                                            </span>
                                            <span v-if="tieneStockBajo(producto)" class="ml-1 inline-block w-2 h-2 bg-red-500 rounded-full animate-pulse" title="Stock Bajo / Quiebre"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                              :class="getEstadoBadgeClass(producto.estado?.nombre)">
                                            {{ producto.estado?.nombre }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-3">
                                            <Link :href="route('productos.show', producto.id)" class="text-gray-400 hover:text-indigo-600 transition p-1 rounded hover:bg-indigo-50" title="Ver Detalles">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </Link>
                                            <Link :href="route('productos.edit', producto.id)" class="text-gray-400 hover:text-yellow-600 transition p-1 rounded hover:bg-yellow-50" title="Editar Producto">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="productos.data.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 bg-gray-50">
                                        <p class="text-lg font-medium">No se encontraron productos</p>
                                        <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="productos.links.length > 3">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700 hidden sm:block">
                                Mostrando <span class="font-medium">{{ productos.from }}</span> a <span class="font-medium">{{ productos.to }}</span> de <span class="font-medium">{{ productos.total }}</span> resultados
                            </div>
                            <div class="flex justify-center space-x-1">
                                <template v-for="(link, k) in productos.links" :key="k">
                                    <div v-if="link.url === null" class="px-3 py-1 border border-gray-300 rounded bg-gray-100 text-gray-400 text-sm" v-html="link.label"></div>
                                    <Link v-else 
                                        :href="link.url" 
                                        class="px-3 py-1 border rounded text-sm transition"
                                        :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AppLayout>
</template>