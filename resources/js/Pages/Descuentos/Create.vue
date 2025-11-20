<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';

const form = useForm({
    codigo: '',
    descripcion: '',
    tipo: 'porcentaje', // Valor por defecto
    valor: '',
    valido_desde: new Date().toISOString().substr(0, 10), // Fecha de hoy
    valido_hasta: '',
    aplicabilidad: 'total', // Valor por defecto
    activo: true,
});

const submit = () => {
    form.post(route('descuentos.store'), {
        onFinish: () => form.reset('codigo', 'valor'),
    });
};
</script>

<template>
    <Head title="Crear Descuento" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Crear Nuevo Descuento (CU-08)
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-xl sm:rounded-lg p-6 border-t-4 border-indigo-500">
                    
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="md:col-span-1">
                                <InputLabel for="codigo" value="Código del Cupón *" />
                                <TextInput
                                    id="codigo"
                                    v-model="form.codigo"
                                    type="text"
                                    class="mt-1 block w-full uppercase"
                                    placeholder="EJ: VERANO2025"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.codigo" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="descripcion" value="Descripción *" />
                                <TextInput
                                    id="descripcion"
                                    v-model="form.descripcion"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Descuento especial de temporada"
                                    required
                                />
                                <InputError :message="form.errors.descripcion" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="tipo" value="Tipo de Descuento *" />
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
                                <InputLabel for="valor" value="Valor *" />
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
                                        placeholder="0.00"
                                        required
                                    />
                                </div>
                                <InputError :message="form.errors.valor" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <InputLabel for="valido_desde" value="Válido Desde *" />
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
                                <InputLabel for="valido_hasta" value="Válido Hasta (Opcional)" />
                                <TextInput
                                    id="valido_hasta"
                                    v-model="form.valido_hasta"
                                    type="date"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.valido_hasta" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="aplicabilidad" value="Aplicabilidad *" />
                                <SelectInput
                                    id="aplicabilidad"
                                    v-model="form.aplicabilidad"
                                    class="mt-1 block w-full"
                                    :options="[
                                        { value: 'total', label: 'Aplicar al Total de la Venta (Cupón Global)' },
                                        { value: 'item', label: 'Aplicar a Productos Específicos (Oferta)' },
                                        { value: 'ambos', label: 'Flexible (Ambos casos)' }
                                    ]"
                                />
                                <p class="text-xs text-gray-500 mt-1">Define si este descuento se usa en el carrito general o por ítem.</p>
                                <InputError :message="form.errors.aplicabilidad" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 border-t pt-4">
                            <Link :href="route('descuentos.index')" class="mr-4">
                                <SecondaryButton>Cancelar</SecondaryButton>
                            </Link>
                            
                            <PrimaryButton 
                                :class="{ 'opacity-25': form.processing }" 
                                :disabled="form.processing"
                            >
                                Guardar Descuento
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>