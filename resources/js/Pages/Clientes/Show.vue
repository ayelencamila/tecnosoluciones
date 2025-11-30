<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
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

</script>

<template>
    <Head title="Detalle de Cliente" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Ficha de Cliente: {{ cliente.nombre }} {{ cliente.apellido }}
                </h2>
                <div class="space-x-2">
                    <Link :href="route('clientes.index')">
                        <SecondaryButton>Volver</SecondaryButton>
                    </Link>
                    <Link :href="route('clientes.edit', cliente.clienteID)">
                        <PrimaryButton>Modificar</PrimaryButton>
                    </Link>
                </div>
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
                    <Link :href="route('clientes.confirmDelete', cliente.clienteID)">
                        <DangerButton>
                            Dar de Baja Cliente
                        </DangerButton>
                    </Link>
                </div>

            </div>
        </div>
    </AppLayout>
</template>