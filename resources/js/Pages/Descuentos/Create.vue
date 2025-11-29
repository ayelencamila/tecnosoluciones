<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    tipos: Array,            // <--- Recibido del controlador
    aplicabilidades: Array,  // <--- Recibido del controlador
});

const form = useForm({
    codigo: '',
    descripcion: '',
    tipo_descuento_id: '',          // <--- ID
    aplicabilidad_descuento_id: '', // <--- ID
    valor: '',
    valido_desde: new Date().toISOString().substr(0, 10),
    valido_hasta: '',
    activo: true,
});

const submit = () => {
    form.post(route('descuentos.store'));
};
</script>

<template>
    <Head title="Nuevo Descuento" />
    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-xl sm:rounded-lg p-6 border-t-4 border-indigo-500">
                    
                    <div class="mb-6 border-b pb-4">
                        <h2 class="text-lg font-bold text-gray-900">Crear Regla de Descuento</h2>
                        <p class="text-sm text-gray-500">Configura una nueva promoción o cupón.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="codigo" value="Código (Ej: VERANO)" />
                                <TextInput id="codigo" v-model="form.codigo" class="w-full mt-1 uppercase font-mono" placeholder="CODIGO" />
                                <InputError :message="form.errors.codigo" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="descripcion" value="Descripción (Interna)" />
                                <TextInput id="descripcion" v-model="form.descripcion" class="w-full mt-1" placeholder="Descuento por temporada..." />
                                <InputError :message="form.errors.descripcion" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-4 rounded border">
                            <div>
                                <InputLabel for="tipo" value="Tipo de Cálculo *" />
                                <select id="tipo" v-model="form.tipo_descuento_id" class="w-full border-gray-300 rounded-md shadow-sm mt-1 focus:ring-indigo-500">
                                    <option value="" disabled>Seleccione...</option>
                                    <option v-for="t in tipos" :key="t.id" :value="t.id">{{ t.nombre }}</option>
                                </select>
                                <InputError :message="form.errors.tipo_descuento_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="aplicabilidad" value="Aplicar a... *" />
                                <select id="aplicabilidad" v-model="form.aplicabilidad_descuento_id" class="w-full border-gray-300 rounded-md shadow-sm mt-1 focus:ring-indigo-500">
                                    <option value="" disabled>Seleccione...</option>
                                    <option v-for="a in aplicabilidades" :key="a.id" :value="a.id">{{ a.nombre }}</option>
                                </select>
                                <InputError :message="form.errors.aplicabilidad_descuento_id" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="valor" value="Valor (Monto o %)" />
                                <div class="relative mt-1">
                                    <TextInput id="valor" type="number" step="0.01" v-model="form.valor" class="w-full pl-4" />
                                </div>
                                <InputError :message="form.errors.valor" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="valido_hasta" value="Válido Hasta (Opcional)" />
                                <TextInput id="valido_hasta" type="date" v-model="form.valido_hasta" class="w-full mt-1" />
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t gap-3">
                            <Link :href="route('descuentos.index')"><SecondaryButton>Cancelar</SecondaryButton></Link>
                            <PrimaryButton :disabled="form.processing">Guardar Descuento</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>