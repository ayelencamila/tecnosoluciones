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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Configuración Global del Sistema
            </h2>
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
                                            <Checkbox :id="param.clave" v-model:checked="form[param.clave]" />
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label :for="param.clave" class="font-medium text-gray-700 cursor-pointer">
                                                {{ formatLabel(param.clave, param.descripcion) }}
                                            </label>
                                            <p class="text-gray-500 text-xs">Habilitar o deshabilitar esta función.</p>
                                        </div>
                                    </div>

                                    <div v-else>
                                        <InputLabel :for="param.clave" :value="param.descripcion || param.clave" />
                                        <TextInput 
                                            :id="param.clave" 
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