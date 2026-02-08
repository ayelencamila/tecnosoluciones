<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { onMounted, computed } from 'vue';

const props = defineProps({
    cliente: Object,
    historialAuditoria: Array,
});

onMounted(() => {
    console.log("Datos de Cuenta Corriente:", props.cliente.cuenta_corriente);
});

const formatCurrency = (value) => {
    // Validación robusta para evitar NaN
    const numero = parseFloat(value);
    if (isNaN(numero)) return '$ 0,00';
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(numero);
};

// Computadas para acceso seguro a datos
const saldoActual = computed(() => {
    return parseFloat(props.cliente.cuenta_corriente?.saldo || 0);
});

const limiteCredito = computed(() => {
    return parseFloat(props.cliente.cuenta_corriente?.limiteCredito || 0);
});

const creditoDisponible = computed(() => {
    // Disponible = Límite - Saldo (Asumiendo saldo positivo es deuda)
    return limiteCredito.value - saldoActual.value;
});

const movimientos = computed(() => {
    // Intenta acceder con ambas convenciones por seguridad
    return props.cliente.cuenta_corriente?.movimientos_c_c || 
           props.cliente.cuenta_corriente?.movimientosCC || 
           [];
});

// --- MODAL DAR DE BAJA (CU-04) ---
const showModalBaja = ref(false);
const cargandoVerificacion = ref(false);
const operacionesPendientes = ref([]);
const puedeSerDadoDeBaja = ref(false);
const pasoActual = ref(1);
const errorMotivo = ref('');

const formBaja = useForm({
    motivo: '',
});

const abrirModalBaja = async () => {
    cargandoVerificacion.value = true;
    operacionesPendientes.value = [];
    puedeSerDadoDeBaja.value = false;
    pasoActual.value = 1;
    errorMotivo.value = '';
    showModalBaja.value = true;
    formBaja.reset();
    
    try {
        const response = await fetch(route('clientes.verificarBaja', props.cliente.clienteID), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        const data = await response.json();
        operacionesPendientes.value = data.operacionesPendientes || [];
        puedeSerDadoDeBaja.value = data.puedeSerDadoDeBaja;
        
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
    formBaja.reset();
    pasoActual.value = 1;
    errorMotivo.value = '';
};

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
    formBaja.post(route('clientes.darDeBaja', props.cliente.clienteID), {
        preserveScroll: true,
        onSuccess: () => {
            // CU-04 Paso 10: Confirma la baja exitosa
            cerrarModalBaja();
            // Recargar para reflejar el cambio
            router.reload();
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

const volverAPasoMotivo = () => {
    pasoActual.value = 2;
};
</script>

<template>
    <Head title="Detalle de Cliente" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <Link :href="route('clientes.index')" class="text-gray-500 hover:text-gray-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Ficha de Cliente: {{ cliente.nombre }} {{ cliente.apellido }}
                    </h2>
                </div>
                <Link :href="route('clientes.edit', cliente.clienteID)">
                    <PrimaryButton>Modificar</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Información Personal</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">Datos de contacto y ubicación.</p>
                        </div>
                        <span 
                            class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full"
                            :class="cliente.estado_cliente?.nombreEstado === 'Activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                        >
                            {{ cliente.estado_cliente?.nombreEstado }}
                        </span>
                    </div>
                    <div class="border-t border-gray-200">
                        <dl>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">DNI</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ cliente.DNI }}</dd>
                            </div>
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ cliente.mail || '-' }}</dd>
                            </div>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Teléfono / WhatsApp</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ cliente.telefono || '-' }} / {{ cliente.whatsapp || '-' }}
                                </dd>
                            </div>
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Dirección</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    <span v-if="cliente.direccion">
                                        {{ cliente.direccion.calle }} {{ cliente.direccion.altura }}
                                        <span v-if="cliente.direccion.pisoDepto">, {{ cliente.direccion.pisoDepto }}</span>
                                        <br>
                                        {{ cliente.direccion.localidad?.nombre }}, {{ cliente.direccion.localidad?.provincia?.nombre }}
                                        <span v-if="cliente.direccion.codigoPostal"> (CP: {{ cliente.direccion.codigoPostal }})</span>
                                    </span>
                                    <span v-else class="text-gray-400 italic">
                                        Sin dirección cargada
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div v-if="cliente.cuenta_corriente" class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-indigo-50">
                        <h3 class="text-lg leading-6 font-medium text-indigo-800">Cuenta Corriente (Mayorista)</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-6 py-6">
                        <div class="p-4 bg-white border border-gray-200 rounded-lg text-center shadow-sm">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Saldo Actual</p>
                            <p class="text-3xl font-bold mt-2" :class="saldoActual > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ formatCurrency(saldoActual) }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">{{ saldoActual > 0 ? 'Deuda Pendiente' : 'A favor / Al día' }}</p>
                        </div>

                        <div class="p-4 bg-white border border-gray-200 rounded-lg text-center shadow-sm">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Límite de Crédito</p>
                            <p class="text-3xl font-bold mt-2 text-gray-800">
                                {{ formatCurrency(limiteCredito) }}
                            </p>
                        </div>

                        <div class="p-4 bg-white border border-gray-200 rounded-lg text-center shadow-sm">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Crédito Disponible</p>
                            <p class="text-3xl font-bold mt-2 text-indigo-600">
                                {{ formatCurrency(creditoDisponible) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="cliente.cuenta_corriente" class="bg-white shadow overflow-hidden sm:rounded-lg mt-6">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Historial de Movimientos
                        </h3>
                        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Últimos 20 registros</span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concepto</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debe (Entrada)</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Haber (Salida)</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Parcial</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="mov in movimientos" :key="mov.id" class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ new Date(mov.created_at).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                        {{ mov.descripcion || mov.tipoMovimiento }}
                                        <div class="text-xs text-gray-400" v-if="mov.observaciones">{{ mov.observaciones }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600 font-bold">
                                        <span v-if="mov.tipoMovimiento === 'Debito'">
                                            {{ formatCurrency(mov.monto) }}
                                        </span>
                                        <span v-else class="text-gray-300">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600 font-bold">
                                        <span v-if="mov.tipoMovimiento === 'Credito'">
                                            {{ formatCurrency(mov.monto) }}
                                        </span>
                                        <span v-else class="text-gray-300">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-800 bg-gray-50">
                                        {{ formatCurrency(mov.saldoAlMomento) }}
                                    </td>
                                </tr>
                                <tr v-if="movimientos.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                            <span class="text-lg font-medium">Sin movimientos registrados</span>
                                            <span class="text-sm mt-1">El historial de cuenta corriente está vacío.</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="cliente.estado_cliente?.nombreEstado === 'Activo'" class="flex justify-end pt-4">
                    <DangerButton @click="abrirModalBaja">
                        Dar de Baja Cliente
                    </DangerButton>
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
                            <p class="text-sm text-gray-500">{{ cliente.nombre }} {{ cliente.apellido }}</p>
                            <p class="text-xs text-gray-400">DNI: {{ cliente.DNI }} | {{ cliente.tipo_cliente?.nombreTipo }}</p>
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
                            Para dar de baja a <strong>{{ cliente.nombre }} {{ cliente.apellido }}</strong>, 
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