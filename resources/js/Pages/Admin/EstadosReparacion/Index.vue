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
    estados: Object
});

// --- Lógica del Modal ---
const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');

// Formulario (Usamos los nombres de campo de tu base de datos)
const form = useForm({
    estadoReparacionID: null, 
    nombreEstado: '',
    descripcion: ''
});

const openCreateModal = () => {
    isEditing.value = false;
    modalTitle.value = 'Nuevo Estado de Reparación';
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (estado) => {
    isEditing.value = true;
    modalTitle.value = 'Editar Estado';
    form.estadoReparacionID = estado.estadoReparacionID;
    form.nombreEstado = estado.nombreEstado;
    form.descripcion = estado.descripcion;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.estados-reparacion.update', form.estadoReparacionID), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.estados-reparacion.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const deleteEstado = (estado) => {
    if (confirm(`¿Estás seguro de eliminar el estado "${estado.nombreEstado}"?`)) {
        router.delete(route('admin.estados-reparacion.destroy', estado.estadoReparacionID));
    }
};

// Helper para las flechas de paginación
const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Estados de Reparación" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Estados de Reparación</h2>
                <PrimaryButton @click="openCreateModal">
                    + Nuevo Estado
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="estado in estados.data" :key="estado.estadoReparacionID">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{{ estado.estadoReparacionID }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ estado.nombreEstado }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ estado.descripcion || '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(estado)" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
                                        <button @click="deleteEstado(estado)" class="text-red-600 hover:text-red-900">Borrar</button>
                                    </td>
                                </tr>
                                <tr v-if="estados.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay estados registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="estados.links">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in estados.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    class="px-3 py-1 min-w-[32px] text-center border rounded text-sm font-medium transition-all duration-150"
                                    :class="link.active 
                                        ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500 border-indigo-600' 
                                        : 'bg-white text-gray-600 border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'"
                                >
                                    <span v-html="getPaginationLabel(link.label, k, estados.links.length)"></span>
                                </Link>
                                <span 
                                    v-else 
                                    class="px-3 py-1 min-w-[32px] text-center border rounded text-sm bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed" 
                                    v-html="getPaginationLabel(link.label, k, estados.links.length)"
                                ></span>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ modalTitle }}
                </h2>

                <div class="mb-4">
                    <InputLabel for="nombreEstado" value="Nombre del Estado" />
                    <TextInput id="nombreEstado" v-model="form.nombreEstado" type="text" class="mt-1 block w-full" placeholder="Ej: Esperando Repuesto" />
                    <InputError :message="form.errors.nombreEstado" class="mt-2" />
                </div>

                <div class="mb-4">
                    <InputLabel for="descripcion" value="Descripción (Opcional)" />
                    <TextInput id="descripcion" v-model="form.descripcion" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.descripcion" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancelar </SecondaryButton>
                    <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="submit">
                        Guardar
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>