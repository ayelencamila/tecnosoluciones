<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    grupos: Object, // Recibimos los grupos desde el controlador
});

// Inicializamos el formulario aplanando los grupos
const initialForm = {};
Object.values(props.grupos).flat().forEach(param => {
    initialForm[param.clave] = param.valor;
});

const form = useForm(initialForm);

const submit = () => {
    form.put(route('configuracion.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Opcional: Mostrar toast
        }
    });
};

// Helper para limpiar nombres (ej: "reparacion_sla_dias" -> "Sla Dias")
const formatLabel = (key, desc) => {
    // Si hay descripción, la usamos como label principal, si no, formateamos la clave
    return desc || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};
</script>

<template>
    <Head title="Configuración del Sistema" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Configuración Global del Sistema
                </h2>
                <a 
                    :href="route('auditorias.index', { tabla: 'configuracion' })" 
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Ver Historial de Cambios
                </a>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <form @submit.prevent="submit">
                    
                    <div v-for="(parametros, nombreGrupo) in grupos" :key="nombreGrupo" class="mb-8">
                        
                        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                            <div class="px-4 py-4 sm:px-6 bg-indigo-50 border-b border-indigo-100">
                                <h3 class="text-lg leading-6 font-medium text-indigo-900">
                                    {{ nombreGrupo }}
                                </h3>
                            </div>

                            <div class="px-4 py-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="param in parametros" :key="param.clave">
                                    
                                    <div v-if="typeof param.valor === 'boolean'" class="flex items-start mt-4">
                                        <div class="flex items-center h-5">
                                            <Checkbox 
                                                :id="param.clave" 
                                                :name="param.clave"
                                                v-model:checked="form[param.clave]" 
                                            />
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label :for="param.clave" class="font-medium text-gray-700 cursor-pointer">
                                                {{ formatLabel(param.clave, param.descripcion) }}
                                            </label>
                                            <p class="text-gray-500 text-xs">Habilitar o deshabilitar esta función.</p>
                                        </div>
                                    </div>

                                    <div v-else :class="{ 'md:col-span-2': param.clave.includes('plantilla') }">
                                        <InputLabel :for="param.clave" :value="param.descripcion || param.clave" />
                                        
                                        <!-- Mostrar variables disponibles para plantillas WhatsApp -->
                                        <div v-if="param.clave.includes('whatsapp_plantilla')" class="mt-2 mb-3 p-3 bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-indigo-400 rounded-r">
                                            <div class="flex items-start">
                                                <svg class="w-5 h-5 text-indigo-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <div class="flex-1">
                                                    <p class="text-sm font-semibold text-indigo-900 mb-1">Puedes usar las siguientes variables:</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono bg-white border border-indigo-200 text-indigo-700">
                                                            [nombre_cliente]
                                                        </span>
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono bg-white border border-indigo-200 text-indigo-700">
                                                            [motivo]
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Campo de texto o textarea según el tipo -->
                                        <textarea
                                            v-if="param.clave.includes('plantilla')"
                                            :id="param.clave" 
                                            :name="param.clave"
                                            v-model="form[param.clave]" 
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm" 
                                            rows="5"
                                            placeholder="Escribe el mensaje de la plantilla aquí..."
                                        ></textarea>
                                        <TextInput 
                                            v-else
                                            :id="param.clave" 
                                            :name="param.clave"
                                            v-model="form[param.clave]" 
                                            class="mt-1 block w-full" 
                                            :type="typeof param.valor === 'number' ? 'number' : 'text'"
                                            :step="typeof param.valor === 'number' ? '0.01' : null"
                                        />
                                    </div>
                                    
                                    <InputError :message="form.errors[param.clave]" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end sticky bottom-6">
                        <div class="bg-white p-4 rounded-lg shadow-lg border border-gray-200">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Guardar Toda la Configuración
                            </PrimaryButton>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </AppLayout>
</template>