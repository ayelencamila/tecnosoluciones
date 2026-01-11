<template>
    <div class="space-y-1">
        <InputLabel v-if="label" :for="id" :value="label" />
        
        <div class="flex gap-2">
            <!-- Select Principal -->
            <div class="flex-1">
                <select
                    :id="id"
                    :value="modelValue"
                    @change="$emit('update:modelValue', $event.target.value)"
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                    :class="{ 'border-red-500': error }"
                    :disabled="disabled || loading"
                >
                    <option value="">{{ placeholder }}</option>
                    <option v-for="option in options" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>

            <!-- Botón Configurar -->
            <button
                type="button"
                @click="openModal"
                class="inline-flex items-center justify-center w-10 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors h-10"
                :disabled="disabled"
                title="Configurar opciones"
            >
                <span class="text-base">⚙️</span>
            </button>
        </div>

        <InputError v-if="error" :message="error" />

        <!-- Modal de Configuración usando el componente Modal -->
        <Modal :show="showModal" @close="closeModal" max-width="lg">
            <div class="bg-indigo-600 px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-medium text-white" id="modal-title">
                    Configurar {{ label }}
                </h3>
                <button
                    @click="closeModal"
                    class="text-indigo-200 hover:text-white transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="bg-white px-6 py-4">
                <!-- Formulario de Nuevo Item -->
                <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <label :for="`${id}_new_item`" class="block text-sm font-medium text-gray-700 mb-2">
                        Agregar Nuevo
                    </label>
                    <div class="flex gap-2">
                        <input
                            :id="`${id}_new_item`"
                            :name="`${id}_new_item`"
                            v-model="newItemName"
                            type="text"
                            :placeholder="`Nuevo ${label}...`"
                            class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                            @keyup.enter="createItem"
                            autocomplete="off"
                        />
                        <button
                            type="button"
                            @click="createItem"
                            :disabled="!newItemName.trim() || creating"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                        >
                            <svg v-if="creating" class="animate-spin h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Agregar
                        </button>
                    </div>
                </div>

                <!-- Lista de Items Existentes -->
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    <div
                        v-for="option in options"
                        :key="option.value"
                        class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <span class="text-sm font-medium text-gray-900">{{ option.label }}</span>
                        <button
                            type="button"
                            @click="deleteItem(option.value)"
                            :disabled="deleting === option.value"
                            class="text-red-600 hover:text-red-800 transition-colors disabled:opacity-50"
                            title="Eliminar"
                        >
                            <svg v-if="deleting === option.value" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    <div v-if="options.length === 0" class="text-center py-8 text-gray-500">
                        No hay items registrados
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-3 flex justify-end">
                <button
                    type="button"
                    @click="closeModal"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                >
                    Cerrar
                </button>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Seleccione una opción...' },
    error: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    apiEndpoint: { type: String, required: true }, // Ej: '/api/tipos-cliente'
    nameField: { type: String, default: 'nombre' }, // Campo que contiene el nombre
    additionalData: { type: Object, default: () => ({}) }, // Datos adicionales para el POST
});

const emit = defineEmits(['update:modelValue', 'refresh']);

const showModal = ref(false);
const newItemName = ref('');
const creating = ref(false);
const deleting = ref(null);

const openModal = () => {
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    newItemName.value = '';
};

const createItem = async () => {
    if (!newItemName.value.trim()) return;

    creating.value = true;
    try {
        await axios.post(props.apiEndpoint, {
            [props.nameField]: newItemName.value.trim(),
            ...props.additionalData
        });
        
        newItemName.value = '';
        emit('refresh'); // Notificar al padre que recargue los datos
        
        // Mostrar mensaje de éxito (opcional)
        // alert('Item creado exitosamente');
    } catch (error) {
        console.error('Error al crear item:', error);
        alert('Error al crear el item. Por favor, intente nuevamente.');
    } finally {
        creating.value = false;
    }
};

const deleteItem = async (id) => {
    if (!confirm('¿Está seguro de eliminar este item?')) return;

    deleting.value = id;
    try {
        await axios.delete(`${props.apiEndpoint}/${id}`);
        emit('refresh'); // Notificar al padre que recargue los datos
    } catch (error) {
        console.error('Error al eliminar item:', error);
        // Mostrar el mensaje del servidor si existe
        const serverMessage = error.response?.data?.message;
        alert(serverMessage || 'Error al eliminar el item. Puede estar siendo utilizado por otros registros.');
    } finally {
        deleting.value = null;
    }
};
</script>
