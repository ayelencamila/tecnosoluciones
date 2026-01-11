<script setup>
import { ref, computed, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import ConfigurableSelect from '@/Components/ConfigurableSelect.vue';
import axios from 'axios';

const props = defineProps({
    form: Object,
    provincias: Array,
    localidades: Array,
    esEdicion: { type: Boolean, default: false }
});

// Estados reactivos para listas configurables
const provinciasList = ref([...props.provincias]);
const localidadesList = ref([]);
const cargandoLocalidades = ref(false);

// Computed para opciones de selects
const provinciasOptions = computed(() => {
    return provinciasList.value.map(p => ({
        value: p.provinciaID,
        label: p.nombre
    }));
});

const localidadesOptions = computed(() => {
    if (cargandoLocalidades.value) {
        return [{ value: '', label: 'Cargando...' }];
    }
    const opciones = localidadesList.value.map(l => ({
        value: l.localidadID,
        label: l.nombre
    }));
    if (opciones.length === 0 && props.form.provincia_id) {
        return [{ value: '', label: 'No hay localidades disponibles' }];
    }
    return opciones;
});

// Watcher para cargar localidades al cambiar provincia
watch(() => props.form.provincia_id, async (newProvinciaId) => {
    props.form.localidad_id = '';
    localidadesList.value = [];
    
    if (newProvinciaId) {
        cargandoLocalidades.value = true;
        try {
            const response = await axios.get(`/api/localidades?provincia_id=${newProvinciaId}`);
            localidadesList.value = response.data;
        } catch (error) {
            console.error('Error al cargar localidades:', error);
        } finally {
            cargandoLocalidades.value = false;
        }
    }
});

// Funciones refresh para ConfigurableSelect
const refreshProvincias = async () => {
    try {
        const response = await axios.get('/api/provincias');
        provinciasList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar provincias:', error);
    }
};

const refreshLocalidades = async () => {
    if (!props.form.provincia_id) return;
    
    cargandoLocalidades.value = true;
    try {
        const response = await axios.get(`/api/localidades?provincia_id=${props.form.provincia_id}`);
        localidadesList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar localidades:', error);
    } finally {
        cargandoLocalidades.value = false;
    }
};
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
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <ConfigurableSelect
                        id="provincia_id"
                        v-model="form.provincia_id"
                        label="Provincia"
                        :options="provinciasOptions"
                        placeholder="Seleccione Provincia..."
                        :error="form.errors.provincia_id"
                        api-endpoint="/api/provincias"
                        name-field="nombre"
                        @refresh="refreshProvincias"
                    />
                </div>
                <div>
                    <ConfigurableSelect
                        id="localidad_id"
                        v-model="form.localidad_id"
                        label="Localidad *"
                        :options="localidadesOptions"
                        placeholder="Seleccione Localidad..."
                        :error="form.errors.localidad_id"
                        :disabled="!form.provincia_id || cargandoLocalidades"
                        :loading="cargandoLocalidades"
                        api-endpoint="/api/localidades"
                        name-field="nombre"
                        :additional-data="{ provincia_id: form.provincia_id }"
                        @refresh="refreshLocalidades"
                    />
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