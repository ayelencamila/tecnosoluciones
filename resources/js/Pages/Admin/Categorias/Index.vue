<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

// Recibimos las categorías desde el controlador
const props = defineProps({
    categorias: Object
});

// --- Lógica del Modal ---
const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');

const form = useForm({
    id: null,
    nombre: '',
    descripcion: '',
    activo: true
});

const openCreateModal = () => {
    isEditing.value = false;
    modalTitle.value = 'Nueva Categoría';
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (categoria) => {
    isEditing.value = true;
    modalTitle.value = 'Editar Categoría';
    form.id = categoria.id;
    form.nombre = categoria.nombre;
    form.descripcion = categoria.descripcion;
    form.activo = Boolean(categoria.activo);
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.categorias.update', form.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.categorias.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const deleteCategoria = (categoria) => {
    if (confirm(`¿Estás seguro de eliminar "${categoria.nombre}"?`)) {
        router.delete(route('admin.categorias.destroy', categoria.id));
    }
};

// --- PAGINACIÓN (Helper visual igual a Productos) ---
const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;'; // Flecha izquierda
    if (index === totalLinks - 1) return '&raquo;'; // Flecha derecha
    return label;
};
</script>

<template>
    <Head title="Gestión de Categorías" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Categorías</h2>
                <PrimaryButton @click="openCreateModal">
                    + Nueva Categoría
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="cat in categorias.data" :key="cat.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{{ cat.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ cat.nombre }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ cat.descripcion || '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="cat.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ cat.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(cat)" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
                                        <button @click="deleteCategoria(cat)" class="text-red-600 hover:text-red-900">Borrar</button>
                                    </td>
                                </tr>
                                <tr v-if="categorias.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No hay categorías registradas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="categorias.links">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in categorias.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    class="px-3 py-1 min-w-[32px] text-center border rounded text-sm font-medium transition-all duration-150"
                                    :class="link.active 
                                        ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-500 border-indigo-600' 
                                        : 'bg-white text-gray-600 border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'"
                                >
                                    <span v-html="getPaginationLabel(link.label, k, categorias.links.length)"></span>
                                </Link>
                                <span 
                                    v-else 
                                    class="px-3 py-1 min-w-[32px] text-center border rounded text-sm bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed" 
                                    v-html="getPaginationLabel(link.label, k, categorias.links.length)"
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
                    <InputLabel for="nombre" value="Nombre de la Categoría" />
                    <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" placeholder="Ej: Insumos" />
                    <InputError :message="form.errors.nombre" class="mt-2" />
                </div>

                <div class="mb-4">
                    <InputLabel for="descripcion" value="Descripción (Opcional)" />
                    <TextInput id="descripcion" v-model="form.descripcion" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.descripcion" class="mt-2" />
                </div>

                <div class="mb-4 block">
                    <label class="flex items-center">
                        <Checkbox name="activo" v-model:checked="form.activo" />
                        <span class="ml-2 text-sm text-gray-600">Categoría Activa</span>
                    </label>
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