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

const props = defineProps({
    provincias: Object
});

const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');

// Formulario
const form = useForm({
    provinciaID: null, // Tu PK personalizada
    nombre: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    modalTitle.value = 'Nueva Provincia';
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (provincia) => {
    isEditing.value = true;
    modalTitle.value = 'Editar Provincia';
    form.provinciaID = provincia.provinciaID;
    form.nombre = provincia.nombre;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        // Usamos la ruta update con la PK correcta
        form.put(route('admin.provincias.update', form.provinciaID), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.provincias.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const deleteProvincia = (provincia) => {
    if (confirm(`¿Eliminar la provincia "${provincia.nombre}"?`)) {
        router.delete(route('admin.provincias.destroy', provincia.provinciaID));
    }
};

const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Provincias" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Provincias</h2>
                <PrimaryButton @click="openCreateModal">
                    + Nueva Provincia
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
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="prov in provincias.data" :key="prov.provinciaID">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{{ prov.provinciaID }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ prov.nombre }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(prov)" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
                                        <button @click="deleteProvincia(prov)" class="text-red-600 hover:text-red-900">Borrar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="provincias.links">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in provincias.links" :key="k">
                                <Link v-if="link.url" :href="link.url" class="px-3 py-1 min-w-[32px] text-center border rounded text-sm font-medium transition-all duration-150" :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-indigo-50'">
                                    <span v-html="getPaginationLabel(link.label, k, provincias.links.length)"></span>
                                </Link>
                                <span v-else class="px-3 py-1 min-w-[32px] text-center border rounded text-sm bg-gray-100 text-gray-400" v-html="getPaginationLabel(link.label, k, provincias.links.length)"></span>
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
                    <InputLabel for="nombre" value="Nombre de la Provincia" />
                    <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" placeholder="Ej: Misiones" />
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