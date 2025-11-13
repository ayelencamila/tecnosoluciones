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
    stats: Object, // Estadísticas
});

// Filtros Reactivos
const form = ref({
    search: props.filters.search || '',
    categoria_id: props.filters.categoria_id || '',
    estado_id: props.filters.estado_id || '',
    stock_bajo: props.filters.stock_bajo || false,
});

// --- COMPATIBILIDAD KENDALL (Formato para tu SelectInput) ---
// Transformamos los arrays de Laravel al formato { value, label }
const categoriasOptions = computed(() => [
    { value: '', label: 'Todas las Categorías' },
    ...props.categorias.map(c => ({ value: c.id, label: c.nombre }))
]);

const estadosOptions = computed(() => [
    { value: '', label: 'Todos los Estados' },
    ...props.estados.map(e => ({ value: e.id, label: e.nombre }))
]);
// --- FIN COMPATIBILIDAD ---

watch(form, debounce(() => {
    router.get(route('productos.index'), form.value, {
        preserveState: true,
        replace: true,
    });
}, 300), { deep: true });

const resetFilters = () => {
    form.value = { search: '', categoria_id: '', estado_id: '', stock_bajo: false };
};

// Formato de Badges (Kendall: Feedback Visual)
const getEstadoBadgeClass = (estadoNombre) => {
    switch (estadoNombre?.toLowerCase()) {
        case 'activo': return 'bg-green-100 text-green-800';
        case 'inactivo': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
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

                <!-- Estadísticas (Dashboard) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white p-4 shadow-sm sm:rounded-lg border-l-4 border-indigo-500">
                        <div class="text-gray-500 text-xs uppercase font-bold">Total Items</div>
                        <div class="text-2xl font-bold text-gray-800">{{ stats.total }}</div>
                    </div>
                    <div class="bg-white p-4 shadow-sm sm:rounded-lg border-l-4 border-green-500">
                        <div class="text-gray-500 text-xs uppercase font-bold">Activos</div>
                        <div class="text-2xl font-bold text-gray-800">{{ stats.activos }}</div>
                    </div>
                    <div class="bg-white p-4 shadow-sm sm:rounded-lg border-l-4 border-yellow-500">
                        <div class="text-gray-500 text-xs uppercase font-bold">Stock Bajo</div>
                        <div class="text-2xl font-bold text-gray-800">{{ stats.stockBajo }}</div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                        <div class="flex-1 w-full">
                            <TextInput v-model="form.search" placeholder="Buscar por Código, Nombre o Marca..." class="w-full" />
                        </div>
                        <Link :href="route('productos.create')">
                            <PrimaryButton>+ Nuevo Producto</PrimaryButton>
                        </Link>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        
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

                        <label class="flex items-center text-sm text-gray-700">
                            <input type="checkbox" v-model="form.stock_bajo" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                            <span class="ml-2">Mostrar solo Stock Bajo</span>
                        </label>
                        <button @click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 underline text-right">
                            Limpiar Filtros
                        </button>
                    </div>
                </div>

                <!-- Tabla de Productos -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="producto in productos.data" :key="producto.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ producto.nombre }}</div>
                                        <div class="text-sm text-gray-500">{{ producto.codigo }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ producto.categoria?.nombre }}</td>
                                    <td class="px-6 py-4 text-center text-sm font-bold"
                                        :class="{'text-red-600': producto.stockActual < producto.stockMinimo, 'text-green-600': producto.stockActual >= producto.stockMinimo}">
                                        {{ producto.stockActual }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                              :class="getEstadoBadgeClass(producto.estado?.nombre)">
                                            {{ producto.estado?.nombre }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                        <Link :href="route('productos.show', producto.id)" class="text-indigo-600 hover:text-indigo-900 font-bold">Ver</Link>
                                        <Link :href="route('productos.edit', producto.id)" class="text-yellow-600 hover:text-yellow-900">Editar</Link>
                                    </td>
                                </tr>
                                <tr v-if="productos.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No se encontraron productos.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    <div class="px-6 py-4 border-t border-gray-200" v-if="productos.links.length > 3">
                        <div class="flex justify-center space-x-1">
                            <template v-for="(link, k) in productos.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    v-html="link.label"
                                    class="px-3 py-1 border rounded text-sm"
                                    :class="link.active ? 'bg-indigo-500 text-white' : 'bg-white text-gray-700'"
                                />
                                <span v-else class="px-3 py-1 border rounded text-sm bg-gray-100 text-gray-400" v-html="link.label"></span>
                            </template>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AppLayout>
</template>
