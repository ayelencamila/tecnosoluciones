<script setup>
import { ref, watch, computed } from 'vue'; 
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { debounce } from 'lodash';

const props = defineProps({
    clientes: Object, 
    estadosCliente: Array,
    tiposCliente: Array,
    provincias: Array,
    filters: Object,
    counts: Object, 
});

const form = ref({
    search: props.filters.search || '',
    tipo_cliente_id: props.filters.tipo_cliente_id || '',
    estado_cliente_id: props.filters.estado_cliente_id || '',
    provincia_id: props.filters.provincia_id || '',
    sort_column: props.filters.sort_column || 'apellido',
    sort_direction: props.filters.sort_direction || 'asc',
});

const tiposClienteOptions = computed(() => [
    { value: '', label: 'Todos los Tipos' },
    ...props.tiposCliente.map(t => ({ value: t.tipoClienteID, label: t.nombreTipo }))
]);

const estadosClienteOptions = computed(() => [
    { value: '', label: 'Todos los Estados' },
    ...props.estadosCliente.map(e => ({ value: e.estadoClienteID, label: e.nombreEstado }))
]);

const provinciasOptions = computed(() => [
    { value: '', label: 'Todas las Provincias' },
    ...props.provincias.map(p => ({ value: p.provinciaID, label: p.nombre }))
]);

watch(form, debounce(() => {
    router.get(route('clientes.index'), form.value, { preserveState: true, replace: true });
}, 300), { deep: true });

const sortBy = (column) => {
    form.value.sort_column = column;
    form.value.sort_direction = form.value.sort_direction === 'asc' ? 'desc' : 'asc';
};

const resetFilters = () => {
    form.value = { search: '', tipo_cliente_id: '', estado_cliente_id: '', provincia_id: '', sort_column: 'apellido', sort_direction: 'asc' };
};

const getEstadoBadgeClass = (estado) => {
    switch (estado?.toLowerCase()) {
        case 'activo': return 'bg-green-100 text-green-800';
        case 'inactivo': return 'bg-red-100 text-red-800';
        case 'moroso': return 'bg-orange-100 text-orange-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// --- SOLUCIÓN INFALIBLE PAGINACIÓN ---
// No miramos el texto, miramos la posición.
const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;'; // El primero siempre es "Anterior" -> Flecha Izq
    if (index === totalLinks - 1) return '&raquo;'; // El último siempre es "Siguiente" -> Flecha Der
    return label; // Los del medio son números, los dejamos igual
};
</script>

<template>
    <Head title="Gestión de Clientes" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Clientes</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-center">
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-indigo-500">
                        <p class="text-sm font-medium text-gray-500">Total Clientes</p>
                        <p class="text-2xl font-bold text-gray-900">{{ props.counts?.total || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-green-500">
                        <p class="text-sm font-medium text-gray-500">Activos</p>
                        <p class="text-2xl font-bold text-gray-900">{{ props.counts?.activos || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-red-500">
                        <p class="text-sm font-medium text-gray-500">Inactivos</p>
                        <p class="text-2xl font-bold text-gray-900">{{ props.counts?.inactivos || 0 }}</p>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                        <div class="flex-1 w-full">
                            <TextInput v-model="form.search" placeholder="Buscar por Nombre, DNI o Email..." class="w-full" />
                        </div>
                        <Link :href="route('clientes.create')">
                            <PrimaryButton>+ Nuevo Cliente</PrimaryButton>
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <SelectInput v-model="form.tipo_cliente_id" class="w-full" :options="tiposClienteOptions" />
                        <SelectInput v-model="form.estado_cliente_id" class="w-full" :options="estadosClienteOptions" />
                        <SelectInput v-model="form.provincia_id" class="w-full" :options="provinciasOptions" />
                        
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
                                    <th @click="sortBy('apellido')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
                                        Cliente <span v-if="form.sort_column === 'apellido'">{{ form.sort_direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th @click="sortBy('DNI')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
                                        Contacto / DNI
                                    </th>
                                    <th @click="sortBy('tipoClienteID')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
                                        Tipo / Estado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        Ubicación
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="cliente in clientes.data" :key="cliente.clienteID" class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ cliente.apellido }}, {{ cliente.nombre }}</div>
                                        <div class="text-xs text-gray-500">{{ cliente.mail }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 font-mono">{{ cliente.DNI }}</div>
                                        <div class="text-xs text-gray-500">{{ cliente.whatsapp || cliente.telefono || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs text-gray-500 mb-1 uppercase font-semibold">{{ cliente.tipo_cliente?.nombreTipo }}</div>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getEstadoBadgeClass(cliente.estado_cliente?.nombreEstado)">
                                            {{ cliente.estado_cliente?.nombreEstado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ cliente.direccion?.localidad?.nombre || '-' }}, {{ cliente.direccion?.localidad?.provincia?.nombre || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-3 items-center">
                                            <Link :href="route('clientes.show', cliente.clienteID)" class="text-indigo-600 hover:text-indigo-900 font-bold" title="Ver">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </Link>
                                            <Link :href="route('clientes.edit', cliente.clienteID)" class="text-yellow-600 hover:text-yellow-900" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                            </Link>
                                            <Link v-if="cliente.estado_cliente?.nombreEstado === 'Activo'" :href="route('clientes.confirmDelete', cliente.clienteID)" class="text-red-600 hover:text-red-900" title="Dar de Baja">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="clientes.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <span class="text-lg font-medium">No se encontraron resultados</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200" v-if="clientes.links.length > 3">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in clientes.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-medium transition-all duration-150"
                                    :class="link.active 
                                        ? 'bg-indigo-600 text-white shadow-md ring-2 ring-indigo-300' 
                                        : 'bg-white text-gray-600 border border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'"
                                >
                                    <span v-html="getPaginationLabel(link.label, k, clientes.links.length)"></span>
                                </Link>
                                <span 
                                    v-else 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm text-gray-300 border border-gray-200 cursor-not-allowed" 
                                    v-html="getPaginationLabel(link.label, k, clientes.links.length)"
                                ></span>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>