<script setup>
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    plantilla: Object,
});

const form = useForm({
    contenido_plantilla: props.plantilla.contenido_plantilla,
    horario_inicio: props.plantilla.horario_inicio,
    horario_fin: props.plantilla.horario_fin,
    activo: props.plantilla.activo,
    motivo_modificacion: '',
});

const mensajePreview = ref('');
const mostrandoPreview = ref(false);

const submit = () => {
    form.put(`/plantillas-whatsapp/${props.plantilla.plantilla_id}`, {
        preserveScroll: true,
    });
};

const generarPreview = async () => {
    try {
        const response = await fetch(`/plantillas-whatsapp/${props.plantilla.plantilla_id}/preview`, {
            headers: {
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        mensajePreview.value = data.mensaje;
        mostrandoPreview.value = true;
    } catch (error) {
        console.error('Error al generar preview:', error);
    }
};

const insertarVariable = (variable) => {
    const textarea = document.getElementById('contenido_plantilla');
    const inicio = textarea.selectionStart;
    const fin = textarea.selectionEnd;
    const textoAntes = form.contenido_plantilla.substring(0, inicio);
    const textoDespues = form.contenido_plantilla.substring(fin);
    form.contenido_plantilla = textoAntes + `{${variable}}` + textoDespues;
    
    // Mantener foco y posición del cursor
    textarea.focus();
    setTimeout(() => {
        const nuevaPosicion = inicio + variable.length + 2;
        textarea.setSelectionRange(nuevaPosicion, nuevaPosicion);
    }, 0);
};
</script>

<template>
    <AppLayout :title="`Editar Plantilla: ${plantilla.nombre}`">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Editar Plantilla WhatsApp
                </h2>
                <Link href="/plantillas-whatsapp" 
                      class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    ← Volver al listado
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Información de la plantilla -->
                <div class="bg-white shadow sm:rounded-lg mb-6">
                    <div class="px-4 py-5 sm:p-6 bg-gradient-to-r from-purple-50 to-indigo-50">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ plantilla.nombre }}</h3>
                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <code class="text-xs bg-white px-2 py-1 rounded">{{ plantilla.tipo_evento }}</code>
                            </span>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Última modificación: {{ plantilla.usuario_modificacion }} ({{ plantilla.updated_at }})
                            </span>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Columna principal: Editor -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Editor de contenido -->
                            <div class="bg-white shadow sm:rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Contenido de la Plantilla</h3>
                                    
                                    <div>
                                        <InputLabel for="contenido_plantilla" value="Mensaje" />
                                        <textarea
                                            id="contenido_plantilla"
                                            v-model="form.contenido_plantilla"
                                            rows="15"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm"
                                            placeholder="Escribe el contenido del mensaje usando variables como {nombre_cliente}..."
                                        ></textarea>
                                        <InputError :message="form.errors.contenido_plantilla" class="mt-2" />
                                    </div>

                                    <div class="mt-4 flex space-x-2">
                                        <button
                                            type="button"
                                            @click="generarPreview"
                                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Vista Previa
                                        </button>
                                    </div>

                                    <!-- Preview -->
                                    <div v-if="mostrandoPreview" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                                        <h4 class="text-sm font-medium text-green-900 mb-2 flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                            </svg>
                                            Vista Previa del Mensaje
                                        </h4>
                                        <pre class="whitespace-pre-wrap text-sm text-gray-800 font-sans">{{ mensajePreview }}</pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuración de horarios -->
                            <div class="bg-white shadow sm:rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Horarios de Envío</h3>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel for="horario_inicio" value="Hora de inicio" />
                                            <TextInput
                                                id="horario_inicio"
                                                v-model="form.horario_inicio"
                                                type="time"
                                                class="mt-1 block w-full"
                                            />
                                            <InputError :message="form.errors.horario_inicio" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="horario_fin" value="Hora de fin" />
                                            <TextInput
                                                id="horario_fin"
                                                v-model="form.horario_fin"
                                                type="time"
                                                class="mt-1 block w-full"
                                            />
                                            <InputError :message="form.errors.horario_fin" class="mt-2" />
                                        </div>
                                    </div>

                                    <p class="mt-2 text-sm text-gray-500">
                                        Los mensajes solo se enviarán dentro de este rango horario.
                                    </p>
                                </div>
                            </div>

                            <!-- Estado y Motivo -->
                            <div class="bg-white shadow sm:rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Estado y Motivo del Cambio (CU-30)</h3>
                                    
                                    <div class="flex items-center mb-4">
                                        <Checkbox 
                                            id="activo" 
                                            v-model:checked="form.activo"
                                        />
                                        <label for="activo" class="ml-2 block text-sm text-gray-900 cursor-pointer">
                                            Plantilla activa (si está deshabilitada, no se usará para envíos)
                                        </label>
                                    </div>

                                    <div>
                                        <InputLabel for="motivo_modificacion" value="Motivo de la modificación *" />
                                        <textarea
                                            id="motivo_modificacion"
                                            v-model="form.motivo_modificacion"
                                            rows="3"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            placeholder="Describe por qué estás modificando esta plantilla (mínimo 10 caracteres)..."
                                            required
                                        ></textarea>
                                        <InputError :message="form.errors.motivo_modificacion" class="mt-2" />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Este motivo quedará registrado en el historial de cambios (CU-30 Paso 7-8).
                                        </p>
                                    </div>

                                    <div class="mt-6 flex items-center justify-end space-x-3">
                                        <Link 
                                            href="/plantillas-whatsapp"
                                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                            Cancelar
                                        </Link>
                                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Guardar Cambios
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar: Variables disponibles -->
                        <div class="lg:col-span-1">
                            <div class="bg-white shadow sm:rounded-lg sticky top-6">
                                <div class="px-4 py-5 sm:p-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                        </svg>
                                        Variables Disponibles
                                    </h3>
                                    
                                    <p class="text-sm text-gray-600 mb-4">
                                        Haz clic en una variable para insertarla en la posición del cursor:
                                    </p>

                                    <div class="space-y-2">
                                        <button
                                            v-for="variable in plantilla.variables_disponibles"
                                            :key="variable"
                                            type="button"
                                            @click="insertarVariable(variable)"
                                            class="w-full flex items-center justify-between px-3 py-2 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-md text-left transition duration-150">
                                            <span class="text-sm font-mono text-indigo-900">{<span>{{ variable }}</span>}</span>
                                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <p class="text-xs text-yellow-800">
                                            <strong>⚠️ Importante:</strong> Las variables deben escribirse exactamente como se muestran, entre llaves.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
