<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    categorias: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const tipo = ref(props.filters.tipo || '');

const searchCategorias = debounce(() => {
    router.get(route('admin.categorias-gasto.index'), {
        search: search.value,
        tipo: tipo.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, tipo], searchCategorias);

const toggleActivo = (categoria) => {
    router.patch(route('admin.categorias-gasto.toggle-activo', categoria.categoria_gasto_id));
};

const confirmDelete = (categoria) => {
    if (confirm(`¿Está seguro de eliminar la categoría "${categoria.nombre}"?`)) {
        router.delete(route('admin.categorias-gasto.destroy', categoria.categoria_gasto_id));
    }
};

const getTipoBadge = (tipo) => {
    return tipo === 'gasto' 
        ? 'bg-blue-100 text-blue-800' 
        : 'bg-red-100 text-red-800';
};

const getTipoLabel = (tipo) => {
    return tipo === 'gasto' ? 'Gasto' : 'Pérdida';
};
</script>

<template>
    <AppLayout>
        <Head title="Categorías de Gasto" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Categorías de Gasto
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Administrar categorías para clasificar gastos y pérdidas
                    </p>
                </div>
                <Link
                    :href="route('admin.categorias-gasto.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Categoría
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Filtros -->
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por nombre o descripción..."
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                        <div>
                            <select
                                v-model="tipo"
                                class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="">Todos los tipos</option>
                                <option value="gasto">Gastos</option>
                                <option value="perdida">Pérdidas</option>
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
                                    Nombre
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Descripción
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tipo
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="categoria in categorias.data" :key="categoria.categoria_gasto_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">{{ categoria.nombre }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-500">{{ categoria.descripcion || '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span 
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="getTipoBadge(categoria.tipo)"
                                    >
                                        {{ getTipoLabel(categoria.tipo) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button
                                        @click="toggleActivo(categoria)"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors"
                                        :class="categoria.activo ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                    >
                                        {{ categoria.activo ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link
                                            :href="route('admin.categorias-gasto.edit', categoria.categoria_gasto_id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            title="Editar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="confirmDelete(categoria)"
                                            class="text-red-600 hover:text-red-900"
                                            title="Eliminar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="categorias.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    No se encontraron categorías
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <div v-if="categorias.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">
                                Mostrando {{ categorias.from }} a {{ categorias.to }} de {{ categorias.total }} categorías
                            </span>
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in categorias.links"
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
