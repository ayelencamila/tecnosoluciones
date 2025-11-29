<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({ unidades: Object });
const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');

const form = useForm({ id: null, nombre: '', abreviatura: '', activo: true });

const openCreateModal = () => {
    isEditing.value = false; modalTitle.value = 'Nueva Unidad';
    form.reset(); form.clearErrors(); showModal.value = true;
};

const openEditModal = (u) => {
    isEditing.value = true; modalTitle.value = 'Editar Unidad';
    form.id = u.id; form.nombre = u.nombre; form.abreviatura = u.abreviatura; form.activo = Boolean(u.activo);
    form.clearErrors(); showModal.value = true;
};

const closeModal = () => { showModal.value = false; form.reset(); };

const submit = () => {
    if (isEditing.value) form.put(route('admin.unidades-medida.update', form.id), { onSuccess: () => closeModal() });
    else form.post(route('admin.unidades-medida.store'), { onSuccess: () => closeModal() });
};

const deleteUnidad = (u) => {
    if (confirm('¿Eliminar unidad "' + u.nombre + '"?')) router.delete(route('admin.unidades-medida.destroy', u.id));
};

const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Unidades de Medida" />
    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800">Unidades de Medida</h2>
                <PrimaryButton @click="openCreateModal">+ Nueva</PrimaryButton>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200 mb-4">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Abreviatura</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="u in unidades.data" :key="u.id">
                            <td class="px-6 py-4 font-bold">{{ u.nombre }}</td>
                            <td class="px-6 py-4 text-sm bg-gray-50 rounded"><span class="font-mono">{{ u.abreviatura }}</span></td>
                            <td class="px-6 py-4">
                                <span :class="u.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 inline-flex text-xs font-semibold rounded-full">
                                    {{ u.activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openEditModal(u)" class="text-indigo-600 mr-3">Editar</button>
                                <button @click="deleteUnidad(u)" class="text-red-600">Borrar</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-4 flex justify-end" v-if="unidades.links">
                    <template v-for="(link, k) in unidades.links" :key="k">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="getPaginationLabel(link.label, k, unidades.links.length)" />
                        <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-indigo-50 text-indigo-700': link.active }" :href="link.url" v-html="getPaginationLabel(link.label, k, unidades.links.length)" />
                    </template>
                </div>
            </div>
        </div>
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium mb-4">{{ modalTitle }}</h2>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="col-span-1">
                        <InputLabel value="Nombre" />
                        <TextInput v-model="form.nombre" class="w-full mt-1" placeholder="Ej: Kilogramo" />
                        <InputError :message="form.errors.nombre" class="mt-2" />
                    </div>
                    <div class="col-span-1">
                        <InputLabel value="Abreviatura" />
                        <TextInput v-model="form.abreviatura" class="w-full mt-1" placeholder="Ej: kg" />
                        <InputError :message="form.errors.abreviatura" class="mt-2" />
                    </div>
                </div>
                <div class="mb-4">
                    <label class="flex items-center">
                        <Checkbox v-model:checked="form.activo" />
                        <span class="ml-2 text-sm text-gray-600">Unidad Activa</span>
                    </label>
                </div>
                <div class="flex justify-end">
                    <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                    <PrimaryButton class="ml-3" @click="submit" :disabled="form.processing">Guardar</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
