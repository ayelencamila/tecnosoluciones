<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    parametros: Object,
});

const form = useForm({
    limite_credito_global: props.parametros.limite_credito_global || 0,
    dias_gracia_global: props.parametros.dias_gracia_global || 0,
    monto_minimo_notif: props.parametros.monto_minimo_notif || 0,
    bloqueo_auto: props.parametros.bloqueo_auto === '1' || props.parametros.bloqueo_auto === true,
});

const submit = () => {
    form.post(route('configuracion.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Configuración del Sistema" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Configuración Global</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    
                    <!-- TARJETA 1: CUENTA CORRIENTE -->
                    <div class="bg-white shadow sm:rounded-lg mb-6 overflow-hidden">
                        <div class="px-4 py-5 sm:px-6 bg-indigo-50 border-b border-indigo-100">
                            <h3 class="text-lg leading-6 font-medium text-indigo-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Parámetros de Cuenta Corriente
                            </h3>
                            <p class="mt-1 max-w-2xl text-sm text-indigo-500">Reglas por defecto para nuevos clientes mayoristas.</p>
                        </div>
                        <div class="px-4 py-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Límite Global -->
                            <div>
                                <InputLabel for="limite_credito_global" value="Límite de Crédito Global ($)" />
                                <TextInput id="limite_credito_global" v-model="form.limite_credito_global" type="number" step="0.01" class="mt-1 block w-full" />
                                <InputError :message="form.errors.limite_credito_global" class="mt-2" />
                            </div>

                            <!-- Días de Gracia -->
                            <div>
                                <InputLabel for="dias_gracia_global" value="Días de Gracia Global" />
                                <TextInput id="dias_gracia_global" v-model="form.dias_gracia_global" type="number" class="mt-1 block w-full" />
                                <InputError :message="form.errors.dias_gracia_global" class="mt-2" />
                            </div>

                            <!-- Monto Mínimo Notificación -->
                            <div>
                                <InputLabel for="monto_minimo_notif" value="Mínimo para Notificar Deuda ($)" />
                                <TextInput id="monto_minimo_notif" v-model="form.monto_minimo_notif" type="number" step="0.01" class="mt-1 block w-full" />
                                <p class="text-xs text-gray-500 mt-1">No se enviarán alertas por deudas menores a este monto.</p>
                            </div>

                            <!-- Bloqueo Automático -->
                            <div class="flex items-center mt-6">
                                <Checkbox id="bloqueo_auto" v-model:checked="form.bloqueo_auto" />
                                <div class="ml-2">
                                    <InputLabel for="bloqueo_auto" value="Bloqueo Automático por Mora" class="cursor-pointer" />
                                    <p class="text-xs text-gray-500">Bloquear venta a crédito si supera días de gracia.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BOTÓN GUARDAR -->
                    <div class="flex justify-end">
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="px-6 py-3 text-base">
                            Guardar Cambios
                        </PrimaryButton>
                    </div>

                </form>
            </div>
        </div>
    </AppLayout>
</template>