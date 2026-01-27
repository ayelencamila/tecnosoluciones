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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar Plantilla
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Breadcrumb -->
                <nav class="flex items-center mb-6">
                    <ol class="flex items-center space-x-2 text-sm">
                        <li>
                            <Link :href="route('dashboard')" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                </svg>
                            </Link>
                        </li>
                        <li class="text-gray-300">/</li>
                        <li>
                            <Link href="/plantillas-whatsapp" class="text-gray-500 hover:text-gray-700 transition-colors">
                                Plantillas WhatsApp
                            </Link>
                        </li>
                        <li class="text-gray-300">/</li>
                        <li class="text-gray-900 font-medium">{{ plantilla.nombre }}</li>
                    </ol>
                </nav>

                <!-- Header Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ plantilla.nombre }}</h3>
                                <div class="flex items-center gap-3 mt-1">
                                    <code class="text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-600 font-mono">{{ plantilla.tipo_evento }}</code>
                                    <span class="text-xs text-gray-500">
                                        Última modificación: {{ plantilla.usuario_modificacion }} • {{ plantilla.updated_at }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <span v-if="plantilla.activo" class="px-3 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">
                            Activa
                        </span>
                        <span v-else class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                            Inactiva
                        </span>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- Columna principal -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- Editor de contenido -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h3 class="font-semibold text-gray-900 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Contenido del Mensaje
                                    </h3>
                                </div>
                                <div class="p-5">
                                    <textarea
                                        id="contenido_plantilla"
                                        v-model="form.contenido_plantilla"
                                        rows="12"
                                        class="w-full border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-lg font-mono text-sm resize-none"
                                        placeholder="Escribe el contenido del mensaje usando variables como {nombre_cliente}..."
                                    ></textarea>
                                    <InputError :message="form.errors.contenido_plantilla" class="mt-2" />
                                    
                                    <div class="mt-4 flex items-center gap-3">
                                        <button
                                            type="button"
                                            @click="generarPreview"
                                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition-colors">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Vista Previa
                                        </button>
                                    </div>

                                    <!-- Preview -->
                                    <div v-if="mostrandoPreview" class="mt-4">
                                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4">
                                            <div class="flex items-center gap-2 mb-3">
                                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                                    </svg>
                                                </div>
                                                <span class="text-sm font-medium text-green-800">Vista previa del mensaje</span>
                                            </div>
                                            <div class="bg-white rounded-lg p-3 shadow-sm">
                                                <pre class="whitespace-pre-wrap text-sm text-gray-800 font-sans leading-relaxed">{{ mensajePreview }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Horarios -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h3 class="font-semibold text-gray-900 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Horarios de Envío
                                    </h3>
                                </div>
                                <div class="p-5">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel for="horario_inicio" value="Hora de inicio" class="text-sm font-medium text-gray-700" />
                                            <TextInput
                                                id="horario_inicio"
                                                v-model="form.horario_inicio"
                                                type="time"
                                                class="mt-1 block w-full border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-lg"
                                            />
                                            <InputError :message="form.errors.horario_inicio" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="horario_fin" value="Hora de fin" class="text-sm font-medium text-gray-700" />
                                            <TextInput
                                                id="horario_fin"
                                                v-model="form.horario_fin"
                                                type="time"
                                                class="mt-1 block w-full border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-lg"
                                            />
                                            <InputError :message="form.errors.horario_fin" class="mt-2" />
                                        </div>
                                    </div>
                                    <p class="mt-3 text-xs text-gray-500">
                                        Los mensajes solo se enviarán dentro de este rango horario. Fuera de este horario, se programarán para el siguiente día hábil.
                                    </p>
                                </div>
                            </div>

                            <!-- Estado y Motivo -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h3 class="font-semibold text-gray-900 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Estado y Registro de Cambio
                                    </h3>
                                </div>
                                <div class="p-5">
                                    <label class="flex items-center cursor-pointer">
                                        <Checkbox id="activo" v-model:checked="form.activo" />
                                        <span class="ml-3 text-sm text-gray-700">
                                            <strong>Plantilla activa</strong> — Si está deshabilitada, no se usará para envíos automáticos
                                        </span>
                                    </label>

                                    <div class="mt-5">
                                        <InputLabel for="motivo_modificacion" class="text-sm font-medium text-gray-700">
                                            Motivo de la modificación <span class="text-rose-500">*</span>
                                        </InputLabel>
                                        <textarea
                                            id="motivo_modificacion"
                                            v-model="form.motivo_modificacion"
                                            rows="3"
                                            class="mt-1 block w-full border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-lg text-sm"
                                            placeholder="Describe brevemente por qué estás modificando esta plantilla..."
                                            required
                                        ></textarea>
                                        <InputError :message="form.errors.motivo_modificacion" class="mt-2" />
                                        <p class="mt-2 text-xs text-gray-500">
                                            Mínimo 10 caracteres. Este motivo quedará registrado en el historial de cambios.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <Link href="/plantillas-whatsapp"
                                      class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancelar
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                                    class="px-5 py-2.5 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors inline-flex items-center">
                                    <svg v-if="!form.processing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <svg v-else class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                            </div>
                        </div>

                        <!-- Sidebar: Variables -->
                        <div class="lg:col-span-1">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 sticky top-6 overflow-hidden">
                                <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-purple-50">
                                    <h3 class="font-semibold text-gray-900 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        Variables Disponibles
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1">Clic para insertar en el cursor</p>
                                </div>
                                <div class="p-4">
                                    <div class="space-y-2 max-h-96 overflow-y-auto">
                                        <button
                                            v-for="variable in plantilla.variables_disponibles"
                                            :key="variable"
                                            type="button"
                                            @click="insertarVariable(variable)"
                                            class="w-full flex items-center justify-between px-3 py-2.5 bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-indigo-300 rounded-lg text-left transition-all duration-150 group">
                                            <span class="text-sm font-mono text-gray-700 group-hover:text-indigo-700">
                                                {<span class="text-indigo-600">{{ variable }}</span>}
                                            </span>
                                            <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <p class="text-xs text-amber-800">
                                                Las variables deben escribirse <strong>exactamente</strong> como se muestran, entre llaves.
                                            </p>
                                        </div>
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
