<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    cliente: Object,
});

const form = useForm({
    motivo: '',
});

const submit = () => {
    form.post(route('clientes.darDeBaja', props.cliente.clienteID));
};
</script>

<template>
    <Head title="Confirmar Baja" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-red-800 leading-tight">
                Dar de Baja Cliente
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-md mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-red-500">
                    
                    <h3 class="text-lg font-medium text-gray-900">¿Está seguro que desea dar de baja a este cliente?</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        El cliente <strong>{{ cliente.nombre }} {{ cliente.apellido }}</strong> pasará a estado Inactivo y se deshabilitará su cuenta corriente.
                    </p>

                    <form @submit.prevent="submit" class="mt-6">
                        <div>
                            <InputLabel for="motivo">Motivo de la baja <span class="text-red-600">*</span></InputLabel>
                            <TextInput 
                                id="motivo" 
                                v-model="form.motivo" 
                                type="text" 
                                class="mt-1 block w-full" 
                                placeholder="Ej: Cliente solicitó cierre de cuenta..."
                                required 
                                autofocus
                            />
                            <InputError :message="form.errors.motivo" class="mt-2" />
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <Link :href="route('clientes.show', cliente.clienteID)">
                                <SecondaryButton>Cancelar</SecondaryButton>
                            </Link>
                            
                            <DangerButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Confirmar Baja
                            </DangerButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>