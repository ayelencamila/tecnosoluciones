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

const props = defineProps({ medios: Object });

const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');

const form = useForm({
    medioPagoID: null,
    nombre: '',
    recargo_porcentaje: 0,
    instrucciones: '',
    activo: true
});

const openCreateModal = () => {
    isEditing.value = false;
    modalTitle.value = 'Nuevo Medio de Pago';
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (medio) => {
    isEditing.value = true;
    modalTitle.value = 'Editar Medio de Pago';
    form.medioPagoID = medio.medioPagoID;
    form.nombre = medio.nombre;
    form.recargo_porcentaje = medio.recargo_porcentaje;
    form.instrucciones = medio.instrucciones;
    form.activo = Boolean(medio.activo);
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.medios-pago.update', form.medioPagoID), { onSuccess: () => closeModal() });
    } else {
        form.post(route('admin.medios-pago.store'), { onSuccess: () => closeModal() });
    }
};

const deleteMedio = (medio) => {
    if (confirm(`¿Eliminar "${medio.nombre}"?`)) {
        router.delete(route('admin.medios-pago.destroy', medio.medioPagoID));
    }
};

const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Medios de Pago" />
    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Medios de Pago</h2>
                <PrimaryButton @click="openCreateModal">+ Nuevo</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <table class="min-w-full divide-y divide-gray-200 mb-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recargo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="m in medios.data" :key="m.medioPagoID">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ m.nombre }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ m.recargo_porcentaje }}%</td>
                                <td class="px-6 py-4">
                                    <span :class="m.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 inline-flex text-xs font-semibold rounded-full">
                                        {{ m.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button @click="openEditModal(m)" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
                                    <button @click="deleteMedio(m)" class="text-red-600 hover:text-red-900">Borrar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ modalTitle }}</h2>
                
                <div class="mb-4">
                    <InputLabel for="nombre" value="Nombre" />
                    <TextInput id="nombre" v-model="form.nombre" class="mt-1 block w-full" />
                    <InputError :message="form.errors.nombre" class="mt-2" />
                </div>

                <div class="mb-4">
                    <InputLabel for="recargo" value="Recargo (%)" />
                    <TextInput id="recargo" type="number" step="0.01" v-model="form.recargo_porcentaje" class="mt-1 block w-full" />
                    <InputError :message="form.errors.recargo_porcentaje" class="mt-2" />
                </div>

                <div class="mb-4">
                    <InputLabel for="instrucciones" value="Instrucciones (Opcional)" />
                    <TextInput id="instrucciones" v-model="form.instrucciones" class="mt-1 block w-full" />
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