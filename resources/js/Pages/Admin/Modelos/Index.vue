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

const props = defineProps({
    modelos: Object,
    marcas: Array // Recibimos la lista de marcas para el select
});

const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');

const form = useForm({
    id: null,
    marca_id: '',
    nombre: '',
    activo: true
});

const openCreateModal = () => {
    isEditing.value = false;
    modalTitle.value = 'Nuevo Modelo';
    form.reset();
    // Si hay marcas, pre-seleccionamos la primera para ayudar
    if (props.marcas.length > 0) form.marca_id = props.marcas[0].id;
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (modelo) => {
    isEditing.value = true;
    modalTitle.value = 'Editar Modelo';
    form.id = modelo.id;
    form.marca_id = modelo.marca_id;
    form.nombre = modelo.nombre;
    form.activo = Boolean(modelo.activo);
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => { showModal.value = false; form.reset(); };

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.modelos.update', form.id), { onSuccess: () => closeModal() });
    } else {
        form.post(route('admin.modelos.store'), { onSuccess: () => closeModal() });
    }
};

const deleteModelo = (modelo) => {
    if (confirm('¿Eliminar "' + modelo.nombre + '"?')) {
        router.delete(route('admin.modelos.destroy', modelo.id));
    }
};

const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Modelos" />
    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Modelos</h2>
                <PrimaryButton @click="openCreateModal">+ Nuevo Modelo</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <table class="min-w-full divide-y divide-gray-200 mb-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marca</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modelo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="mod in modelos.data" :key="mod.id">
                                <td class="px-6 py-4 text-sm text-gray-600">{{ mod.marca?.nombre || '-' }}</td>
                                <td class="px-6 py-4 font-bold text-gray-900">{{ mod.nombre }}</td>
                                <td class="px-6 py-4">
                                    <span :class="mod.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 inline-flex text-xs font-semibold rounded-full">
                                        {{ mod.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button @click="openEditModal(mod)" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
                                    <button @click="deleteModelo(mod)" class="text-red-600 hover:text-red-900">Borrar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="mt-4 flex justify-end" v-if="modelos.links">
                        <template v-for="(link, k) in modelos.links" :key="k">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="getPaginationLabel(link.label, k, modelos.links.length)" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-indigo-50 text-indigo-700': link.active }" :href="link.url" v-html="getPaginationLabel(link.label, k, modelos.links.length)" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ modalTitle }}</h2>
                
                <div class="mb-4">
                    <InputLabel for="marca" value="Marca" />
                    <select id="marca" v-model="form.marca_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="" disabled>Seleccione una marca</option>
                        <option v-for="m in marcas" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                    </select>
                    <InputError :message="form.errors.marca_id" class="mt-2" />
                </div>

                <div class="mb-4">
                    <InputLabel for="nombre" value="Nombre del Modelo" />
                    <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" placeholder="Ej: Galaxy S20" />
                    <InputError :message="form.errors.nombre" class="mt-2" />
                </div>

                <div class="mb-4 block">
                    <label class="flex items-center">
                        <Checkbox name="activo" v-model:checked="form.activo" />
                        <span class="ml-2 text-sm text-gray-600">Activo</span>
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
