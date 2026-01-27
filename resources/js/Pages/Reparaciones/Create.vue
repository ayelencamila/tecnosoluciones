<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AlertMessage from '@/Components/AlertMessage.vue'; 
import ConfigurableSelect from '@/Components/ConfigurableSelect.vue';
import axios from 'axios'; 
import { debounce } from 'lodash';

const props = defineProps({
    productos: Array, 
    marcas: Array,
    tecnicos: Array, // CU-11 Paso 5: Lista de técnicos disponibles
    errors: Object 
});

// --- Listas reactivas para ConfigurableSelect ---
const marcasList = ref([...props.marcas || []]);

const form = useForm({
    clienteID: '',
    tecnico_id: '', // CU-11 Paso 5: Técnico asignado (obligatorio)
    marca_id: '',
    modelo_id: '',
    numero_serie_imei: '',
    clave_bloqueo: '',
    accesorios_dejados: '',
    falla_declarada: '',
    observaciones: '',
    fecha_promesa: '',
    imagenes: [],
});

// --- Computed para opciones de select ---
const marcasOptions = computed(() => {
    return marcasList.value.map(m => ({
        value: m.id,
        label: m.nombre
    }));
});

const modelosOptions = computed(() => {
    return modelosDisponibles.value.map(m => ({
        value: m.id,
        label: m.nombre
    }));
});

// --- LÓGICA MARCAS Y MODELOS (Cascada) ---
const modelosDisponibles = ref([]);
const cargandoModelos = ref(false);

// Refresh para marcas
const refreshMarcas = async () => {
    try {
        const response = await axios.get('/api/marcas');
        marcasList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar marcas:', error);
    }
};

// Refresh para modelos (depende de marca seleccionada)
const refreshModelos = async () => {
    if (form.marca_id) {
        try {
            const response = await axios.get(`/api/modelos?marca_id=${form.marca_id}`);
            modelosDisponibles.value = response.data;
        } catch (error) {
            console.error('Error al refrescar modelos:', error);
        }
    }
};

watch(() => form.marca_id, async (nuevaMarcaId) => {
    form.modelo_id = ''; 
    modelosDisponibles.value = [];
    
    if (nuevaMarcaId) {
        cargandoModelos.value = true;
        try {
            const response = await axios.get(route('api.modelos.por-marca', nuevaMarcaId));
            modelosDisponibles.value = response.data;
        } catch (error) {
            console.error("Error cargando modelos:", error);
        } finally {
            cargandoModelos.value = false;
        }
    }
});

// --- BUSCADOR DE CLIENTES ---
const busquedaCliente = ref('');
const resultadosClientes = ref([]);
const clienteSeleccionado = ref(null);
const buscando = ref(false);

const buscarClientes = debounce(async () => {
    if (busquedaCliente.value.length < 2) {
        resultadosClientes.value = [];
        return;
    }
    buscando.value = true;
    try {
        const response = await axios.get(route('api.clientes.buscar'), { params: { q: busquedaCliente.value } });
        resultadosClientes.value = response.data;
    } catch (error) {
        console.error("Error buscando clientes:", error);
    } finally {
        buscando.value = false;
    }
}, 300);

const seleccionarCliente = (cliente) => {
    form.clienteID = cliente.clienteID;
    clienteSeleccionado.value = cliente;
    busquedaCliente.value = '';
    resultadosClientes.value = [];
};

const quitarCliente = () => {
    form.clienteID = '';
    clienteSeleccionado.value = null;
};

// --- IMÁGENES ---
const imagenPreviews = ref([]);
const errorImagen = ref(''); 

const handleImageUpload = (event) => {
    const files = Array.from(event.target.files);
    errorImagen.value = '';

    if (form.imagenes.length + files.length > 5) {
        errorImagen.value = 'Solo puedes subir un máximo de 5 fotos por reparación.';
        return;
    }
    
    files.forEach(file => {
        // Validación básica de tamaño (5MB)
        if (file.size > 5 * 1024 * 1024) {
            errorImagen.value = `El archivo ${file.name} supera los 5MB permitidos.`;
            return;
        }

        form.imagenes.push(file);
        const reader = new FileReader();
        reader.onload = (e) => imagenPreviews.value.push({ url: e.target.result, name: file.name });
        reader.readAsDataURL(file);
    });
};

const removeImage = (index) => {
    form.imagenes.splice(index, 1);
    imagenPreviews.value.splice(index, 1);
    errorImagen.value = '';
};

const submit = () => {
    form.post(route('reparaciones.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            imagenPreviews.value = [];
            clienteSeleccionado.value = null;
        },
    });
};
</script>

<template>
    <Head title="Registrar Reparación" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva Reparación</h2>
                <Link :href="route('reparaciones.index')">
                    <SecondaryButton>Volver al Listado</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <AlertMessage 
                    v-if="Object.keys(form.errors).length > 0" 
                    type="error"
                    :message="'Por favor revisa los errores indicados abajo.'"
                    class="mb-4"
                />

                <div v-if="Object.keys(form.errors).length > 0" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <strong class="font-bold">Detalles del error:</strong>
                    <ul class="list-disc ml-5 mt-2">
                        <li v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 sm:p-8">
                        
                        <div class="mb-8 border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Cliente
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="relative">
                                    <InputLabel for="buscador_cliente" value="Buscar Cliente (Nombre, Apellido o DNI) *" />
                                    
                                    <div v-if="!clienteSeleccionado">
                                        <div class="relative">
                                            <input 
                                                id="buscador_cliente"
                                                name="buscador_cliente"
                                                type="text" 
                                                v-model="busquedaCliente" 
                                                @input="buscarClientes" 
                                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pl-10" 
                                                placeholder="Escribe para buscar..." 
                                                autocomplete="off" 
                                            />
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg v-if="!buscando" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                <svg v-else class="animate-spin h-5 w-5 text-indigo-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </div>
                                        </div>

                                        <div v-if="resultadosClientes.length > 0" class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                            <ul tabindex="-1" role="listbox">
                                                <li v-for="cliente in resultadosClientes" :key="cliente.clienteID" @click="seleccionarCliente(cliente)" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50 text-gray-900 border-b border-gray-100">
                                                    <div class="flex items-center">
                                                        <span class="font-bold block truncate text-indigo-700">{{ cliente.apellido }}, {{ cliente.nombre }}</span>
                                                        <span class="ml-2 text-gray-500 text-xs">(DNI: {{ cliente.dni }})</span>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                        
                                        <div class="mt-2 text-right">
                                            <Link :href="route('clientes.create')" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold hover:underline">
                                                + Registrar Nuevo Cliente
                                            </Link>
                                        </div>
                                    </div>

                                    <div v-else class="mt-1 p-4 border border-green-200 bg-green-50 rounded-lg flex justify-between items-center shadow-sm">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-bold mr-3 border border-green-300">
                                                {{ clienteSeleccionado.nombre.charAt(0) }}{{ clienteSeleccionado.apellido.charAt(0) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">{{ clienteSeleccionado.apellido }}, {{ clienteSeleccionado.nombre }}</p>
                                                <p class="text-xs text-gray-500">DNI: {{ clienteSeleccionado.dni }}</p>
                                            </div>
                                        </div>
                                        <button type="button" @click="quitarCliente" class="text-red-500 hover:text-red-700 text-xs font-bold uppercase tracking-wider border border-red-200 px-3 py-1 rounded bg-white hover:bg-red-50 transition">
                                            Cambiar
                                        </button>
                                    </div>
                                    <InputError :message="form.errors.clienteID" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="mb-8 border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                Datos del Equipo
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                
                                <div>
                                    <ConfigurableSelect
                                        id="marca_id"
                                        v-model="form.marca_id"
                                        label="Marca *"
                                        :options="marcasOptions"
                                        placeholder="Selecciona una marca"
                                        :error="form.errors.marca_id"
                                        api-endpoint="/api/marcas"
                                        name-field="nombre"
                                        @refresh="refreshMarcas"
                                    />
                                </div>

                                <div>
                                    <ConfigurableSelect
                                        id="modelo_id"
                                        v-model="form.modelo_id"
                                        label="Modelo *"
                                        :options="modelosOptions"
                                        :placeholder="!form.marca_id ? 'Primero selecciona Marca' : (cargandoModelos ? 'Cargando modelos...' : 'Selecciona un modelo')"
                                        :error="form.errors.modelo_id"
                                        :disabled="!form.marca_id || cargandoModelos"
                                        :loading="cargandoModelos"
                                        api-endpoint="/api/modelos"
                                        name-field="nombre"
                                        :additional-data="{ marca_id: form.marca_id }"
                                        @refresh="refreshModelos"
                                    />
                                </div>

                                <!-- CU-11 Paso 5: Asignación de Técnico (Kendall: Variedad de controles - Select) -->
                                <div>
                                    <InputLabel for="tecnico_id" value="Técnico Asignado *" />
                                    <select 
                                        id="tecnico_id" 
                                        name="tecnico_id"
                                        v-model="form.tecnico_id" 
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="" disabled>Selecciona un técnico</option>
                                        <option v-for="tecnico in tecnicos" :key="tecnico.id" :value="tecnico.id">{{ tecnico.name }}</option>
                                    </select>
                                    <InputError :message="form.errors.tecnico_id" class="mt-2" />
                                    <p class="mt-1 text-xs text-gray-500">Técnico responsable del diagnóstico y reparación</p>
                                </div>

                                <div><InputLabel for="numero_serie_imei" value="Nro. Serie / IMEI" /><TextInput id="numero_serie_imei" name="imei" v-model="form.numero_serie_imei" type="text" class="mt-1 block w-full" /></div>
                                <div><InputLabel for="clave_bloqueo" value="Clave / Patrón" /><TextInput id="clave_bloqueo" name="clave" v-model="form.clave_bloqueo" type="text" class="mt-1 block w-full" /></div>
                                <div class="md:col-span-2"><InputLabel for="accesorios_dejados" value="Accesorios (Funda, Cargador, etc.)" /><TextInput id="accesorios_dejados" name="accesorios" v-model="form.accesorios_dejados" type="text" class="mt-1 block w-full" /></div>
                            </div>
                        </div>

                        <div class="mb-8 border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 00-2-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                Detalle del Problema
                            </h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <InputLabel for="falla_declarada" value="Falla Declarada por el Cliente *" />
                                    <textarea 
                                        id="falla_declarada" 
                                        name="falla_declarada" 
                                        v-model="form.falla_declarada" 
                                        rows="3" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Describe el problema reportado..."
                                    ></textarea>
                                    <InputError :message="form.errors.falla_declarada" class="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel for="observaciones" value="Observaciones Internas (Opcional)" />
                                    <textarea id="observaciones" name="observaciones" v-model="form.observaciones" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </div>
                                
                                <div class="w-full md:w-1/3">
                                    <div class="flex items-center mb-1">
                                        <InputLabel for="fecha_promesa" value="Fecha Promesa de Entrega" />
                                        <div class="group relative flex items-center ml-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400 cursor-help hover:text-indigo-500">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block w-48 p-2 bg-gray-800 text-white text-xs rounded shadow-lg z-10 text-center">
                                                Fecha estimada. Se usa para calcular SLA y alertas.
                                            </div>
                                        </div>
                                    </div>
                                    <TextInput id="fecha_promesa" name="fecha_promesa" v-model="form.fecha_promesa" type="datetime-local" class="mt-1 block w-full" />
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Evidencia (Fotos del Estado)
                            </h3>
                            
                            <div v-if="errorImagen" class="mb-4 text-sm text-red-600 font-bold bg-red-50 p-2 rounded border border-red-200">
                                {{ errorImagen }}
                            </div>

                            <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                    <div class="flex text-sm text-gray-600 justify-center mt-2">
                                        <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Subir fotos</span>
                                            <input id="file-upload" name="file-upload" type="file" class="sr-only" multiple accept="image/*" @change="handleImageUpload">
                                        </label>
                                        <p class="pl-1">o arrastrar y soltar</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, WEBP hasta 5MB (Máx 5 fotos)</p>
                                </div>
                            </div>
                            
                            <div v-if="imagenPreviews.length > 0" class="mt-6 grid grid-cols-2 md:grid-cols-5 gap-4">
                                <div v-for="(img, index) in imagenPreviews" :key="index" class="relative group">
                                    <img :src="img.url" class="h-24 w-full object-cover rounded-lg border shadow-sm" />
                                    <button type="button" @click="removeImage(index)" class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow-md transition transform hover:scale-110">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 border-t pt-6 bg-gray-50 -mx-8 -mb-8 p-6 rounded-b-lg">
                            <Link :href="route('reparaciones.index')" class="mr-4">
                                <SecondaryButton>Cancelar</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing" class="shadow-lg transform transition hover:-translate-y-0.5">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ form.processing ? 'Registrando...' : 'Registrar Ingreso' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>