<script setup>
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

// --- ACCIÓN DE ANULAR ---
const anularPago = () => {
    if (confirm('¿Estás seguro de que deseas anular este pago? Esta acción revertirá el saldo en la cuenta corriente.')) {
        // CORRECCIÓN AQUÍ: Pasamos el ID del pago (pago.pagoID) a la ruta
        router.delete(route('pagos.anular', props.pago.pagoID));
    }
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
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t border-gray-200">
                        <Link :href="route('pagos.index')">
                            <SecondaryButton>Volver al Listado</SecondaryButton>
                        </Link>
                        
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
    </AppLayout>
</template>