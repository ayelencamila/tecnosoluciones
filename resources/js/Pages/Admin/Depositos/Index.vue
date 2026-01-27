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
    depositos: Object
});

const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');

const form = useForm({
    deposito_id: null,
    nombre: '',
    direccion: '',
    activo: true
});

const openCreateModal = () => {
    isEditing.value = false;
    modalTitle.value = 'Nuevo Depósito';
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (deposito) => {
    isEditing.value = true;
    modalTitle.value = 'Editar Depósito';
    form.deposito_id = deposito.deposito_id;
    form.nombre = deposito.nombre;
    form.direccion = deposito.direccion;
    form.activo = Boolean(deposito.activo);
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.depositos.update', form.deposito_id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.depositos.store'), {
            onSuccess: () => closeModal()
        });
    }
};

// Lógica visual para el estado
const getEstadoBadge = (activo) => {
    return activo 
        ? 'bg-green-100 text-green-800' 
        : 'bg-red-100 text-red-800';
};

const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Depósitos" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Depósitos</h2>
                <PrimaryButton @click="openCreateModal">
                    + Nuevo Depósito
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ubicación</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="dep in depositos.data" :key="dep.deposito_id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{{ dep.deposito_id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">
                                        {{ dep.nombre }}
                                        <span v-if="dep.deposito_id === 1" class="ml-2 text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full">Principal</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ dep.direccion || '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getEstadoBadge(dep.activo)]">
                                            {{ dep.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(dep)" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="depositos.links">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in depositos.links" :key="k">
                                <Link v-if="link.url" :href="link.url" class="px-3 py-1 min-w-[32px] text-center border rounded text-sm font-medium transition-all duration-150" :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-indigo-50'">
                                    <span v-html="getPaginationLabel(link.label, k, depositos.links.length)"></span>
                                </Link>
                                <span v-else class="px-3 py-1 min-w-[32px] text-center border rounded text-sm bg-gray-100 text-gray-400" v-html="getPaginationLabel(link.label, k, depositos.links.length)"></span>
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
                    <InputLabel for="nombre" value="Nombre del Depósito" />
                    <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" placeholder="Ej: Depósito Central" />
                    <InputError :message="form.errors.nombre" class="mt-2" />
                </div>

                <div class="mb-4">
                    <InputLabel for="direccion" value="Ubicación / Dirección" />
                    <TextInput id="direccion" v-model="form.direccion" type="text" class="mt-1 block w-full" placeholder="Ej: Calle Falsa 123" />
                    <InputError :message="form.errors.direccion" class="mt-2" />
                </div>

                <div class="mb-4 block">
                    <label class="flex items-center">
                        <Checkbox name="activo" v-model:checked="form.activo" />
                        <span class="ml-2 text-sm text-gray-600">Depósito Activo (Habilitado para stock)</span>
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