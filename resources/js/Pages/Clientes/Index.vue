<script setup>
import { ref, watch, computed } from 'vue'; 
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { debounce } from 'lodash';

const props = defineProps({
    clientes: Object, 
    estadosCliente: Array,
    tiposCliente: Array,
    provincias: Array,
    filters: Object,
    counts: Object, 
});

const form = ref({
    search: props.filters.search || '',
    tipo_cliente_id: props.filters.tipo_cliente_id || '',
    estado_cliente_id: props.filters.estado_cliente_id || '',
    provincia_id: props.filters.provincia_id || '',
    sort_column: props.filters.sort_column || 'apellido',
    sort_direction: props.filters.sort_direction || 'asc',
});

const tiposClienteOptions = computed(() => [
    { value: '', label: 'Todos los Tipos' },
    ...props.tiposCliente.map(t => ({ value: t.tipoClienteID, label: t.nombreTipo }))
]);

const estadosClienteOptions = computed(() => [
    { value: '', label: 'Todos los Estados' },
    ...props.estadosCliente.map(e => ({ value: e.estadoClienteID, label: e.nombreEstado }))
]);

const provinciasOptions = computed(() => [
    { value: '', label: 'Todas las Provincias' },
    ...props.provincias.map(p => ({ value: p.provinciaID, label: p.nombre }))
]);

watch(form, debounce(() => {
    router.get(route('clientes.index'), form.value, { preserveState: true, replace: true });
}, 300), { deep: true });

const sortBy = (column) => {
    form.value.sort_column = column;
    form.value.sort_direction = form.value.sort_direction === 'asc' ? 'desc' : 'asc';
};

const resetFilters = () => {
    form.value = { search: '', tipo_cliente_id: '', estado_cliente_id: '', provincia_id: '', sort_column: 'apellido', sort_direction: 'asc' };
};

const getEstadoBadgeClass = (estado) => {
    switch (estado?.toLowerCase()) {
        case 'activo': return 'bg-green-100 text-green-800';
        case 'inactivo': return 'bg-red-100 text-red-800';
        case 'moroso': return 'bg-orange-100 text-orange-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// --- MODAL DAR DE BAJA (CU-04) ---
const showModalBaja = ref(false);
const clienteSeleccionado = ref(null);
const cargandoVerificacion = ref(false);
const operacionesPendientes = ref([]);
const puedeSerDadoDeBaja = ref(false);
const pasoActual = ref(1); // 1: verificando/pendientes, 2: motivo, 3: confirmar
const errorMotivo = ref('');

const formBaja = useForm({
    motivo: '',
});

const abrirModalBaja = async (cliente) => {
    clienteSeleccionado.value = cliente;
    cargandoVerificacion.value = true;
    operacionesPendientes.value = [];
    puedeSerDadoDeBaja.value = false;
    pasoActual.value = 1;
    errorMotivo.value = '';
    showModalBaja.value = true;
    formBaja.reset();
    
    // CU-04 Paso 4: Verificar operaciones activas pendientes
    try {
        const response = await fetch(route('clientes.verificarBaja', cliente.clienteID), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        const data = await response.json();
        operacionesPendientes.value = data.operacionesPendientes || [];
        puedeSerDadoDeBaja.value = data.puedeSerDadoDeBaja;
        
        // CU-04 Paso 5: Si no hay operaciones pendientes, pasar a solicitar motivo
        if (data.puedeSerDadoDeBaja) {
            pasoActual.value = 2;
        }
    } catch (error) {
        console.error('Error verificando baja:', error);
    } finally {
        cargandoVerificacion.value = false;
    }
};

const cerrarModalBaja = () => {
    showModalBaja.value = false;
    clienteSeleccionado.value = null;
    formBaja.reset();
    pasoActual.value = 1;
    errorMotivo.value = '';
};

// CU-04 Paso 6: Usuario ingresa motivo -> Paso 7: Mostrar confirmación
const continuarAConfirmacion = () => {
    if (!formBaja.motivo || formBaja.motivo.trim() === '') {
        errorMotivo.value = 'Debe ingresar el motivo de la baja.';
        return;
    }
    if (formBaja.motivo.trim().length < 5) {
        errorMotivo.value = 'El motivo debe tener al menos 5 caracteres.';
        return;
    }
    errorMotivo.value = '';
    pasoActual.value = 3;
};

// CU-04 Paso 8: Usuario confirma -> Paso 9: Procesar baja
const confirmarBaja = () => {
    formBaja.post(route('clientes.darDeBaja', clienteSeleccionado.value.clienteID), {
        preserveScroll: true,
        onSuccess: () => {
            // CU-04 Paso 10: Confirma la baja exitosa
            cerrarModalBaja();
            // Recargar la página para reflejar el cambio de estado
            router.reload({ only: ['clientes', 'counts'] });
        },
        onError: (errors) => {
            // CU-04 Excepción 9a: Error al procesar la baja
            if (errors.motivo) {
                // Si el error menciona "procesar" es excepción 9a, mostrar en paso 3
                if (errors.motivo.includes('procesar') || errors.motivo.includes('Error')) {
                    errorMotivo.value = errors.motivo;
                    // Mantenemos en paso 3 para que vea el error
                } else {
                    // Error de validación del motivo, volver al paso 2
                    errorMotivo.value = errors.motivo;
                    pasoActual.value = 2;
                }
            }
            console.error('CU-04 Error al dar de baja:', errors);
        },
    });
};

// Volver al paso anterior
const volverAPasoMotivo = () => {
    pasoActual.value = 2;
};

// --- SOLUCIÓN INFALIBLE PAGINACIÓN ---
// No miramos el texto, miramos la posición.
const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;'; // El primero siempre es "Anterior" -> Flecha Izq
    if (index === totalLinks - 1) return '&raquo;'; // El último siempre es "Siguiente" -> Flecha Der
    return label; // Los del medio son números, los dejamos igual
};
</script>

<template>
    <Head title="Gestión de Clientes" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Clientes</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-center">
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-indigo-500">
                        <p class="text-sm font-medium text-gray-500">Total Clientes</p>
                        <p class="text-2xl font-bold text-gray-900">{{ props.counts?.total || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-green-500">
                        <p class="text-sm font-medium text-gray-500">Activos</p>
                        <p class="text-2xl font-bold text-gray-900">{{ props.counts?.activos || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-red-500">
                        <p class="text-sm font-medium text-gray-500">Inactivos</p>
                        <p class="text-2xl font-bold text-gray-900">{{ props.counts?.inactivos || 0 }}</p>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                        <div class="flex-1 w-full">
                            <TextInput v-model="form.search" placeholder="Buscar por Nombre, DNI o Email..." class="w-full" />
                        </div>
                        <Link :href="route('clientes.create')">
                            <PrimaryButton>+ Nuevo Cliente</PrimaryButton>
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <SelectInput v-model="form.tipo_cliente_id" class="w-full" :options="tiposClienteOptions" />
                        <SelectInput v-model="form.estado_cliente_id" class="w-full" :options="estadosClienteOptions" />
                        <SelectInput v-model="form.provincia_id" class="w-full" :options="provinciasOptions" />
                        
                        <div class="flex justify-end items-center">
                            <button @click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900 underline text-right">
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th @click="sortBy('apellido')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
                                        Cliente <span v-if="form.sort_column === 'apellido'">{{ form.sort_direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th @click="sortBy('DNI')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
                                        Contacto / DNI
                                    </th>
                                    <th @click="sortBy('tipoClienteID')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none">
                                        Tipo / Estado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        Ubicación
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="cliente in clientes.data" :key="cliente.clienteID" class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ cliente.apellido }}, {{ cliente.nombre }}</div>
                                        <div class="text-xs text-gray-500">{{ cliente.mail }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 font-mono">{{ cliente.DNI }}</div>
                                        <div class="text-xs text-gray-500">{{ cliente.whatsapp || cliente.telefono || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs text-gray-500 mb-1 uppercase font-semibold">{{ cliente.tipo_cliente?.nombreTipo }}</div>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getEstadoBadgeClass(cliente.estado_cliente?.nombreEstado)">
                                            {{ cliente.estado_cliente?.nombreEstado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ cliente.direccion?.localidad?.nombre || '-' }}, {{ cliente.direccion?.localidad?.provincia?.nombre || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-3 items-center">
                                            <Link :href="route('clientes.show', cliente.clienteID)" class="text-indigo-600 hover:text-indigo-900 font-bold" title="Ver">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </Link>
                                            <Link :href="route('clientes.edit', cliente.clienteID)" class="text-yellow-600 hover:text-yellow-900" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                            </Link>
                                            <button v-if="cliente.estado_cliente?.nombreEstado === 'Activo'" @click="abrirModalBaja(cliente)" class="text-red-600 hover:text-red-900" title="Dar de Baja">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="clientes.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <span class="text-lg font-medium">No se encontraron resultados</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200" v-if="clientes.links.length > 3">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in clientes.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-medium transition-all duration-150"
                                    :class="link.active 
                                        ? 'bg-indigo-600 text-white shadow-md ring-2 ring-indigo-300' 
                                        : 'bg-white text-gray-600 border border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'"
                                >
                                    <span v-html="getPaginationLabel(link.label, k, clientes.links.length)"></span>
                                </Link>
                                <span 
                                    v-else 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm text-gray-300 border border-gray-200 cursor-not-allowed" 
                                    v-html="getPaginationLabel(link.label, k, clientes.links.length)"
                                ></span>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Modal Dar de Baja (CU-04) -->
        <Modal :show="showModalBaja" @close="cerrarModalBaja" max-width="lg">
            <div class="p-6">
                <!-- Header con info del cliente -->
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center mr-4 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Dar de Baja Cliente</h3>
                            <p class="text-sm text-gray-500">{{ clienteSeleccionado?.nombre }} {{ clienteSeleccionado?.apellido }}</p>
                            <p class="text-xs text-gray-400">DNI: {{ clienteSeleccionado?.DNI }} | {{ clienteSeleccionado?.tipo_cliente?.nombreTipo }}</p>
                        </div>
                    </div>
                    <button @click="cerrarModalBaja" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Stepper visual -->
                <div v-if="!cargandoVerificacion && puedeSerDadoDeBaja" class="mb-6">
                    <div class="flex items-center justify-center">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold transition-all"
                                :class="pasoActual >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500'">
                                1
                            </div>
                            <span class="ml-2 text-sm font-medium" :class="pasoActual >= 2 ? 'text-indigo-600' : 'text-gray-500'">Motivo</span>
                        </div>
                        <div class="w-12 h-0.5 mx-2" :class="pasoActual >= 3 ? 'bg-indigo-600' : 'bg-gray-200'"></div>
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold transition-all"
                                :class="pasoActual >= 3 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500'">
                                2
                            </div>
                            <span class="ml-2 text-sm font-medium" :class="pasoActual >= 3 ? 'text-indigo-600' : 'text-gray-500'">Confirmar</span>
                        </div>
                    </div>
                </div>

                <!-- Cargando (Paso 4: Verificando operaciones pendientes) -->
                <div v-if="cargandoVerificacion" class="text-center py-10">
                    <div class="relative">
                        <div class="w-16 h-16 mx-auto">
                            <svg class="animate-spin h-16 w-16 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-4 text-base font-medium text-gray-700">Verificando operaciones pendientes...</p>
                    <p class="mt-1 text-sm text-gray-500">Comprobando deudas, ventas y reparaciones en curso</p>
                </div>

                <!-- Paso 1: Excepción 4a - Operaciones Pendientes (NO PUEDE SER DADO DE BAJA) -->
                <div v-else-if="pasoActual === 1 && operacionesPendientes.length > 0">
                    <div class="p-5 bg-gradient-to-br from-amber-50 to-yellow-50 border border-amber-200 rounded-xl">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-amber-800">No es posible dar de baja</h4>
                                <p class="text-sm text-amber-700">El cliente tiene operaciones activas pendientes</p>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-lg border border-amber-100 shadow-sm mb-4">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-3 tracking-wide">Operaciones detectadas:</p>
                            <ul class="space-y-2">
                                <li v-for="(op, i) in operacionesPendientes" :key="i" class="flex items-start text-sm text-amber-800">
                                    <svg class="w-4 h-4 mr-2 mt-0.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ op }}
                                </li>
                            </ul>
                        </div>

                        <p class="text-sm text-amber-700">
                            Para dar de baja a <strong>{{ clienteSeleccionado?.nombre }} {{ clienteSeleccionado?.apellido }}</strong>, 
                            primero debe completar o cancelar las operaciones pendientes listadas arriba.
                        </p>
                    </div>
                    <div class="flex justify-end mt-5 pt-4 border-t border-gray-100">
                        <SecondaryButton @click="cerrarModalBaja" class="px-5">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cerrar
                        </SecondaryButton>
                    </div>
                </div>

                <!-- Paso 2 (CU-04 Paso 5-6): Solicitar Motivo -->
                <div v-else-if="pasoActual === 2">
                    <div class="mb-5 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-blue-800">Verificación completada</p>
                                <p class="text-sm text-blue-700 mt-0.5">El cliente no tiene operaciones pendientes y puede ser dado de baja.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <InputLabel for="motivo" class="mb-2 text-gray-700 font-semibold">
                            Motivo de la baja <span class="text-red-500">*</span>
                        </InputLabel>
                        <textarea 
                            id="motivo" 
                            v-model="formBaja.motivo" 
                            rows="3"
                            class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm"
                            placeholder="Describa el motivo de la baja del cliente...&#10;Ej: Cliente solicitó cierre de cuenta por mudanza."
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500">Mínimo 5 caracteres. Este motivo quedará registrado en el historial.</p>
                        <p v-if="errorMotivo" class="mt-2 text-sm text-red-600 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ errorMotivo }}
                        </p>
                        <InputError :message="formBaja.errors.motivo" class="mt-2" />
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <SecondaryButton @click="cerrarModalBaja">Cancelar</SecondaryButton>
                        <PrimaryButton @click="continuarAConfirmacion" class="bg-indigo-600 hover:bg-indigo-700">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            Continuar
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Paso 3 (CU-04 Paso 7-8): Confirmación Final -->
                <div v-else-if="pasoActual === 3">
                    <div class="mb-5 p-5 bg-gradient-to-br from-red-50 to-orange-50 border border-red-200 rounded-xl">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-red-800">¿Confirma la baja del cliente?</h4>
                        </div>
                        
                        <div class="space-y-3">
                            <p class="text-sm text-red-700">
                                Esta acción realizará los siguientes cambios:
                            </p>
                            <ul class="text-sm text-red-700 space-y-1 ml-4">
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    El estado del cliente cambiará a <strong>Inactivo</strong>
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    No podrá realizar nuevas operaciones comerciales
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    Su cuenta corriente será deshabilitada
                                </li>
                            </ul>
                        </div>

                        <div class="mt-4 bg-white p-4 rounded-lg border border-red-100 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-2 tracking-wide">Motivo registrado:</p>
                            <p class="text-sm text-gray-800 italic">"{{ formBaja.motivo }}"</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                        <button 
                            @click="volverAPasoMotivo" 
                            class="text-sm text-gray-500 hover:text-gray-700 flex items-center transition-colors"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Volver a editar motivo
                        </button>
                        <DangerButton 
                            @click="confirmarBaja"
                            :class="{ 'opacity-50 cursor-not-allowed': formBaja.processing }" 
                            :disabled="formBaja.processing"
                            class="px-6"
                        >
                            <svg v-if="formBaja.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ formBaja.processing ? 'Procesando...' : 'Confirmar Baja Definitiva' }}
                        </DangerButton>
                    </div>

                    <!-- CU-04 Excepción 9a: Error al procesar la baja -->
                    <div v-if="errorMotivo && pasoActual === 3" class="mt-4 p-4 bg-red-100 border border-red-300 rounded-lg">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-red-800">Error al procesar la baja</p>
                                <p class="text-sm text-red-700 mt-1">{{ errorMotivo }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>