<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    pago: Object,
});

// --- HELPERS ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// --- CALCULAR ANTICIPO (CU-10 Post-condición) ---
const totalImputado = computed(() => {
    if (!props.pago.ventas_imputadas || props.pago.ventas_imputadas.length === 0) {
        return 0;
    }
    return props.pago.ventas_imputadas.reduce((sum, venta) => sum + parseFloat(venta.pivot.monto_imputado || 0), 0);
});

const anticipoGenerado = computed(() => {
    const monto = parseFloat(props.pago.monto);
    const imputado = totalImputado.value;
    const anticipo = monto - imputado;
    return anticipo > 0 ? anticipo : 0;
});

// --- ACCIÓN DE ANULAR ---
const anularPago = () => {
    if (confirm('¿Estás seguro de que deseas anular este pago? Esta acción revertirá el saldo en la cuenta corriente.')) {
        // CORRECCIÓN AQUÍ: Pasamos el ID del pago (pago.pagoID) a la ruta
        router.delete(route('pagos.anular', props.pago.pagoID));
    }
};

// --- ACCIÓN DE IMPRIMIR (CU-10 Paso 12 - Kendall) ---
const imprimirRecibo = () => {
    // Abre el recibo en nueva ventana para impresión
    window.open(route('pagos.imprimir', props.pago.pagoID), '_blank', 'width=800,height=900');
};
</script>

<template>
    <Head title="Detalle de Pago" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Recibo de Pago #{{ pago.numero_recibo }}
                </h2>
                <span 
                    class="px-3 py-1 rounded-full text-sm font-bold uppercase tracking-wide border"
                    :class="pago.anulado ? 'bg-red-100 text-red-800 border-red-200' : 'bg-green-100 text-green-800 border-green-200'"
                >
                    {{ pago.anulado ? 'ANULADO' : 'VALIDO' }}
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    
                    <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Información del Pago</h3>
                            <p class="mt-1 text-sm text-gray-500">Detalles de la transacción financiera.</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(pago.monto) }}</p>
                            <p class="text-sm text-gray-500">{{ formatDate(pago.fecha_pago) }}</p>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                        <dl class="sm:divide-y sm:divide-gray-200">
                            
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Cliente</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-bold">
                                    {{ pago.cliente?.apellido }}, {{ pago.cliente?.nombre }}
                                    <span class="text-gray-500 font-normal ml-1">(DNI: {{ pago.cliente?.DNI }})</span>
                                </dd>
                            </div>

                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Método de Pago</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 capitalize">
                                    {{ pago.medio_pago?.nombre || 'Desconocido' }}
                                </dd>
                            </div>

                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Cajero / Usuario</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ pago.cajero?.name || 'Sistema' }}
                                </dd>
                            </div>

                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">Observaciones</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ pago.observaciones || '-' }}
                                </dd>
                            </div>

                        </dl>
                    </div>
                    
                    <div class="px-6 py-5 border-t border-gray-200 bg-gray-50" v-if="pago.ventas_imputadas && pago.ventas_imputadas.length > 0">
                        <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3">Imputación (Deudas canceladas)</h4>
                        <ul class="space-y-2">
                            <li v-for="venta in pago.ventas_imputadas" :key="venta.venta_id" class="flex justify-between text-sm border-b border-gray-200 pb-2 last:border-0">
                                <span>Venta N° <strong>{{ venta.numero_comprobante }}</strong></span>
                                <span class="font-mono">Abonado: {{ formatCurrency(venta.pivot.monto_imputado) }}</span>
                            </li>
                        </ul>
                        <div class="mt-3 pt-3 border-t border-gray-300 flex justify-between text-sm font-bold">
                            <span>Total Imputado:</span>
                            <span class="font-mono text-gray-800">{{ formatCurrency(totalImputado) }}</span>
                        </div>
                    </div>

                    <!-- CU-10 Excepción 5a: Mostrar Anticipo si existe -->
                    <div v-if="anticipoGenerado > 0" class="px-6 py-4 border-t border-yellow-200 bg-yellow-50">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-yellow-800 uppercase tracking-wider">Anticipo Generado</h4>
                                <p class="text-xs text-yellow-700 mt-1">
                                    El importe del pago excedió la deuda imputada. El remanente quedó registrado como saldo a favor del cliente.
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-yellow-800">{{ formatCurrency(anticipoGenerado) }}</div>
                                <div class="text-xs text-yellow-600">Saldo a favor</div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensaje cuando no hay documentos imputados (todo es anticipo) -->
                    <div v-else-if="!pago.ventas_imputadas || pago.ventas_imputadas.length === 0" class="px-6 py-4 border-t border-blue-200 bg-blue-50">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-blue-800">
                                    <strong>Pago registrado como anticipo completo.</strong><br>
                                    El cliente no tenía documentos pendientes al momento del pago.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t border-gray-200">
                        <Link :href="route('pagos.index')">
                            <SecondaryButton>Volver al Listado</SecondaryButton>
                        </Link>
                        
                        <div class="flex gap-3">
                            <!-- Botón Imprimir Recibo (CU-10 Paso 12 - Kendall) -->
                            <button
                                @click="imprimirRecibo"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition"
                                title="Imprimir Recibo"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Imprimir Recibo
                            </button>
                            
                            <DangerButton 
                                v-if="!pago.anulado" 
                                @click="anularPago"
                            >
                                Anular Pago
                            </DangerButton>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>