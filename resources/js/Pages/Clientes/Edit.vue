<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue'; 
import InputError from '@/Components/InputError.vue'; 

const props = defineProps({
    cliente: Object, // Cliente existente
    provincias: Array,
    tiposCliente: Array,
    estadosCliente: Array,
    estadosCuentaCorriente: Array,
    // errors es inyectado por useForm
});

// --- ESTADOS REACTIVOS ---
const localidades = ref([]);
const loadingLocalidades = ref(false);

// 1. Formulario reactivo de Inertia: Inicializado con los datos de props.cliente
const form = useForm({
    nombre: props.cliente.nombre || '',
    apellido: props.cliente.apellido || '',
    DNI: props.cliente.DNI || '',
    mail: props.cliente.mail || '',
    telefono: props.cliente.telefono || '',
    whatsapp: props.cliente.whatsapp || '',
    
    // Dirección (usando encadenamiento opcional si la relación no existe)
    calle: props.cliente.direccion?.calle || '',
    altura: props.cliente.direccion?.altura || '',
    pisoDepto: props.cliente.direccion?.pisoDepto || '',
    barrio: props.cliente.direccion?.barrio || '',
    codigoPostal: props.cliente.direccion?.codigoPostal || '',
    provincia_id: props.cliente.direccion?.localidad?.provinciaID || '',
    localidad_id: props.cliente.direccion?.localidadID || '', 

    // Clasificación
    tipo_cliente_id: props.cliente.tipoClienteID || '',
    estado_cliente_id: props.cliente.estadoClienteID || '',

    // Cuenta Corriente
    limiteCredito: props.cliente.cuenta_corriente?.limiteCredito || '',
    diasGracia: props.cliente.cuenta_corriente?.diasGracia || '',
    estado_cuenta_corriente_id: props.cliente.cuenta_corriente?.estadoCuentaCorrienteID || '',
});

// --- COMPATIBILIDAD KENDALL (Formato para tu SelectInput) ---
const provinciasOptions = computed(() => [
    { value: '', label: 'Seleccione una provincia' },
    ...props.provincias.map(p => ({ value: p.provinciaID, label: p.nombre }))
]);
const localidadesOptions = computed(() => [
    { value: '', label: loadingLocalidades.value ? 'Cargando...' : 'Seleccione una localidad' },
    ...localidades.value.map(l => ({ value: l.localidadID, label: l.nombre }))
]);
const tiposClienteOptions = computed(() => [
    { value: '', label: 'Seleccione un tipo' },
    ...props.tiposCliente.map(t => ({ value: t.tipoClienteID, label: t.nombreTipo }))
]);
const estadosClienteOptions = computed(() => [
    { value: '', label: 'Seleccione un estado' },
    ...props.estadosCliente.map(e => ({ value: e.estadoClienteID, label: e.nombreEstado }))
]);
const estadosCCOptions = computed(() => [
    { value: '', label: 'Seleccione un estado' },
    ...props.estadosCuentaCorriente.map(e => ({ value: e.estadoCuentaCorrienteID, label: e.nombreEstado }))
]);

// Computed para lógica de formulario (CU-02)
const esMayorista = computed(() => {
    const tipoSeleccionado = props.tiposCliente.find(t => t.tipoClienteID == form.tipo_cliente_id);
    return tipoSeleccionado?.nombreTipo?.toLowerCase() === 'mayorista';
});
const cuentaCorrienteExistente = computed(() => props.cliente.cuentaCorrienteID); // Reusa el prop

// Methods
const onProvinciaChange = async () => {
    form.localidad_id = '';
    localidades.value = [];
    if (!form.provincia_id) return;
    
    loadingLocalidades.value = true;
    try {
        const response = await fetch(`/api/provincias/${form.provincia_id}/localidades`);
        localidades.value = await response.json();
    } catch (error) {
        console.error('Error al cargar localidades:', error);
    } finally {
        loadingLocalidades.value = false;
    }
};

watch(() => form.tipo_cliente_id, (newVal) => {
    // Si pasa a Minorista (CU-02), limpiamos la info de CC
    if (!esMayorista.value) {
        form.limiteCredito = '';
        form.diasGracia = '';
        form.estado_cuenta_corriente_id = '';
    }
});

const submitForm = () => {
    // Usamos POST con el método PUT simulado para llamar a ClienteController@update
    form.post(route('clientes.update', props.cliente.clienteID), {
        preserveScroll: true,
        _method: 'put',
        onError: () => {
            // Los <InputError> se actualizan solos
        }
    });
};

// Cargar localidades de la provincia inicial (para que aparezca la localidad precargada)
onMounted(async () => {
    if (form.provincia_id) {
        await onProvinciaChange();
    }
});
</script>

<template>
    <Head title="Modificar Cliente" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-3xl font-bold text-gray-900">Modificar Cliente</h1>
                <Link :href="route('clientes.show', cliente.clienteID)" class="flex items-center text-gray-600 hover:text-gray-900">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    Ver Detalles
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Formulario -->
                <form @submit.prevent="submitForm" class="space-y-6">
                    
                    <!-- Datos Personales -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-xl font-semibold">Datos Personales</h2></div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="nombre" value="Nombre *" />
                                    <TextInput id="nombre" v-model="form.nombre" type="text" required class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.nombre }" />
                                    <InputError :message="form.errors.nombre" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="apellido" value="Apellido *" />
                                    <TextInput id="apellido" v-model="form.apellido" type="text" required class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.apellido }" />
                                    <InputError :message="form.errors.apellido" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="DNI" value="DNI *" />
                                    <TextInput id="DNI" v-model="form.DNI" type="text" required class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.DNI }" />
                                    <InputError :message="form.errors.DNI" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="mail" value="Email" />
                                    <TextInput id="mail" v-model="form.mail" type="email" class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.mail }" />
                                    <InputError :message="form.errors.mail" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="telefono" value="Teléfono" />
                                    <TextInput id="telefono" v-model="form.telefono" type="text" class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.telefono }" />
                                    <InputError :message="form.errors.telefono" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="whatsapp" value="WhatsApp" />
                                    <TextInput id="whatsapp" v-model="form.whatsapp" type="text" class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.whatsapp }" />
                                    <InputError :message="form.errors.whatsapp" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-xl font-semibold">Dirección</h2></div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="md:col-span-2">
                                    <InputLabel for="calle" value="Calle *" />
                                    <TextInput id="calle" v-model="form.calle" type="text" required class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.calle }" />
                                    <InputError :message="form.errors.calle" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="altura" value="Altura *" />
                                    <TextInput id="altura" v-model="form.altura" type="text" required class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.altura }" />
                                    <InputError :message="form.errors.altura" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="pisoDepto" value="Piso/Departamento" />
                                    <TextInput id="pisoDepto" v-model="form.pisoDepto" type="text" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.pisoDepto" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="barrio" value="Barrio" />
                                    <TextInput id="barrio" v-model="form.barrio" type="text" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.barrio" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="codigoPostal" value="Código Postal *" />
                                    <TextInput id="codigoPostal" v-model="form.codigoPostal" type="text" required class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.codigoPostal }" />
                                    <InputError :message="form.errors.codigoPostal" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="provincia" value="Provincia *" />
                                    <SelectInput id="provincia" v-model="form.provincia_id" @change="onProvinciaChange" required class="mt-1 block w-full" :options="provinciasOptions" :class="{ 'border-red-500': form.errors.provincia_id }" />
                                    <InputError :message="form.errors.provincia_id" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="localidad" value="Localidad *" />
                                    <SelectInput id="localidad" v-model="form.localidad_id" required :disabled="!form.provincia_id || loadingLocalidades" class="mt-1 block w-full" :options="localidadesOptions" :class="{ 'border-red-500': form.errors.localidad_id }" />
                                    <InputError :message="form.errors.localidad_id" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración Comercial -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-xl font-semibold">Configuración Comercial</h2></div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="tipo_cliente" value="Tipo de Cliente *" />
                                    <SelectInput id="tipo_cliente" v-model="form.tipo_cliente_id" @change="onTipoClienteChange" required class="mt-1 block w-full" :options="tiposClienteOptions" :class="{ 'border-red-500': form.errors.tipo_cliente_id }" />
                                    <InputError :message="form.errors.tipo_cliente_id" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="estado_cliente" value="Estado *" />
                                    <SelectInput id="estado_cliente" v-model="form.estado_cliente_id" required class="mt-1 block w-full" :options="estadosClienteOptions" :class="{ 'border-red-500': form.errors.estado_cliente_id }" />
                                    <InputError :message="form.errors.estado_cliente_id" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración Cuenta Corriente (CU-01 Paso 6) -->
                    <div v-if="esMayorista" class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-xl font-semibold">Configuración de Cuenta Corriente</h2></div>
                        <div class="p-6">
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6"><p class="text-sm text-blue-800">Configure los términos de la cuenta corriente para este cliente mayorista.</p></div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <InputLabel for="limiteCredito" value="Límite de Crédito" />
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-gray-500">$</span>
                                        <TextInput id="limiteCredito" v-model="form.limiteCredito" type="number" min="0" step="0.01" class="mt-1 block w-full pl-8" :class="{ 'border-red-500': form.errors.limiteCredito }" />
                                    </div>
                                    <InputError :message="form.errors.limiteCredito" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="diasGracia" value="Días de Gracia" />
                                    <TextInput id="diasGracia" v-model="form.diasGracia" type="number" min="0" class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.diasGracia }" />
                                    <InputError :message="form.errors.diasGracia" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="estado_cc" value="Estado de Cuenta" />
                                    <SelectInput id="estado_cc" v-model="form.estado_cuenta_corriente_id" class="mt-1 block w-full" :options="estadosCCOptions" :class="{ 'border-red-500': form.errors.estado_cuenta_corriente_id }" />
                                    <InputError :message="form.errors.estado_cuenta_corriente_id" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex justify-end space-x-4 pt-6">
                        <Link :href="route('clientes.index')" class="px-6 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </Link>
                        <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ form.processing ? 'Registrando...' : 'Registrar Cliente' }}</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>