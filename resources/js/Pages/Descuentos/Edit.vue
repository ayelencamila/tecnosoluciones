<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    descuento: Object,
});

const form = useForm({
    codigo: props.descuento.codigo,
    descripcion: props.descuento.descripcion,
    tipo: props.descuento.tipo,
    valor: props.descuento.valor,
    valido_desde: props.descuento.valido_desde,
    valido_hasta: props.descuento.valido_hasta,
    aplicabilidad: props.descuento.aplicabilidad,
    activo: Boolean(props.descuento.activo),
});

const submit = () => {
    form.put(route('descuentos.update', props.descuento.descuento_id));
};

// Lógica para desactivar (Baja lógica - Paso 4)
const desactivarDescuento = () => {
    if (confirm('¿Está seguro de desactivar este descuento? Quedará inutilizable para nuevas ventas.')) {
        // Usamos DELETE semántico, aunque el controlador hace un update(activo=false)
        form.delete(route('descuentos.destroy', props.descuento.descuento_id));
    }
};

// Lógica para reactivar (Opcional pero útil)
const reactivarDescuento = () => {
    form.activo = true;
    submit(); // Simplemente guardamos con activo=true
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
                                <TextInput
                                    id="codigo"
                                    v-model="form.codigo"
                                    type="text"
                                    class="mt-1 block w-full uppercase bg-gray-50"
                                    required
                                />
                                <InputError :message="form.errors.codigo" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="descripcion" value="Descripción" />
                                <TextInput
                                    id="descripcion"
                                    v-model="form.descripcion"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.descripcion" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="tipo" value="Tipo" />
                                <SelectInput
                                    id="tipo"
                                    v-model="form.tipo"
                                    class="mt-1 block w-full"
                                    :options="[
                                        { value: 'porcentaje', label: 'Porcentaje (%)' },
                                        { value: 'monto_fijo', label: 'Monto Fijo ($)' }
                                    ]"
                                />
                                <InputError :message="form.errors.tipo" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="valor" value="Valor" />
                                <div class="relative mt-1 rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">
                                            {{ form.tipo === 'monto_fijo' ? '$' : '%' }}
                                        </span>
                                    </div>
                                    <TextInput
                                        id="valor"
                                        v-model="form.valor"
                                        type="number"
                                        step="0.01"
                                        class="block w-full pl-7"
                                        required
                                    />
                                </div>
                                <InputError :message="form.errors.valor" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="valido_desde" value="Válido Desde" />
                                <TextInput
                                    id="valido_desde"
                                    v-model="form.valido_desde"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.valido_desde" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="valido_hasta" value="Válido Hasta" />
                                <TextInput
                                    id="valido_hasta"
                                    v-model="form.valido_hasta"
                                    type="date"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.valido_hasta" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="aplicabilidad" value="Aplicabilidad" />
                                <SelectInput
                                    id="aplicabilidad"
                                    v-model="form.aplicabilidad"
                                    class="mt-1 block w-full"
                                    :options="[
                                        { value: 'total', label: 'Total (Global)' },
                                        { value: 'item', label: 'Por Ítem' },
                                        { value: 'ambos', label: 'Ambos' }
                                    ]"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-8 border-t pt-4">
                            
                            <div v-if="descuento.activo">
                                <DangerButton type="button" @click="desactivarDescuento">
                                    Desactivar Descuento
                                </DangerButton>
                            </div>
                            <div v-else>
                                <SecondaryButton type="button" @click="reactivarDescuento" class="bg-green-50 text-green-700 border-green-200 hover:bg-green-100">
                                    Reactivar
                                </SecondaryButton>
                            </div>

                            <div class="flex items-center">
                                <Link :href="route('descuentos.index')" class="mr-4">
                                    <SecondaryButton>Cancelar</SecondaryButton>
                                </Link>
                                
                                <PrimaryButton 
                                    :class="{ 'opacity-25': form.processing }" 
                                    :disabled="form.processing"
                                >
                                    Actualizar
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>