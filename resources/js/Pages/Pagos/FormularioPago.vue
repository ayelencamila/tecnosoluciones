<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import ConfigurableSelect from '@/Components/ConfigurableSelect.vue';
import InputError from '@/Components/InputError.vue';
import { debounce } from 'lodash';

const props = defineProps({
    clientes: Array, 
    mediosPago: Array, 
});

// --- Lista reactiva para medios de pago ---
const mediosPagoList = ref([...props.mediosPago || []]);

// --- LÓGICA MEDIOS DE PAGO ---
const mediosOptions = computed(() => {
    if (!mediosPagoList.value) return [];
    return mediosPagoList.value.map(m => ({
        value: m.medioPagoID,
        label: `${m.nombre} ${parseFloat(m.recargo_porcentaje) > 0 ? '(+' + parseFloat(m.recargo_porcentaje) + '%)' : ''}`
    }));
});

// --- Función refresh para ConfigurableSelect ---
const refreshMediosPago = async () => {
    try {
        const response = await axios.get('/api/medios-pago');
        mediosPagoList.value = response.data;
    } catch (error) {
        console.error('Error al refrescar medios de pago:', error);
    }
};

// --- ESTADO LOCAL ---
const searchTermCliente = ref('');
const filteredClientes = ref([]);
const showClienteDropdown = ref(false);
const clienteSeleccionado = ref(null);
const documentosPendientes = ref([]);
const cargandoDocumentos = ref(false);
const imputacionesSeleccionadas = ref([]);
const modoImputacion = ref('automatico'); // 'automatico' o 'manual'

const form = useForm({
    clienteID: '',
    monto: '',
    medioPagoID: '', 
    observaciones: '',
    imputaciones: [], // Nuevo campo para imputaciones manuales
});

// --- FORMATEADORES ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

// Parsear número en formato argentino (74.925,50 → 74925.50)
const parseMontoArgentino = (value) => {
    if (!value) return 0;
    // Remover puntos de miles y cambiar coma decimal por punto
    const cleaned = String(value).replace(/\./g, '').replace(',', '.');
    return parseFloat(cleaned) || 0;
};

// Obtener monto numérico del formulario
const getMontoNumerico = () => parseMontoArgentino(form.monto);

// --- LÓGICA CLIENTES ---
const debouncedFilterClientes = debounce(() => {
    if (searchTermCliente.value.length >= 2) {
        filteredClientes.value = props.clientes.filter(cliente =>
            cliente.nombre.toLowerCase().includes(searchTermCliente.value.toLowerCase()) ||
            cliente.apellido.toLowerCase().includes(searchTermCliente.value.toLowerCase()) ||
            cliente.DNI.includes(searchTermCliente.value)
        ).slice(0, 10);
        showClienteDropdown.value = true;
    } else {
        filteredClientes.value = [];
        showClienteDropdown.value = false;
    }
}, 300);

watch(searchTermCliente, debouncedFilterClientes);

const selectCliente = (cliente) => {
    clienteSeleccionado.value = cliente;
    form.clienteID = cliente.clienteID;
    searchTermCliente.value = `${cliente.apellido}, ${cliente.nombre}`;
    showClienteDropdown.value = false;
    
    // CU-10 Paso 6: Cargar documentos pendientes del cliente
    cargarDocumentosPendientes(cliente.clienteID);
};

const cargarDocumentosPendientes = async (clienteID) => {
    cargandoDocumentos.value = true;
    documentosPendientes.value = [];
    imputacionesSeleccionadas.value = [];
    
    try {
        const response = await axios.get(route('pagos.cliente.documentos', clienteID));
        documentosPendientes.value = response.data.documentos;
        
        // Si no hay documentos pendientes, mostrar alerta (CU-10 Excepción 3a)
        if (documentosPendientes.value.length === 0) {
            // El usuario puede continuar para registrar un anticipo
            console.info('Cliente sin documentos pendientes. El pago se registrará como anticipo.');
        }
    } catch (error) {
        console.error('Error al cargar documentos pendientes:', error);
    } finally {
        cargandoDocumentos.value = false;
    }
};

const clearCliente = () => {
    clienteSeleccionado.value = null;
    form.clienteID = '';
    searchTermCliente.value = '';
    documentosPendientes.value = [];
    imputacionesSeleccionadas.value = [];
    modoImputacion.value = 'automatico';
    form.clearErrors();
};

// --- LÓGICA DE IMPUTACIÓN (CU-10 Paso 7) ---
const calcularImputacionAutomatica = () => {
    if (!getMontoNumerico() || documentosPendientes.value.length === 0) {
        return [];
    }
    
    let montoDisponible = getMontoNumerico();
    const imputaciones = [];
    
    for (const doc of documentosPendientes.value) {
        if (montoDisponible <= 0) break;
        
        const montoAImputar = Math.min(montoDisponible, parseFloat(doc.saldo_pendiente));
        
        if (montoAImputar > 0) {
            imputaciones.push({
                venta_id: doc.venta_id,
                numero_comprobante: doc.numero_comprobante,
                saldo_pendiente: doc.saldo_pendiente,
                monto_imputado: montoAImputar.toFixed(2),
            });
            montoDisponible -= montoAImputar;
        }
    }
    
    return imputaciones;
};

const aplicarImputacionAutomatica = () => {
    imputacionesSeleccionadas.value = calcularImputacionAutomatica();
};

const agregarDocumentoAImputacion = (documento) => {
    const existe = imputacionesSeleccionadas.value.find(i => i.venta_id === documento.venta_id);
    if (!existe) {
        imputacionesSeleccionadas.value.push({
            venta_id: documento.venta_id,
            numero_comprobante: documento.numero_comprobante,
            saldo_pendiente: documento.saldo_pendiente,
            monto_imputado: parseFloat(documento.saldo_pendiente).toFixed(2),
        });
    }
};

const removerDocumentoDeImputacion = (venta_id) => {
    imputacionesSeleccionadas.value = imputacionesSeleccionadas.value.filter(i => i.venta_id !== venta_id);
};

const totalImputado = computed(() => {
    return imputacionesSeleccionadas.value.reduce((sum, imp) => sum + parseFloat(imp.monto_imputado || 0), 0);
});

const montoRemanente = computed(() => {
    const monto = getMontoNumerico();
    return monto - totalImputado.value;
});

const hayAnticipo = computed(() => {
    return montoRemanente.value > 0 && documentosPendientes.value.length > 0;
});

// --- PROPIEDADES UI ---
const infoCuentaCorriente = computed(() => {
    return clienteSeleccionado.value?.cuenta_corriente || null;
});

const submit = () => {
    if (!form.clienteID) {
        alert('Debe seleccionar un cliente.');
        return;
    }

    const montoNumerico = getMontoNumerico();
    if (montoNumerico <= 0) {
        alert('El monto debe ser mayor a 0.');
        return;
    }
    
    // CU-10 Excepción 5a: Advertir si hay anticipo
    if (hayAnticipo.value && modoImputacion.value === 'manual') {
        if (!confirm(`El importe excede la deuda imputada. El remanente de $${montoRemanente.value.toFixed(2)} será registrado como anticipo a favor del cliente. ¿Desea continuar?`)) {
            return;
        }
    }
    
    // Si está en modo manual, incluir las imputaciones
    if (modoImputacion.value === 'manual' && imputacionesSeleccionadas.value.length > 0) {
        form.imputaciones = imputacionesSeleccionadas.value.map(imp => ({
            venta_id: imp.venta_id,
            monto_imputado: parseFloat(imp.monto_imputado)
        }));
    } else {
        form.imputaciones = [];
    }

    // Convertir monto a formato numérico antes de enviar
    form.transform(data => ({
        ...data,
        monto: montoNumerico
    })).post(route('pagos.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.cliente-search-container')) {
            showClienteDropdown.value = false;
        }
    });
});
</script>

<template>
    <Head title="Registrar Pago" />
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Nuevo Pago</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <div class="bg-white shadow-xl sm:rounded-lg overflow-hidden">
                        <div class="bg-indigo-50 px-6 py-4 border-b border-indigo-100">
                            <h3 class="text-lg font-medium text-indigo-800">Detalles del Pago</h3>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="md:col-span-2 relative cliente-search-container">
                                <InputLabel for="cliente_search" value="Cliente (Buscar por Nombre o DNI)" />
                                <div class="flex mt-1">
                                    <TextInput id="cliente_search" v-model="searchTermCliente" placeholder="Escriba para buscar..." class="w-full" autocomplete="off" @focus="searchTermCliente.length >= 2 ? showClienteDropdown = true : null" />
                                    <button type="button" v-if="clienteSeleccionado" @click="clearCliente" class="ml-2 text-gray-400 hover:text-red-500" title="Limpiar"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                </div>
                                <ul v-if="showClienteDropdown && filteredClientes.length" class="absolute z-20 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto">
                                    <li v-for="cli in filteredClientes" :key="cli.clienteID" @click="selectCliente(cli)" class="px-4 py-3 hover:bg-indigo-50 cursor-pointer border-b last:border-b-0">
                                        <div class="font-bold text-gray-800">{{ cli.apellido }}, {{ cli.nombre }}</div>
                                        <div class="text-xs text-gray-500">DNI: {{ cli.DNI }} <span v-if="cli.cuenta_corriente" :class="cli.cuenta_corriente.saldo > 0 ? 'text-red-600' : 'text-green-600'">| Deuda: {{ formatCurrency(cli.cuenta_corriente.saldo) }}</span></div>
                                    </li>
                                </ul>
                                <InputError :message="form.errors.clienteID" class="mt-2" />
                            </div>

                            <div v-if="clienteSeleccionado && infoCuentaCorriente" class="md:col-span-2 bg-gray-50 rounded-lg p-4 border border-gray-200 flex justify-between items-center">
                                <div><span class="text-xs font-bold text-gray-400 uppercase">Saldo Actual</span><span class="text-2xl font-bold" :class="infoCuentaCorriente.saldo > 0 ? 'text-red-600' : 'text-green-600'">{{ formatCurrency(infoCuentaCorriente.saldo) }}</span></div>
                            </div>

                            <div>
                                <InputLabel for="monto" value="Monto a Pagar" />
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500 sm:text-sm">$</span></div>
                                    <TextInput id="monto" type="text" v-model="form.monto" class="w-full pl-7 font-bold text-lg" placeholder="Ej: 74.925,00" inputmode="decimal" />
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Formato: 74.925,00</p>
                                <InputError :message="form.errors.monto" class="mt-2" />
                            </div>

                            <div>
                                <ConfigurableSelect
                                    id="medioPagoID"
                                    v-model="form.medioPagoID"
                                    label="Método de Pago"
                                    :options="mediosOptions"
                                    placeholder="Seleccione método..."
                                    :error="form.errors.medioPagoID"
                                    api-endpoint="/api/medios-pago"
                                    name-field="nombre"
                                    @refresh="refreshMediosPago"
                                />
                            </div>

                            <!-- CU-10 Paso 6 y 7: Documentos Pendientes e Imputación -->
                            <div v-if="clienteSeleccionado && documentosPendientes.length > 0" class="md:col-span-2 border-t pt-6 mt-4">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-lg font-semibold text-gray-700">📋 Documentos Pendientes de Pago</h4>
                                    <div class="flex gap-2">
                                        <button 
                                            type="button"
                                            @click="modoImputacion = 'automatico'; imputacionesSeleccionadas = []"
                                            :class="modoImputacion === 'automatico' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'"
                                            class="px-3 py-1 rounded text-sm font-medium"
                                        >
                                            Automático
                                        </button>
                                        <button 
                                            type="button"
                                            @click="modoImputacion = 'manual'"
                                            :class="modoImputacion === 'manual' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'"
                                            class="px-3 py-1 rounded text-sm font-medium"
                                        >
                                            Manual
                                        </button>
                                    </div>
                                </div>

                                <!-- Modo Automático -->
                                <div v-if="modoImputacion === 'automatico'" class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <p class="text-sm text-blue-800 mb-2">
                                        ✓ El sistema imputará automáticamente el pago a los documentos más antiguos.
                                    </p>
                                    <button 
                                        type="button" 
                                        @click="aplicarImputacionAutomatica"
                                        class="text-sm text-blue-600 hover:text-blue-800 underline"
                                    >
                                        👁️ Ver sugerencia de imputación
                                    </button>
                                    
                                    <div v-if="imputacionesSeleccionadas.length > 0" class="mt-3 space-y-1">
                                        <p class="text-xs font-bold text-gray-600 uppercase">Vista Previa:</p>
                                        <div v-for="imp in imputacionesSeleccionadas" :key="imp.venta_id" class="text-xs text-gray-700 flex justify-between">
                                            <span>{{ imp.numero_comprobante }}</span>
                                            <span class="font-mono">${{ imp.monto_imputado }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modo Manual -->
                                <div v-else class="space-y-3">
                                    <!-- Lista de documentos disponibles -->
                                    <div class="bg-gray-50 p-3 rounded border border-gray-200 max-h-60 overflow-y-auto">
                                        <p class="text-xs font-bold text-gray-600 uppercase mb-2">Documentos Disponibles:</p>
                                        <div 
                                            v-for="doc in documentosPendientes" 
                                            :key="doc.venta_id"
                                            class="flex justify-between items-center py-2 border-b last:border-b-0 hover:bg-white px-2 rounded"
                                        >
                                            <div class="flex-1">
                                                <div class="text-sm font-medium text-gray-800">{{ doc.numero_comprobante }}</div>
                                                <div class="text-xs text-gray-500">{{ doc.fecha_venta }} | Pendiente: ${{ doc.saldo_pendiente }}</div>
                                            </div>
                                            <button 
                                                type="button"
                                                @click="agregarDocumentoAImputacion(doc)"
                                                class="px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600"
                                                :disabled="imputacionesSeleccionadas.some(i => i.venta_id === doc.venta_id)"
                                            >
                                                + Agregar
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Documentos seleccionados para imputar -->
                                    <div v-if="imputacionesSeleccionadas.length > 0" class="bg-indigo-50 p-3 rounded border border-indigo-200">
                                        <p class="text-xs font-bold text-indigo-800 uppercase mb-2">Imputaciones Seleccionadas:</p>
                                        <div 
                                            v-for="(imp, index) in imputacionesSeleccionadas" 
                                            :key="imp.venta_id"
                                            class="flex items-center gap-2 py-2 border-b last:border-b-0"
                                        >
                                            <div class="flex-1">
                                                <div class="text-sm font-medium text-gray-800">{{ imp.numero_comprobante }}</div>
                                                <div class="text-xs text-gray-500">Pendiente: ${{ imp.saldo_pendiente }}</div>
                                            </div>
                                            <div class="w-32">
                                                <input 
                                                    type="number" 
                                                    v-model="imp.monto_imputado"
                                                    :max="imp.saldo_pendiente"
                                                    min="0.01"
                                                    step="0.01"
                                                    class="w-full text-sm border-gray-300 rounded px-2 py-1"
                                                    placeholder="Monto"
                                                />
                                            </div>
                                            <button 
                                                type="button"
                                                @click="removerDocumentoDeImputacion(imp.venta_id)"
                                                class="text-red-500 hover:text-red-700"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                        
                                        <div class="mt-3 pt-3 border-t border-indigo-300 flex justify-between text-sm font-bold">
                                            <span>Total Imputado:</span>
                                            <span class="font-mono text-indigo-800">${{ totalImputado.toFixed(2) }}</span>
                                        </div>
                                        
                                        <!-- CU-10 Excepción 5a: Alerta de Anticipo -->
                                        <div v-if="montoRemanente > 0" class="mt-2 p-2 bg-yellow-100 border border-yellow-300 rounded text-xs">
                                            <strong class="text-yellow-800">⚠️ Anticipo:</strong> 
                                            <span class="text-yellow-700">El remanente de ${{ montoRemanente.toFixed(2) }} se registrará como anticipo a favor del cliente.</span>
                                        </div>
                                        
                                        <InputError :message="form.errors.imputaciones" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- CU-10 Excepción 3a: Cliente sin documentos pendientes -->
                            <div v-else-if="clienteSeleccionado && !cargandoDocumentos && documentosPendientes.length === 0" class="md:col-span-2 bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                <p class="text-sm text-yellow-800">
                                    ℹ️ <strong>El cliente no tiene documentos pendientes.</strong><br>
                                    El pago se registrará como <strong>anticipo a favor del cliente</strong>.
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="observaciones" value="Observaciones (Opcional)" />
                                <TextInput id="observaciones" v-model="form.observaciones" class="w-full mt-1" />
                                <InputError :message="form.errors.observaciones" class="mt-2" />
                            </div>
                        </div>

                        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="px-6 py-3">Registrar Pago</PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>