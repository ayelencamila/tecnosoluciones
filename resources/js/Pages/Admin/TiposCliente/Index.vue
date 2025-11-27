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

// Recibimos los tipos desde el controlador
const props = defineProps({
    tipos: Object
});

// --- Lógica del Modal ---
const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');

// Formulario adaptado a TipoCliente (nombreTipo, tipoClienteID)
const form = useForm({
    tipoClienteID: null,
    nombreTipo: '',
    descripcion: ''
});

const openCreateModal = () => {
    isEditing.value = false;
    modalTitle.value = 'Nuevo Tipo de Cliente';
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (tipo) => {
    isEditing.value = true;
    modalTitle.value = 'Editar Tipo de Cliente';
    form.tipoClienteID = tipo.tipoClienteID;
    form.nombreTipo = tipo.nombreTipo;
    form.descripcion = tipo.descripcion;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        // Ruta update con la PK correcta
        form.put(route('admin.tipos-cliente.update', form.tipoClienteID), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.tipos-cliente.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const deleteTipo = (tipo) => {
    if (confirm(`¿Estás seguro de eliminar el tipo "${tipo.nombreTipo}"?`)) {
        router.delete(route('admin.tipos-cliente.destroy', tipo.tipoClienteID));
    }
};

// Paginación
const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Tipos de Cliente" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tipos de Cliente</h2>
                <PrimaryButton @click="openCreateModal">
                    + Nuevo Tipo
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="tipo in tipos.data" :key="tipo.tipoClienteID">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{{ tipo.tipoClienteID }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ tipo.nombreTipo }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ tipo.descripcion || '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(tipo)" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
                                        <button @click="deleteTipo(tipo)" class="text-red-600 hover:text-red-900">Borrar</button>
                                    </td>
                                </tr>
                                <tr v-if="tipos.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay tipos de cliente registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="tipos.links">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in tipos.links" :key="k">
                                <Link v-if="link.url" :href="link.url" class="px-3 py-1 min-w-[32px] text-center border rounded text-sm font-medium transition-all duration-150" :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 hover:bg-indigo-50'">
                                    <span v-html="getPaginationLabel(link.label, k, tipos.links.length)"></span>
                                </Link>
                                <span v-else class="px-3 py-1 min-w-[32px] text-center border rounded text-sm bg-gray-100 text-gray-400" v-html="getPaginationLabel(link.label, k, tipos.links.length)"></span>
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
                    <InputLabel for="nombreTipo" value="Nombre del Tipo" />
                    <TextInput id="nombreTipo" v-model="form.nombreTipo" type="text" class="mt-1 block w-full" placeholder="Ej: Distribuidor" />
                    <InputError :message="form.errors.nombreTipo" class="mt-2" />
                </div>

                <div class="mb-4">
                    <InputLabel for="descripcion" value="Descripción" />
                    <TextInput id="descripcion" v-model="form.descripcion" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.descripcion" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                    <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="submit">Guardar</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>