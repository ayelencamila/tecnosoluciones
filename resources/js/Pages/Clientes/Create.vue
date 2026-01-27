<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import ConfigurableSelect from '@/Components/ConfigurableSelect.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios';

// Props que vienen del Controlador
const props = defineProps({
    provincias: { type: Array, default: () => [] },
    tiposCliente: { type: Array, default: () => [] },
    estadosCliente: { type: Array, default: () => [] },
});

// Estados reactivos para las listas configurables
const tiposClienteList = ref([...props.tiposCliente]);
const estadosClienteList = ref([...props.estadosCliente]);
const provinciasList = ref([...props.provincias]);
const localidadesList = ref([]);

// Definición del Formulario
const form = useForm({
    nombre: '',
    apellido: '',
    DNI: '',
    mail: '',
    telefono: '',
    whatsapp: '',
    calle: '',
    altura: '',
    pisoDepto: '',
    barrio: '',
    codigoPostal: '',
    provincia_id: '', // Se inicializa vacío
    localidad_id: '',
    tipo_cliente_id: '',
    estado_cliente_id: props.estadosCliente?.find(e => e.nombreEstado === 'Activo')?.estadoClienteID || '',
    limiteCredito: 0,
    diasGracia: 0,
});

// --- TRANSFORMACIÓN DE DATOS PARA SELECTINPUT (Corrección del Error) ---
// El componente SelectInput espera un array de objetos { value, label }

const provinciasOptions = computed(() => {
    return provinciasList.value.map(p => ({
        value: p.provinciaID,
        label: p.nombre
    }));
});

const tiposClienteOptions = computed(() => {
    return tiposClienteList.value.map(t => ({
        value: t.tipoClienteID,
        label: t.nombreTipo
    }));
});

const estadosClienteOptions = computed(() => {
    return estadosClienteList.value.map(e => ({
        value: e.estadoClienteID,
        label: e.nombreEstado
    }));
});

// Localidades (Dinámicas)
const localidades = ref([]);
const cargandoLocalidades = ref(false);

// Calculamos las opciones de localidades dinámicamente
const localidadesOptions = computed(() => {
    if (cargandoLocalidades.value) {
        return [{ value: '', label: 'Cargando...' }];
    }
    const opciones = localidadesList.value.map(l => ({
        value: l.localidadID,
        label: l.nombre
    }));
    if (opciones.length === 0 && form.provincia_id) {
        return [{ value: '', label: 'No hay localidades disponibles' }];
    }
    return opciones;
});

// Lógica Reactiva: Detectar si es Mayorista
const esMayorista = computed(() => {
    const tipo = props.tiposCliente?.find(t => t.tipoClienteID == form.tipo_cliente_id);
    return tipo && tipo.nombreTipo.toLowerCase().includes('mayorista');
});

// Watcher: Cargar localidades al cambiar provincia
watch(() => form.provincia_id, async (newProvinciaId) => {
    form.localidad_id = ''; // Resetear selección
    localidadesList.value = []; // Limpiar lista anterior
    
    if (newProvinciaId) {
        cargandoLocalidades.value = true;
        try {
            const response = await axios.get(route('api.localidades.por-provincia', newProvinciaId));
            localidadesList.value = response.data;
        } catch (error) {
            console.error('Error al cargar localidades:', error);
        } finally {
            cargandoLocalidades.value = false;
        }
    }
});

// Funciones de refresh para ConfigurableSelect
const refreshTiposCliente = async () => {
    try {
        const response = await axios.get('/api/tipos-cliente');
        tiposClienteList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar tipos de cliente:', error);
    }
};

const refreshEstadosCliente = async () => {
    try {
        const response = await axios.get('/api/estados-cliente');
        estadosClienteList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar estados de cliente:', error);
    }
};

const refreshProvincias = async () => {
    try {
        const response = await axios.get('/api/provincias');
        provinciasList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar provincias:', error);
    }
};

const refreshLocalidades = async () => {
    if (!form.provincia_id) return;
    
    cargandoLocalidades.value = true;
    try {
        const response = await axios.get(`/api/localidades?provincia_id=${form.provincia_id}`);
        localidadesList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar localidades:', error);
    } finally {
        cargandoLocalidades.value = false;
    }
};

const submit = () => {
    form.post(route('clientes.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Registrar Cliente" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Nuevo Cliente</h2>
                <Link :href="route('clientes.index')">
                    <SecondaryButton>Volver al Listado</SecondaryButton>
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
                                <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" required autofocus />
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
                                <InputLabel for="mail" value="Correo Electrónico" />
                                <TextInput id="mail" v-model="form.mail" type="email" class="mt-1 block w-full" />
                                <InputError :message="form.errors.mail" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="whatsapp" value="WhatsApp" />
                                <TextInput id="whatsapp" v-model="form.whatsapp" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.whatsapp" class="mt-2" />
                            </div>
                             <div>
                                <InputLabel for="telefono" value="Teléfono" />
                                <TextInput id="telefono" v-model="form.telefono" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.telefono" class="mt-2" />
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
                                <InputLabel for="altura">Altura/Número <span class="text-red-600">*</span></InputLabel>
                                <TextInput id="altura" v-model="form.altura" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.altura" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
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
                                    label="Localidad"
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

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <InputLabel for="codigoPostal">Código Postal <span class="text-red-600">*</span></InputLabel>
                                <TextInput id="codigoPostal" v-model="form.codigoPostal" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.codigoPostal" class="mt-2" />
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

                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Datos Comerciales</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <ConfigurableSelect
                                    id="tipo_cliente_id"
                                    v-model="form.tipo_cliente_id"
                                    label="Tipo de Cliente"
                                    :options="tiposClienteOptions"
                                    placeholder="Seleccione Tipo..."
                                    :error="form.errors.tipo_cliente_id"
                                    api-endpoint="/api/tipos-cliente"
                                    name-field="nombreTipo"
                                    @refresh="refreshTiposCliente"
                                />
                            </div>

                            <div>
                                <ConfigurableSelect
                                    id="estado_cliente_id"
                                    v-model="form.estado_cliente_id"
                                    label="Estado"
                                    :options="estadosClienteOptions"
                                    placeholder="Seleccione Estado..."
                                    :error="form.errors.estado_cliente_id"
                                    api-endpoint="/api/estados-cliente"
                                    name-field="nombreEstado"
                                    @refresh="refreshEstadosCliente"
                                />
                            </div>
                        </div>

                        <div v-if="esMayorista" class="bg-indigo-50 p-4 rounded-md border border-indigo-200 mb-6 animate-fade-in-down">
                            <h4 class="font-bold text-indigo-700 mb-2">Configuración de Cuenta Corriente</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="limiteCredito" value="Límite de Crédito ($)" />
                                    <TextInput id="limiteCredito" v-model="form.limiteCredito" type="number" step="0.01" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.limiteCredito" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="diasGracia" value="Días de Gracia" />
                                    <TextInput id="diasGracia" v-model="form.diasGracia" type="number" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.diasGracia" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <PrimaryButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Guardar Cliente
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>