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
    proveedores: Object,
    provincias: Array,
    filters: Object,
    stats: Object,
});

// --- FILTROS ---
const form = ref({
    search: props.filters.search || '',
    provincia_id: props.filters.provincia_id || '',
    estado: props.filters.estado || '',
    sort_column: props.filters.sort_column || 'razon_social',
    sort_direction: props.filters.sort_direction || 'asc',
});

// --- COMPUTED OPTIONS ---
const provinciasOptions = computed(() => [
    { value: '', label: 'Todas las Provincias' },
    ...(props.provincias || []).map(p => ({ value: p.id, label: p.nombre }))
]);

const estadosOptions = computed(() => [
    { value: '', label: 'Todos los Estados' },
    { value: 'activo', label: 'Activos' },
    { value: 'inactivo', label: 'Inactivos' }
]);

// --- WATCHERS & FILTROS ---
watch(form, debounce(() => {
    router.get(route('proveedores.index'), form.value, { preserveState: true, replace: true });
}, 300), { deep: true });

const sortBy = (column) => {
    form.value.sort_column = column;
    form.value.sort_direction = form.value.sort_direction === 'asc' ? 'desc' : 'asc';
};

const resetFilters = () => {
    form.value = { 
        search: '', 
        provincia_id: '', 
        estado: '', 
        sort_column: 'razon_social', 
        sort_direction: 'asc' 
    };
};

// --- LÓGICA DE BAJA (CU-19) ---
const confirmingDeletion = ref(false);
const proveedorToDelete = ref(null);
const deleteForm = useForm({ motivo: '' });

const confirmDelete = (proveedor) => {
    proveedorToDelete.value = proveedor;
    confirmingDeletion.value = true;
};

const closeModal = () => {
    confirmingDeletion.value = false;
    proveedorToDelete.value = null;
    deleteForm.reset();
    deleteForm.clearErrors();
};

const deleteProveedor = () => {
    deleteForm.delete(route('proveedores.destroy', proveedorToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

// --- HELPERS VISUALES ---
const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};

const getCalificacionStars = (calificacion) => {
    if (!calificacion) return '-';
    return '⭐'.repeat(Math.round(calificacion)) + ` (${calificacion})`;
};
</script>

<template>
    <Head title="Gestión de Proveedores" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Proveedores</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- TARJETAS DE ESTADÍSTICAS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-center" v-if="stats">
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-indigo-500">
                        <p class="text-sm font-medium text-gray-500">Total Proveedores</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.total || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-green-500">
                        <p class="text-sm font-medium text-gray-500">Activos</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.activos || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-amber-500">
                        <p class="text-sm font-medium text-gray-500">Mejor Calificación</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.mejorCalificacion ? '⭐ ' + stats.mejorCalificacion : '-' }}</p>
                    </div>
                </div>
                
                <!-- BARRA DE BÚSQUEDA Y FILTROS -->
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                        <div class="flex-1 w-full">
                            <TextInput v-model="form.search" placeholder="Buscar por Razón Social, CUIT o Email..." class="w-full" />
                        </div>
                        <Link :href="route('proveedores.create')">
                            <PrimaryButton>+ Registrar Proveedor</PrimaryButton>
                        </Link>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <SelectInput v-model="form.provincia_id" class="w-full" :options="provinciasOptions" />
                        <SelectInput v-model="form.estado" class="w-full" :options="estadosOptions" />

                        <div class="md:col-span-2 flex justify-end items-center h-[42px]">
                            <button @click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 underline">
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TABLA DE PROVEEDORES -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th @click="sortBy('razon_social')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
                                        Proveedor / CUIT <span v-if="form.sort_column === 'razon_social'">{{ form.sort_direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contacto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ubicación</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Condiciones</th>
                                    <th @click="sortBy('calificacion')" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
                                        Calificación <span v-if="form.sort_column === 'calificacion'">{{ form.sort_direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="prov in proveedores.data" :key="prov.id" class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900">{{ prov.razon_social }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ prov.cuit }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="text-gray-900 flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                            </svg>
                                            {{ prov.email }}
                                        </div>
                                        <div class="text-gray-500 text-xs flex items-center gap-1 mt-1" v-if="prov.telefono">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                            </svg>
                                            {{ prov.telefono }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div v-if="prov.direccion" class="flex items-start gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                            </svg>
                                            <div>
                                                <div>{{ prov.direccion.localidad?.nombre }}</div>
                                                <div class="text-xs">{{ prov.direccion.localidad?.provincia?.nombre }}</div>
                                            </div>
                                        </div>
                                        <div v-else class="italic text-xs">Sin dirección</div>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm">
                                        <span class="block text-xs font-semibold bg-blue-50 text-blue-700 px-2 py-1 rounded inline-block mb-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 inline mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                            </svg>
                                            {{ prov.plazo_entrega_estimado ? prov.plazo_entrega_estimado + ' días' : '-' }}
                                        </span>
                                        <div class="text-xs text-gray-500 truncate max-w-[120px]" :title="prov.forma_pago_preferida">
                                            {{ prov.forma_pago_preferida || '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm">
                                        <span class="text-amber-500 font-semibold">
                                            {{ getCalificacionStars(prov.calificacion) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            :class="prov.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                            {{ prov.activo ? 'ACTIVO' : 'INACTIVO' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-3 items-center">
                                            <Link :href="route('proveedores.show', prov.id)" class="text-indigo-600 hover:text-indigo-900 font-bold" title="Ver Detalle">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </Link>
                                            <Link :href="route('proveedores.edit', prov.id)" class="text-amber-500 hover:text-amber-700" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                            </Link>
                                            <button v-if="prov.activo" @click="confirmDelete(prov)" class="text-red-600 hover:text-red-900 transition" title="Dar de Baja">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="proveedores.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">No se encontraron proveedores.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINACIÓN -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="proveedores.links">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in proveedores.links" :key="k">
                                <Link v-if="link.url" :href="link.url" class="px-3 py-1 min-w-[32px] text-center border rounded text-sm font-medium transition-all duration-150"
                                    :class="link.active ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500 border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'">
                                    <span v-html="getPaginationLabel(link.label, k, proveedores.links.length)"></span>
                                </Link>
                                <span v-else class="px-3 py-1 min-w-[32px] text-center border rounded text-sm bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed"
                                    v-html="getPaginationLabel(link.label, k, proveedores.links.length)"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL DE CONFIRMACIÓN DE BAJA -->
        <Modal :show="confirmingDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    ¿Dar de baja al proveedor "{{ proveedorToDelete?.razon_social }}"?
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    El proveedor pasará a estado Inactivo y no podrá utilizarse en nuevas Compras.
                </p>
                <div class="mt-6">
                    <InputLabel for="motivo" value="Motivo de la baja (Requerido por Auditoría)" />
                    <textarea id="motivo" v-model="deleteForm.motivo" rows="3" 
                        class="w-full mt-1 border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                        placeholder="Ej: Cierre comercial, mala calidad de servicio..."></textarea>
                    <InputError :message="deleteForm.errors.motivo" class="mt-2" />
                    <InputError :message="deleteForm.errors.error" class="mt-2" /> 
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                    <DangerButton class="ms-3" :class="{ 'opacity-25': deleteForm.processing }" :disabled="deleteForm.processing" @click="deleteProveedor">
                        Confirmar Baja
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>