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
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({ marcas: Object });
const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');

const form = useForm({ id: null, nombre: '', activo: true });

const openCreateModal = () => {
    isEditing.value = false;
    modalTitle.value = 'Nueva Marca';
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (marca) => {
    isEditing.value = true;
    modalTitle.value = 'Editar Marca';
    form.id = marca.id;
    form.nombre = marca.nombre;
    form.activo = Boolean(marca.activo);
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => { showModal.value = false; form.reset(); };

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.marcas.update', form.id), { onSuccess: () => closeModal() });
    } else {
        form.post(route('admin.marcas.store'), { onSuccess: () => closeModal() });
    }
};

const deleteMarca = (marca) => {
    if (confirm('¿Eliminar "' + marca.nombre + '"?')) {
        router.delete(route('admin.marcas.destroy', marca.id));
    }
};

const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Marcas" />
    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Marcas</h2>
                <PrimaryButton @click="openCreateModal">+ Nueva Marca</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <table class="min-w-full divide-y divide-gray-200 mb-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="marca in marcas.data" :key="marca.id">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ marca.nombre }}</td>
                                <td class="px-6 py-4">
                                    <span :class="marca.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 inline-flex text-xs font-semibold rounded-full">
                                        {{ marca.activo ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button @click="openEditModal(marca)" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
                                    <button @click="deleteMarca(marca)" class="text-red-600 hover:text-red-900">Borrar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="mt-4 flex justify-end" v-if="marcas.links">
                        <template v-for="(link, k) in marcas.links" :key="k">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="getPaginationLabel(link.label, k, marcas.links.length)" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-indigo-50 text-indigo-700': link.active }" :href="link.url" v-html="getPaginationLabel(link.label, k, marcas.links.length)" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ modalTitle }}</h2>
                <div class="mb-4">
                    <InputLabel for="nombre" value="Nombre de la Marca" />
                    <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" placeholder="Ej: Samsung" />
                    <InputError :message="form.errors.nombre" class="mt-2" />
                </div>
                <div class="mb-4 block">
                    <label class="flex items-center">
                        <Checkbox name="activo" v-model:checked="form.activo" />
                        <span class="ml-2 text-sm text-gray-600">Activa</span>
                    </label>
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                    <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="submit">Guardar</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
