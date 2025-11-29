<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    descuento: Object,
    tipos: Array,            // <--- Recibido
    aplicabilidades: Array,  // <--- Recibido
});

const form = useForm({
    codigo: props.descuento.codigo,
    descripcion: props.descuento.descripcion,
    
    // IDs en lugar de texto
    tipo_descuento_id: props.descuento.tipo_descuento_id,
    aplicabilidad_descuento_id: props.descuento.aplicabilidad_descuento_id,
    
    valor: props.descuento.valor,
    valido_hasta: props.descuento.valido_hasta,
    activo: Boolean(props.descuento.activo),
});

const submit = () => {
    form.put(route('descuentos.update', props.descuento.descuento_id));
};

const desactivarDescuento = () => {
    if (confirm('¿Está seguro de desactivar este descuento?')) {
        form.delete(route('descuentos.destroy', props.descuento.descuento_id));
    }
};

const reactivarDescuento = () => {
    form.activo = true;
    submit();
};
</script>

<template>
    <Head title="Editar Descuento" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Editar Descuento: {{ descuento.codigo }}
                </h2>
                <span :class="descuento.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-3 py-1 rounded-full text-sm font-bold">
                    {{ descuento.activo ? 'ACTIVO' : 'INACTIVO' }}
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-xl sm:rounded-lg p-6 border-t-4 border-blue-500">
                    
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="md:col-span-1">
                                <InputLabel for="codigo" value="Código" />
                                <TextInput id="codigo" v-model="form.codigo" type="text" class="mt-1 block w-full uppercase bg-gray-50" required />
                                <InputError :message="form.errors.codigo" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="descripcion" value="Descripción" />
                                <TextInput id="descripcion" v-model="form.descripcion" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.descripcion" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="tipo" value="Tipo" />
                                <select id="tipo" v-model="form.tipo_descuento_id" class="w-full border-gray-300 rounded-md shadow-sm mt-1">
                                    <option v-for="t in tipos" :key="t.id" :value="t.id">{{ t.nombre }}</option>
                                </select>
                                <InputError :message="form.errors.tipo_descuento_id" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="aplicabilidad" value="Aplicabilidad" />
                                <select id="aplicabilidad" v-model="form.aplicabilidad_descuento_id" class="w-full border-gray-300 rounded-md shadow-sm mt-1">
                                    <option v-for="a in aplicabilidades" :key="a.id" :value="a.id">{{ a.nombre }}</option>
                                </select>
                                <InputError :message="form.errors.aplicabilidad_descuento_id" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="valor" value="Valor" />
                                <TextInput id="valor" v-model="form.valor" type="number" step="0.01" class="block w-full" required />
                                <InputError :message="form.errors.valor" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="valido_hasta" value="Válido Hasta" />
                                <TextInput id="valido_hasta" v-model="form.valido_hasta" type="date" class="mt-1 block w-full" />
                                <InputError :message="form.errors.valido_hasta" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-8 border-t pt-4">
                            
                            <div v-if="descuento.activo">
                                <DangerButton type="button" @click="desactivarDescuento">Desactivar</DangerButton>
                            </div>
                            <div v-else>
                                <SecondaryButton type="button" @click="reactivarDescuento" class="bg-green-50 text-green-700 border-green-200">Reactivar</SecondaryButton>
                            </div>

                            <div class="flex items-center gap-4">
                                <Link :href="route('descuentos.index')"><SecondaryButton>Cancelar</SecondaryButton></Link>
                                <PrimaryButton :disabled="form.processing">Actualizar</PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>