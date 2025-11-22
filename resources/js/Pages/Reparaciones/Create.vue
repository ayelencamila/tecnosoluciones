<script setup>
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios'; 
import { debounce } from 'lodash';

const props = defineProps({
    productos: Array, 
});

const form = useForm({
    clienteID: '',
    equipo_marca: '',
    equipo_modelo: '',
    numero_serie_imei: '',
    clave_bloqueo: '',
    accesorios_dejados: '',
    falla_declarada: '',
    observaciones: '',
    fecha_promesa: '',
    imagenes: [],
});

// --- BUSCADOR DE CLIENTES ---
const busquedaCliente = ref('');
const resultadosClientes = ref([]);
const clienteSeleccionado = ref(null);
const buscando = ref(false);
const errorBusqueda = ref(false); // Nuevo estado para errores

const buscarClientes = debounce(async () => {
    if (busquedaCliente.value.length < 2) {
        resultadosClientes.value = [];
        return;
    }
    
    buscando.value = true;
    errorBusqueda.value = false;

    try {
        const response = await axios.get(route('api.clientes.buscar'), {
            params: { q: busquedaCliente.value }
        });
        resultadosClientes.value = response.data;
    } catch (error) {
        console.error("Error buscando clientes:", error);
        errorBusqueda.value = true;
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

// --- IMAGENES ---
const imagenPreviews = ref([]);

const handleImageUpload = (event) => {
    const files = Array.from(event.target.files);
    if (form.imagenes.length + files.length > 5) {
        alert('Solo puedes subir un máximo de 5 fotos.');
        return;
    }
    files.forEach(file => {
        form.imagenes.push(file);
        const reader = new FileReader();
        reader.onload = (e) => {
            imagenPreviews.value.push({ url: e.target.result, name: file.name });
        };
        reader.readAsDataURL(file);
    });
};

const removeImage = (index) => {
    form.imagenes.splice(index, 1);
    imagenPreviews.value.splice(index, 1);
};

const submit = () => {
    form.post(route('reparaciones.store'), {
        forceFormData: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Registrar Reparación" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva Reparación</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 sm:p-8">
                        
                        <div class="mb-8 border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Cliente</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="relative">
                                    <InputLabel value="Buscar Cliente (Nombre, Apellido o DNI) *" />
                                    
                                    <div v-if="!clienteSeleccionado">
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                v-model="busquedaCliente"
                                                @input="buscarClientes"
                                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pl-10"
                                                placeholder="Escribe para buscar..."
                                                autocomplete="off"
                                            />
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </div>
                                            <div v-if="buscando" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <div v-if="resultadosClientes.length > 0" class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                            <ul tabindex="-1" role="listbox">
                                                <li v-for="cliente in resultadosClientes" :key="cliente.clienteID" @click="seleccionarCliente(cliente)" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50 text-gray-900 border-b border-gray-100">
                                                    <div class="flex items-center">
                                                        <span class="font-bold block truncate">{{ cliente.apellido }}, {{ cliente.nombre }}</span>
                                                        <span class="ml-2 text-gray-500 text-xs">(DNI: {{ cliente.dni }})</span>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                        
                                        <div v-else-if="busquedaCliente.length > 2 && !buscando && resultadosClientes.length === 0" class="absolute z-50 mt-1 w-full bg-white shadow-lg rounded-md py-3 px-4 text-sm text-gray-500 border border-gray-100">
                                            No se encontraron clientes con ese dato.
                                        </div>
                                        
                                        <div class="mt-2 text-right">
                                            <Link :href="route('clientes.create')" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold hover:underline flex items-center justify-end">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Agregar Nuevo Cliente
                                            </Link>
                                        </div>
                                    </div>

                                    <div v-else class="mt-1 p-4 border border-indigo-200 bg-indigo-50 rounded-lg flex justify-between items-center">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold mr-3">
                                                {{ clienteSeleccionado.nombre.charAt(0) }}{{ clienteSeleccionado.apellido.charAt(0) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">{{ clienteSeleccionado.apellido }}, {{ clienteSeleccionado.nombre }}</p>
                                                <p class="text-xs text-gray-500">DNI: {{ clienteSeleccionado.dni }}</p>
                                            </div>
                                        </div>
                                        <button type="button" @click="quitarCliente" class="text-red-500 hover:text-red-700 text-xs font-bold uppercase tracking-wider border border-red-200 px-2 py-1 rounded bg-white hover:bg-red-50">Cambiar</button>
                                    </div>
                                    <InputError :message="form.errors.clienteID" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="mb-8 border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Datos del Equipo</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div><InputLabel for="equipo_marca" value="Marca *" /><TextInput id="equipo_marca" v-model="form.equipo_marca" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.equipo_marca" class="mt-2" /></div>
                                <div><InputLabel for="equipo_modelo" value="Modelo *" /><TextInput id="equipo_modelo" v-model="form.equipo_modelo" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.equipo_modelo" class="mt-2" /></div>
                                <div><InputLabel for="numero_serie_imei" value="Nro. Serie / IMEI" /><TextInput id="numero_serie_imei" v-model="form.numero_serie_imei" type="text" class="mt-1 block w-full" /></div>
                                <div><InputLabel for="clave_bloqueo" value="Clave / Patrón" /><TextInput id="clave_bloqueo" v-model="form.clave_bloqueo" type="text" class="mt-1 block w-full" /></div>
                                <div class="md:col-span-2"><InputLabel for="accesorios_dejados" value="Accesorios" /><TextInput id="accesorios_dejados" v-model="form.accesorios_dejados" type="text" class="mt-1 block w-full" /></div>
                            </div>
                        </div>

                        <div class="mb-8 border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Detalle del Problema</h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div><InputLabel for="falla_declarada" value="Falla Declarada *" /><textarea id="falla_declarada" v-model="form.falla_declarada" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea><InputError :message="form.errors.falla_declarada" class="mt-2" /></div>
                                <div><InputLabel for="observaciones" value="Observaciones Internas" /><textarea id="observaciones" v-model="form.observaciones" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea></div>
                                <div class="w-full md:w-1/3"><InputLabel for="fecha_promesa" value="Fecha Promesa" /><TextInput id="fecha_promesa" v-model="form.fecha_promesa" type="datetime-local" class="mt-1 block w-full" /></div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Evidencia (Fotos)</h3>
                            <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500"><span>Subir fotos</span><input id="file-upload" name="file-upload" type="file" class="sr-only" multiple accept="image/*" @change="handleImageUpload"></label>
                                    </div>
                                </div>
                            </div>
                            <div v-if="imagenPreviews.length > 0" class="mt-6 grid grid-cols-4 gap-4">
                                <div v-for="(img, index) in imagenPreviews" :key="index" class="relative"><img :src="img.url" class="h-24 w-full object-cover rounded-lg border" /><button type="button" @click="removeImage(index)" class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1">X</button></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 border-t pt-6">
                            <Link :href="route('reparaciones.index')" class="mr-4"><SecondaryButton>Cancelar</SecondaryButton></Link>
                            <PrimaryButton :disabled="form.processing">Registrar Ingreso</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
