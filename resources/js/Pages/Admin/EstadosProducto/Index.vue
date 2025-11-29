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

const props = defineProps({ estados: Object });
const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');
const form = useForm({ id: null, nombre: '' });

const openCreateModal = () => {
    isEditing.value = false; modalTitle.value = 'Nuevo Estado de Producto';
    form.reset(); form.clearErrors(); showModal.value = true;
};
const openEditModal = (estado) => {
    isEditing.value = true; modalTitle.value = 'Editar Estado';
    form.id = estado.id; form.nombre = estado.nombre; form.clearErrors(); showModal.value = true;
};
const closeModal = () => { showModal.value = false; form.reset(); };
const submit = () => {
    if (isEditing.value) form.put(route('admin.estados-producto.update', form.id), { onSuccess: () => closeModal() });
    else form.post(route('admin.estados-producto.store'), { onSuccess: () => closeModal() });
};
const deleteEstado = (estado) => {
    if (confirm('¿Eliminar estado "' + estado.nombre + '"?')) router.delete(route('admin.estados-producto.destroy', estado.id));
};
</script>
<template>
    <Head title="Estados de Producto" />
    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center"><h2 class="font-semibold text-xl text-gray-800">Estados de Producto</h2><PrimaryButton @click="openCreateModal">+ Nuevo</PrimaryButton></div>
        </template>
        <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200 mb-4"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><tr v-for="e in estados.data" :key="e.id"><td class="px-6 py-4 font-bold">{{ e.nombre }}</td><td class="px-6 py-4 text-right"><button @click="openEditModal(e)" class="text-indigo-600 mr-3">Editar</button><button @click="deleteEstado(e)" class="text-red-600">Borrar</button></td></tr></tbody></table>
        </div></div>
        <Modal :show="showModal" @close="closeModal"><div class="p-6"><h2 class="text-lg font-medium mb-4">{{ modalTitle }}</h2><div class="mb-4"><InputLabel value="Nombre" /><TextInput v-model="form.nombre" class="w-full mt-1" /><InputError :message="form.errors.nombre" class="mt-2" /></div><div class="flex justify-end"><SecondaryButton @click="closeModal">Cancelar</SecondaryButton><PrimaryButton class="ml-3" @click="submit" :disabled="form.processing">Guardar</PrimaryButton></div></div></Modal>
    </AppLayout>
</template>
