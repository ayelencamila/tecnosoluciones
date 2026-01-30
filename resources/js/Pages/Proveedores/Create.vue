<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FormularioProveedor from './FormularioProveedor.vue';
import { useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref } from 'vue';

const props = defineProps({ 
    provincias: Array,
    localidades: Array 
});

const form = useForm({
    razon_social: '', cuit: '', email: '', telefono: '', whatsapp: '',
    forma_pago_preferida: '', plazo_entrega_estimado: '',
    calle: '', altura: '', provincia_id: '', localidad_id: '',
});

const submitting = ref(false);

const submit = () => {
    // Protección contra doble submit
    if (submitting.value || form.processing) return;
    
    submitting.value = true;
    form.post(route('proveedores.store'), {
        onFinish: () => {
            submitting.value = false;
        }
    });
};
</script>

<template>
    <AppLayout title="Registrar Proveedor">
        <template #header><h2 class="font-semibold text-xl text-gray-800">Registrar Nuevo Proveedor</h2></template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <FormularioProveedor :form="form" :provincias="provincias" :localidades="localidades" @submit="submit">
                        <template #actions>
                            <Link :href="route('proveedores.index')" class="mr-3"><SecondaryButton>Cancelar</SecondaryButton></Link>
                            <PrimaryButton type="submit" :disabled="form.processing || submitting">
                                <svg v-if="form.processing || submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing || submitting ? 'Guardando...' : 'Guardar Proveedor' }}
                            </PrimaryButton>
                        </template>
                    </FormularioProveedor>
                </div>
            </div>
        </div>
    </AppLayout>
</template>