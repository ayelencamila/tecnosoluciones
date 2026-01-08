<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FormularioProveedor from './FormularioProveedor.vue';
import { useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({ 
    provincias: Array,
    localidades: Array 
});

const form = useForm({
    razon_social: '', cuit: '', email: '', telefono: '',
    forma_pago_preferida: '', plazo_entrega_estimado: '',
    calle: '', altura: '', localidad_id: '',
});

const submit = () => {
    form.post(route('proveedores.store'));
};
</script>

<template>
    <AppLayout title="Registrar Proveedor">
        <template #header><h2 class="font-semibold text-xl text-gray-800">Registrar Nuevo Proveedor</h2></template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <FormularioProveedor :form="form" :provincias="provincias" :localidades="localidades">
                        <template #actions>
                            <Link :href="route('proveedores.index')" class="mr-3"><SecondaryButton>Cancelar</SecondaryButton></Link>
                            <PrimaryButton :disabled="form.processing">Guardar Proveedor</PrimaryButton>
                        </template>
                    </FormularioProveedor>
                </div>
            </div>
        </div>
    </AppLayout>
</template>