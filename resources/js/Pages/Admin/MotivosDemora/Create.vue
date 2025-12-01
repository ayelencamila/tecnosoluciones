<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const form = useForm({
    codigo: '',
    nombre: '',
    descripcion: '',
    requiere_bonificacion: false,
    pausa_sla: false,
    activo: true
});

const submit = () => {
    form.post(route('admin.motivos-demora.store'));
};
</script>

<template>
    <Head title="Nuevo Motivo de Demora" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo Motivo de Demora</h2>
                <Link :href="route('admin.motivos-demora.index')">
                    <SecondaryButton>
                        ← Volver al Listado
                    </SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            
                            <div class="mb-6">
                                <InputLabel for="codigo" value="Código *" />
                                <TextInput 
                                    id="codigo" 
                                    v-model="form.codigo" 
                                    type="text" 
                                    class="mt-1 block w-full" 
                                    placeholder="Ej: FALTA_REPUESTO"
                                    required
                                />
                                <InputError :message="form.errors.codigo" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">Código único identificador (máx. 50 caracteres, sin espacios)</p>
                            </div>

                            <div class="mb-6">
                                <InputLabel for="nombre" value="Nombre *" />
                                <TextInput 
                                    id="nombre" 
                                    v-model="form.nombre" 
                                    type="text" 
                                    class="mt-1 block w-full" 
                                    placeholder="Ej: Falta de repuesto"
                                    required
                                />
                                <InputError :message="form.errors.nombre" class="mt-2" />
                            </div>

                            <div class="mb-6">
                                <InputLabel for="descripcion" value="Descripción (opcional)" />
                                <textarea 
                                    id="descripcion" 
                                    v-model="form.descripcion"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    rows="3"
                                    placeholder="Descripción detallada del motivo de demora"
                                ></textarea>
                                <InputError :message="form.errors.descripcion" class="mt-2" />
                            </div>

                            <div class="mb-6 space-y-3">
                                <label class="flex items-center">
                                    <Checkbox name="requiere_bonificacion" v-model:checked="form.requiere_bonificacion" />
                                    <span class="ml-2 text-sm text-gray-700">Requiere bonificación al cliente</span>
                                </label>

                                <label class="flex items-center">
                                    <Checkbox name="pausa_sla" v-model:checked="form.pausa_sla" />
                                    <span class="ml-2 text-sm text-gray-700">Pausa el cómputo de SLA</span>
                                </label>

                                <label class="flex items-center">
                                    <Checkbox name="activo" v-model:checked="form.activo" />
                                    <span class="ml-2 text-sm text-gray-700">Motivo activo</span>
                                </label>
                            </div>

                            <div class="mt-8 flex justify-end space-x-3">
                                <Link :href="route('admin.motivos-demora.index')">
                                    <SecondaryButton type="button">
                                        Cancelar
                                    </SecondaryButton>
                                </Link>
                                <PrimaryButton 
                                    :class="{ 'opacity-25': form.processing }" 
                                    :disabled="form.processing"
                                >
                                    Guardar Motivo
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
