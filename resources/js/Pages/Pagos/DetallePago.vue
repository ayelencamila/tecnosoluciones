<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    pago: Object, // Incluye cliente, cajero y ventasImputadas
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-AR', {
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const formAnular = useForm({});

const anularPago = () => {
    if (confirm('¿Está seguro de anular este pago? Se revertirá el saldo de la cuenta corriente.')) {
        formAnular.delete(route('pagos.anular', props.pago.pago_id));
    }
};
</script>

<template>
    <Head :title="`Recibo ${pago.numero_recibo}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalle de Pago
                </h2>
                <Link 
                    :href="route('pagos.index')" 
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none transition ease-in-out duration-150"
                >
                    &larr; Volver al Listado
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white shadow-xl sm:rounded-lg overflow-hidden border border-gray-200">
                    
                    <div class="bg-gray-50 px-8 py-6 border-b border-gray-200 flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Recibo de Pago</h1>
                            <p class="text-sm text-gray-500 mt-1">N° {{ pago.numero_recibo }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Fecha</p>
                            <p class="font-bold text-gray-800">{{ formatDate(pago.fecha_pago) }}</p>
                            <div v-if="pago.anulado" class="mt-2">
                                <span class="px-3 py-1 text-xs font-bold text-red-800 bg-red-100 rounded-full border border-red-200">
                                    ANULADO
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-8">
                        
                        <div class="flex justify-between items-center mb-8 p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                            <div>
                                <p class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Recibimos de</p>
                                <p class="text-xl font-bold text-indigo-900">{{ pago.cliente.nombre }} {{ pago.cliente.apellido }}</p>
                                <p class="text-sm text-indigo-700">DNI: {{ pago.cliente.DNI }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-bold text-indigo-400 uppercase tracking-wider">La suma de</p>
                                <p class="text-3xl font-extrabold text-indigo-600">{{ formatCurrency(pago.monto) }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
                            <div>
                                <span class="block text-gray-500 font-bold">Método de Pago:</span>
                                <span class="capitalize">{{ pago.metodo_pago }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-500 font-bold">Cajero:</span>
                                <span>{{ pago.cajero?.name || 'Sistema' }}</span>
                            </div>
                            <div class="col-span-2" v-if="pago.observaciones">
                                <span class="block text-gray-500 font-bold">Observaciones:</span>
                                <span class="italic text-gray-700">{{ pago.observaciones }}</span>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Imputación de Comprobantes</h3>
                            
                            <div v-if="pago.ventas_imputadas && pago.ventas_imputadas.length > 0" class="overflow-hidden border rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Comprobante</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha Venta</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Venta</th>
                                            <th class="px-4 py-2 text-right text-xs font-bold text-gray-700 uppercase bg-gray-100">Imputado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="venta in pago.ventas_imputadas" :key="venta.venta_id">
                                            <td class="px-4 py-2 text-sm font-medium text-indigo-600">
                                                <Link :href="route('ventas.show', venta.venta_id)" class="hover:underline">
                                                    {{ venta.numero_comprobante }}
                                                </Link>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-500">
                                                {{ formatDate(venta.fecha_venta).split(',')[0] }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-500 text-right">
                                                {{ formatCurrency(venta.total) }}
                                            </td>
                                            <td class="px-4 py-2 text-sm font-bold text-gray-900 text-right bg-gray-50">
                                                {{ formatCurrency(venta.pivot.monto_imputado) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-4 text-gray-500 italic bg-gray-50 rounded-lg">
                                Este pago quedó como saldo a favor (Anticipo) o no se imputó a ninguna venta específica.
                            </div>
                        </div>

                    </div>

                    <div class="bg-gray-50 px-8 py-4 border-t border-gray-200 flex justify-between items-center">
                        <SecondaryButton @click="() => window.print()">
                            🖨️ Imprimir Recibo
                        </SecondaryButton>

                        <DangerButton 
                            v-if="!pago.anulado" 
                            @click="anularPago" 
                            :class="{ 'opacity-25': formAnular.processing }" 
                            :disabled="formAnular.processing"
                        >
                            Anular Pago
                        </DangerButton>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>