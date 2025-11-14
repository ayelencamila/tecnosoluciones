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
});

// --- COMPATIBILIDAD KENDALL (Formato para tu SelectInput) ---
// Transformamos los arrays de Laravel al formato { value, label }

// Opción genérica para "Todos"
const allOption = [{ value: '', label: 'Todos' }];

const tiposClienteOptions = computed(() => [
    { value: '', label: 'Todos los Tipos' },
    ...props.tiposCliente.map(t => ({
        value: t.tipoClienteID,
        label: t.nombreTipo
    }))
]);

const estadosClienteOptions = computed(() => [
    { value: '', label: 'Todos los Estados' },
    ...props.estadosCliente.map(e => ({
        value: e.estadoClienteID,
        label: e.nombreEstado
    }))
]);

const provinciasOptions = computed(() => [
    { value: '', label: 'Todas las Provincias' },
    ...props.provincias.map(p => ({
        value: p.provinciaID,
        label: p.nombre
    }))
]);
// --- FIN COMPATIBILIDAD ---


watch(form, debounce(() => {
    router.get(route('clientes.index'), form.value, {
        preserveState: true,
        replace: true,
    });
}, 300), { deep: true });

const resetFilters = () => {
    form.value = { search: '', tipo_cliente_id: '', estado_cliente_id: '', provincia_id: '' };
};

// ... (helpers getEstadoBadgeClass y formatCurrency) ...
const getEstadoBadgeClass = (estado) => {
    switch (estado?.toLowerCase()) {
        case 'activo': return 'bg-green-100 text-green-800';
        case 'inactivo': return 'bg-red-100 text-red-800';
        case 'moroso': return 'bg-orange-100 text-orange-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
const formatCurrency = (value) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
</script>

<template>
    <Head title="Listado de Clientes" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Clientes</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- ... (Tarjetas de Resumen no cambian) ... -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <!-- ... -->
                </div>


                <!-- FILTROS -->
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
                        
                        <!-- CORRECCIÓN: Ahora pasamos el prop 'options' -->
                        <SelectInput 
                            v-model="form.tipo_cliente_id" 
                            class="w-full" 
                            :options="tiposClienteOptions" 
                        />
                        
                        <SelectInput 
                            v-model="form.estado_cliente_id" 
                            class="w-full" 
                            :options="estadosClienteOptions"
                        />

                        <SelectInput 
                            v-model="form.provincia_id" 
                            class="w-full" 
                            :options="provinciasOptions"
                        />

                        <button @click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 underline text-right">
                            Limpiar Filtros
                        </button>
                    </div>
                </div>

                <!-- TABLA -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contacto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo / Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ubicación</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="cliente in clientes.data" :key="cliente.clienteID" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ cliente.nombre }} {{ cliente.apellido }}</div>
                                        <div class="text-sm text-gray-500">DNI: {{ cliente.DNI }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ cliente.whatsapp || cliente.telefono || '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ cliente.mail }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 mb-1">{{ cliente.tipo_cliente?.nombreTipo }}</div>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getEstadoBadgeClass(cliente.estado_cliente?.nombreEstado)">
                                            {{ cliente.estado_cliente?.nombreEstado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ cliente.direccion?.localidad?.nombre }}, {{ cliente.direccion?.localidad?.provincia?.nombre }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                        <Link :href="route('clientes.show', cliente.clienteID)" class="text-indigo-600 hover:text-indigo-900 font-bold">Ver</Link>
                                        <Link :href="route('clientes.edit', cliente.clienteID)" class="text-yellow-600 hover:text-yellow-900">Editar</Link>
                                    </td>
                                </tr>
                                <tr v-if="clientes.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No se encontraron clientes.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- PAGINACIÓN -->
                    <div class="px-6 py-4 border-t border-gray-200" v-if="clientes.links.length > 3">
                        <div class="flex justify-center space-x-1">
                            <template v-for="(link, k) in clientes.links" :key="k">
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