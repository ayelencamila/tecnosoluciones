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
    operacionesPendientes: Array,
    puedeSerDadoDeBaja: Boolean,
});

const form = useForm({
    motivo: '',
});

const submit = () => {
    if (!props.puedeSerDadoDeBaja) {
        return;
    }
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4" :class="puedeSerDadoDeBaja ? 'border-red-500' : 'border-yellow-500'">
                    
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ puedeSerDadoDeBaja ? '¿Está seguro que desea dar de baja a este cliente?' : 'No se puede dar de baja al cliente' }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-600">
                        El cliente <strong>{{ cliente.nombre }} {{ cliente.apellido }}</strong> 
                        <span v-if="puedeSerDadoDeBaja">pasará a estado Inactivo y se deshabilitará su cuenta corriente.</span>
                    </p>

                    <!-- CU-04 Excepción 4a: Operaciones Activas Pendientes -->
                    <div v-if="operacionesPendientes && operacionesPendientes.length > 0" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <h4 class="text-sm font-semibold text-yellow-800 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Operaciones Activas Pendientes
                        </h4>
                        <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside space-y-1">
                            <li v-for="(operacion, index) in operacionesPendientes" :key="index">{{ operacion }}</li>
                        </ul>
                        <p class="mt-3 text-xs text-yellow-800">
                            <strong>No es posible dar de baja al cliente {{ cliente.nombre }} {{ cliente.apellido }}</strong> porque tiene operaciones activas pendientes. Por favor, complete o cancele estas operaciones antes de continuar.
                        </p>
                    </div>

                    <form v-if="puedeSerDadoDeBaja" @submit.prevent="submit" class="mt-6">
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

                    <!-- Si no puede ser dado de baja, solo mostrar botón volver -->
                    <div v-else class="mt-6 flex justify-end">
                        <Link :href="route('clientes.show', cliente.clienteID)">
                            <SecondaryButton>Volver al Cliente</SecondaryButton>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>