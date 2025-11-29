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

const props = defineProps({ proveedores: Object });
const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');
const form = useForm({ id: null, razon_social: '', cuit: '', email: '', telefono: '' });

const openCreateModal = () => {
    isEditing.value = false; modalTitle.value = 'Nuevo Proveedor';
    form.reset(); form.clearErrors(); showModal.value = true;
};
const openEditModal = (p) => {
    isEditing.value = true; modalTitle.value = 'Editar Proveedor';
    form.id = p.id; form.razon_social = p.razon_social; form.cuit = p.cuit; form.email = p.email; form.telefono = p.telefono;
    form.clearErrors(); showModal.value = true;
};
const closeModal = () => { showModal.value = false; form.reset(); };
const submit = () => {
    if (isEditing.value) form.put(route('admin.proveedores.update', form.id), { onSuccess: () => closeModal() });
    else form.post(route('admin.proveedores.store'), { onSuccess: () => closeModal() });
};
const deleteProv = (p) => {
    if (confirm('¿Eliminar proveedor "' + p.razon_social + '"?')) router.delete(route('admin.proveedores.destroy', p.id));
};
</script>
<template>
    <Head title="Proveedores" />
    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center"><h2 class="font-semibold text-xl text-gray-800">Proveedores</h2><PrimaryButton @click="openCreateModal">+ Nuevo</PrimaryButton></div>
        </template>
        <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200 mb-4"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Razón Social</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CUIT</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contacto</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><tr v-for="p in proveedores.data" :key="p.id"><td class="px-6 py-4 font-bold">{{ p.razon_social }}</td><td class="px-6 py-4 text-sm">{{ p.cuit }}</td><td class="px-6 py-4 text-sm">{{ p.email }} / {{ p.telefono }}</td><td class="px-6 py-4 text-right"><button @click="openEditModal(p)" class="text-indigo-600 mr-3">Editar</button><button @click="deleteProv(p)" class="text-red-600">Borrar</button></td></tr></tbody></table>
        </div></div>
        <Modal :show="showModal" @close="closeModal"><div class="p-6"><h2 class="text-lg font-medium mb-4">{{ modalTitle }}</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2"><InputLabel value="Razón Social" /><TextInput v-model="form.razon_social" class="w-full mt-1" /><InputError :message="form.errors.razon_social" class="mt-2" /></div>
                <div><InputLabel value="CUIT" /><TextInput v-model="form.cuit" class="w-full mt-1" /><InputError :message="form.errors.cuit" class="mt-2" /></div>
                <div><InputLabel value="Teléfono" /><TextInput v-model="form.telefono" class="w-full mt-1" /></div>
                <div class="col-span-2"><InputLabel value="Email" /><TextInput v-model="form.email" type="email" class="w-full mt-1" /></div>
            </div>
            <div class="flex justify-end mt-6"><SecondaryButton @click="closeModal">Cancelar</SecondaryButton><PrimaryButton class="ml-3" @click="submit" :disabled="form.processing">Guardar</PrimaryButton></div></div></Modal>
    </AppLayout>
</template>
