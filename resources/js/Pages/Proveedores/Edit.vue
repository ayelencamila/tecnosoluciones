<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FormularioProveedor from './FormularioProveedor.vue';
import { useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({ 
    proveedor: Object, 
    provincias: Array,
    localidades: Array 
});

const form = useForm({
    razon_social: props.proveedor.razon_social || '',
    cuit: props.proveedor.cuit || '',
    email: props.proveedor.email || '',
    telefono: props.proveedor.telefono || '',
    forma_pago_preferida: props.proveedor.forma_pago_preferida || '',
    plazo_entrega_estimado: props.proveedor.plazo_entrega_estimado || '',
    
    // Datos de dirección
    calle: props.proveedor.direccion?.calle || '',
    altura: props.proveedor.direccion?.altura || '',
    provincia_id: props.proveedor.direccion?.localidad?.provinciaID || '',
    localidad_id: props.proveedor.direccion?.localidadID || '',

    motivo: '', // Campo obligatorio para el PUT
});

const submit = () => {
    form.put(route('proveedores.update', props.proveedor.id));
};
</script>

<template>
    <AppLayout title="Editar Proveedor">
        <template #header><h2 class="font-semibold text-xl text-gray-800">Editar Proveedor: {{ proveedor.razon_social }}</h2></template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <FormularioProveedor :form="form" :provincias="provincias" :localidades="localidades" :esEdicion="true" @submit="submit">
                        <template #actions>
                            <Link :href="route('proveedores.index')" class="mr-3"><SecondaryButton>Cancelar</SecondaryButton></Link>
                            <PrimaryButton type="submit" :disabled="form.processing">Actualizar Proveedor</PrimaryButton>
                        </template>
                    </FormularioProveedor>
                </div>
            </div>
        </div>
    </AppLayout>
</template>