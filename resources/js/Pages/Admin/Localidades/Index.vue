<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

// Recibimos las localidades Y las provincias (para el select)
const props = defineProps({
    localidades: Object,
    provincias: Array // <--- Lista para el dropdown
});

const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');

const form = useForm({
    localidadID: null,
    nombre: '',
    provinciaID: '', // <--- Aquí se guardará la selección
});

const openCreateModal = () => {
    isEditing.value = false;
    modalTitle.value = 'Nueva Localidad';
    form.reset();
    // Si hay provincias, seleccionamos la primera por defecto para mejor UX
    if (props.provincias.length > 0) {
        form.provinciaID = props.provincias[0].provinciaID;
    }
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (localidad) => {
    isEditing.value = true;
    modalTitle.value = 'Editar Localidad';
    form.localidadID = localidad.localidadID;
    form.nombre = localidad.nombre;
    form.provinciaID = localidad.provinciaID; // Cargamos la provincia actual
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.localidades.update', form.localidadID), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.localidades.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const deleteLocalidad = (localidad) => {
    if (confirm(`¿Eliminar "${localidad.nombre}"?`)) {
        router.delete(route('admin.localidades.destroy', localidad.localidadID));
    }
};

const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Localidades" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Localidades</h2>
                <PrimaryButton @click="openCreateModal">
                    + Nueva Localidad
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <table class="min-w-full divide-y divide-gray-200 mb-4">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provincia</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="loc in localidades.data" :key="loc.localidadID">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{{ loc.localidadID }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ loc.nombre }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-50 text-blue-700">
                                            {{ loc.provincia?.nombre || 'Sin Provincia' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(loc)" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
                                        <button @click="deleteLocalidad(loc)" class="text-red-600 hover:text-red-900">Borrar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="localidades.links">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in localidades.links" :key="k">
                                <Link v-if="link.url" :href="link.url" class="px-3 py-1 min-w-[32px] text-center border rounded text-sm font-medium transition-all duration-150" :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-indigo-50'">
                                    <span v-html="getPaginationLabel(link.label, k, localidades.links.length)"></span>
                                </Link>
                                <span v-else class="px-3 py-1 min-w-[32px] text-center border rounded text-sm bg-gray-100 text-gray-400" v-html="getPaginationLabel(link.label, k, localidades.links.length)"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ modalTitle }}</h2>
                
                <div class="mb-4">
                    <InputLabel for="provinciaID" value="Provincia" />
                    <select 
                        id="provinciaID" 
                        v-model="form.provinciaID"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="" disabled>Seleccione una provincia</option>
                        <option v-for="prov in provincias" :key="prov.provinciaID" :value="prov.provinciaID">
                            {{ prov.nombre }}
                        </option>
                    </select>
                    <InputError :message="form.errors.provinciaID" class="mt-2" />
                </div>

                <div class="mb-4">
                    <InputLabel for="nombre" value="Nombre de la Localidad" />
                    <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" placeholder="Ej: Apóstoles" />
                    <InputError :message="form.errors.nombre" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                    <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="submit">Guardar</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>