<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios';

const props = defineProps({
    cliente: Object,
    provincias: { type: Array, default: () => [] },
    tiposCliente: { type: Array, default: () => [] },
    estadosCliente: { type: Array, default: () => [] },
    estadosCuentaCorriente: { type: Array, default: () => [] },
});

// Inicializamos el form con los datos del cliente (CU-02 Paso 4)
const form = useForm({
    nombre: props.cliente.nombre,
    apellido: props.cliente.apellido,
    DNI: props.cliente.DNI,
    mail: props.cliente.mail || '',
    telefono: props.cliente.telefono || '',
    whatsapp: props.cliente.whatsapp || '',
    
    // Dirección
    calle: props.cliente.direccion?.calle || '',
    altura: props.cliente.direccion?.altura || '',
    pisoDepto: props.cliente.direccion?.pisoDepto || '',
    barrio: props.cliente.direccion?.barrio || '',
    codigoPostal: props.cliente.direccion?.codigoPostal || '',
    provincia_id: props.cliente.direccion?.localidad?.provinciaID || '',
    localidad_id: props.cliente.direccion?.localidadID || '',

    // Clasificación
    tipo_cliente_id: props.cliente.tipoClienteID,
    estado_cliente_id: props.cliente.estadoClienteID,

    // Cuenta Corriente (si existe)
    limiteCredito: props.cliente.cuenta_corriente?.limiteCredito || 0,
    diasGracia: props.cliente.cuenta_corriente?.diasGracia || 0,
    estado_cuenta_corriente_id: props.cliente.cuenta_corriente?.estadoCuentaCorrienteID || '',
});

// --- COMPUTADAS PARA SELECTS (Igual que en Create) ---
const provinciasOptions = computed(() => {
    return [{ value: '', label: 'Seleccione Provincia...' }, ...props.provincias.map(p => ({ value: p.provinciaID, label: p.nombre }))];
});

const tiposClienteOptions = computed(() => {
    return [{ value: '', label: 'Seleccione Tipo...' }, ...props.tiposCliente.map(t => ({ value: t.tipoClienteID, label: t.nombreTipo }))];
});

const estadosClienteOptions = computed(() => {
    return props.estadosCliente.map(e => ({ value: e.estadoClienteID, label: e.nombreEstado }));
});

// Lógica de Localidades
const localidades = ref([]);
const cargandoLocalidades = ref(false);

const localidadesOptions = computed(() => {
    if (cargandoLocalidades.value) return [{ value: '', label: 'Cargando...' }];
    const opts = localidades.value.map(l => ({ value: l.localidadID, label: l.nombre }));
    return [{ value: '', label: 'Seleccione Localidad...' }, ...opts];
});

// Detectar si es Mayorista para mostrar campos de CC
const esMayorista = computed(() => {
    const tipo = props.tiposCliente?.find(t => t.tipoClienteID == form.tipo_cliente_id);
    return tipo && tipo.nombreTipo.toLowerCase().includes('mayorista');
});

// Cargar localidades al montar o cambiar provincia
const cargarLocalidades = async (provinciaId) => {
    if (!provinciaId) return;
    cargandoLocalidades.value = true;
    try {
        const response = await axios.get(route('api.localidades.por-provincia', provinciaId));
        localidades.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        cargandoLocalidades.value = false;
    }
};

watch(() => form.provincia_id, (newVal, oldVal) => {
    // Si cambia la provincia y no es la carga inicial, limpiamos la localidad
    if (newVal !== oldVal && oldVal !== undefined) {
        form.localidad_id = '';
    }
    cargarLocalidades(newVal);
});

onMounted(() => {
    if (form.provincia_id) {
        cargarLocalidades(form.provincia_id);
    }
});

const submit = () => {
    // Usamos PUT para actualizar (CU-02 Paso 8)
    form.put(route('clientes.update', props.cliente.clienteID));
};
</script>

<template>
    <Head title="Modificar Cliente" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modificar Cliente: {{ cliente.nombre }} {{ cliente.apellido }}</h2>
                <Link :href="route('clientes.index')">
                    <SecondaryButton>Cancelar</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Datos Personales</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <InputLabel for="nombre">Nombre <span class="text-red-600">*</span></InputLabel>
                                <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.nombre" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="apellido">Apellido <span class="text-red-600">*</span></InputLabel>
                                <TextInput id="apellido" v-model="form.apellido" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.apellido" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="DNI">DNI <span class="text-red-600">*</span></InputLabel>
                                <TextInput id="DNI" v-model="form.DNI" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.DNI" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="mail" value="Email" />
                                <TextInput id="mail" v-model="form.mail" type="email" class="mt-1 block w-full" />
                                <InputError :message="form.errors.mail" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="whatsapp" value="WhatsApp" />
                                <TextInput id="whatsapp" v-model="form.whatsapp" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="telefono" value="Teléfono" />
                                <TextInput id="telefono" v-model="form.telefono" type="text" class="mt-1 block w-full" />
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Dirección</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="md:col-span-2">
                                <InputLabel for="calle">Calle <span class="text-red-600">*</span></InputLabel>
                                <TextInput id="calle" v-model="form.calle" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.calle" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="altura">Altura <span class="text-red-600">*</span></InputLabel>
                                <TextInput id="altura" v-model="form.altura" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.altura" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="provincia_id">Provincia <span class="text-red-600">*</span></InputLabel>
                                <SelectInput id="provincia_id" v-model="form.provincia_id" :options="provinciasOptions" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel for="localidad_id">Localidad <span class="text-red-600">*</span></InputLabel>
                                <SelectInput id="localidad_id" v-model="form.localidad_id" :options="localidadesOptions" class="mt-1 block w-full" required :disabled="!form.provincia_id" />
                            </div>
                            <div>
                                <InputLabel for="codigoPostal">C.P. <span class="text-red-600">*</span></InputLabel>
                                <TextInput id="codigoPostal" v-model="form.codigoPostal" type="text" class="mt-1 block w-full" required />
                            </div>
                             <div>
                                <InputLabel for="barrio" value="Barrio" />
                                <TextInput id="barrio" v-model="form.barrio" type="text" class="mt-1 block w-full" />
                            </div>
                             <div>
                                <InputLabel for="pisoDepto" value="Piso / Depto" />
                                <TextInput id="pisoDepto" v-model="form.pisoDepto" type="text" class="mt-1 block w-full" />
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Configuración Comercial</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <InputLabel for="tipo_cliente_id">Tipo de Cliente <span class="text-red-600">*</span></InputLabel>
                                <SelectInput id="tipo_cliente_id" v-model="form.tipo_cliente_id" :options="tiposClienteOptions" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel for="estado_cliente_id">Estado <span class="text-red-600">*</span></InputLabel>
                                <SelectInput id="estado_cliente_id" v-model="form.estado_cliente_id" :options="estadosClienteOptions" class="mt-1 block w-full" required />
                            </div>
                        </div>

                        <div v-if="esMayorista" class="bg-indigo-50 p-4 rounded-md border border-indigo-200 mb-6">
                            <h4 class="font-bold text-indigo-700 mb-2">Cuenta Corriente</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="limiteCredito" value="Límite de Crédito" />
                                    <TextInput id="limiteCredito" v-model="form.limiteCredito" type="number" step="0.01" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel for="diasGracia" value="Días de Gracia" />
                                    <TextInput id="diasGracia" v-model="form.diasGracia" type="number" class="mt-1 block w-full" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <PrimaryButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Guardar Cambios
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>