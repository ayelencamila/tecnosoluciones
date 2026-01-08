<script setup>
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: Object,
    provincias: Array,
    localidades: Array,
    esEdicion: { type: Boolean, default: false }
});

const provinciaSeleccionada = ref('');

const localidadesFiltradas = computed(() => {
    if (!provinciaSeleccionada.value) return props.localidades;
    return props.localidades.filter(loc => loc.provincia?.provinciaID == provinciaSeleccionada.value);
});
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
            <h3 class="text-md font-semibold text-indigo-700 mb-4">Datos de Identificación</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <InputLabel value="Razón Social *" />
                    <TextInput v-model="form.razon_social" class="w-full mt-1" autofocus />
                    <InputError :message="form.errors.razon_social" />
                </div>
                <div>
                    <InputLabel value="CUIT" />
                    <TextInput v-model="form.cuit" class="w-full mt-1" placeholder="Ej: 20123456789" maxlength="11" />
                    <InputError :message="form.errors.cuit" />
                </div>
                <div>
                    <InputLabel value="Email" />
                    <TextInput v-model="form.email" type="email" class="w-full mt-1" />
                    <InputError :message="form.errors.email" />
                </div>
                <div>
                    <InputLabel value="Teléfono" />
                    <TextInput v-model="form.telefono" class="w-full mt-1" />
                    <InputError :message="form.errors.telefono" />
                </div>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
            <h3 class="text-md font-semibold text-indigo-700 mb-4">Ubicación Física</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <InputLabel value="Calle *" />
                    <TextInput v-model="form.calle" class="w-full mt-1" />
                    <InputError :message="form.errors.calle" />
                </div>
                <div>
                    <InputLabel value="Altura" />
                    <TextInput v-model="form.altura" class="w-full mt-1" />
                    <InputError :message="form.errors.altura" />
                </div>
                <div class="md:col-span-3">
                    <InputLabel value="Provincia" />
                    <select v-model="provinciaSeleccionada" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todas las provincias</option>
                        <option v-for="prov in provincias" :key="prov.provinciaID" :value="prov.provinciaID">
                            {{ prov.nombre }}
                        </option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <InputLabel value="Localidad *" />
                    <select v-model="form.localidad_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Seleccione una localidad...</option>
                        <option v-for="loc in localidadesFiltradas" :key="loc.localidadID" :value="loc.localidadID">
                            {{ loc.nombre }} - {{ loc.provincia?.nombre }}
                        </option>
                    </select>
                    <InputError :message="form.errors.localidad_id" />
                </div>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
            <h3 class="text-md font-semibold text-indigo-700 mb-4">Condiciones Comerciales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <InputLabel value="Forma de Pago Habitual" />
                    <TextInput v-model="form.forma_pago_preferida" class="w-full mt-1" placeholder="Ej: Cheque 30 días" />
                    <InputError :message="form.errors.forma_pago_preferida" />
                </div>
                <div>
                    <InputLabel value="Plazo de Entrega (Días)" />
                    <TextInput v-model="form.plazo_entrega_estimado" type="number" class="w-full mt-1" />
                    <InputError :message="form.errors.plazo_entrega_estimado" />
                </div>
            </div>
        </div>

        <div v-if="esEdicion" class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
            <InputLabel value="Motivo de la Modificación (Requerido)" class="text-yellow-800 font-bold" />
            <p class="text-xs text-yellow-600 mb-2">Este cambio quedará registrado en la auditoría del sistema.</p>
            <TextInput v-model="form.motivo" class="w-full" placeholder="Ingrese el motivo del cambio..." />
            <InputError :message="form.errors.motivo" />
        </div>

        <div class="flex justify-end">
            <slot name="actions" />
        </div>
    </form>
</template>