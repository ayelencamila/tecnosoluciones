<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; 
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    venta: Object, // Viene del VentaController@show con todas las relaciones cargadas
});

// Estado para el modal/formulario de anulación
const confirmingAnulacion = ref(false);
const formAnular = useForm({
    motivo_anulacion: '',
});

// Formateadores
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-AR', {
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

// Lógica de Anulación (CU-06)
const confirmAnulacion = () => {
    confirmingAnulacion.value = true;
};

const closeModal = () => {
    confirmingAnulacion.value = false;
    formAnular.reset();
};

const anularVenta = () => {
    // Usamos PUT o DELETE según tu ruta. Asumo PUT por ser una actualización de estado.
    formAnular.put(route('ventas.anular', props.venta.venta_id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => formAnular.reset(),
    });
};
</script>

<template>
    <Head :title="`Venta #${venta.numero_comprobante}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalle de Venta #{{ venta.numero_comprobante }}
                </h2>
                <Link :href="route('ventas.index')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                    &larr; Volver al Listado
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white shadow-sm sm:rounded-t-lg p-6 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <span class="text-gray-500 text-sm block">Fecha de Emisión</span>
                        <span class="font-bold text-gray-700">{{ formatDate(venta.fecha_venta) }}</span>
                    </div>
                    <div>
                        <span 
                            class="px-4 py-2 rounded-full text-sm font-bold"
                            :class="venta.anulada 
                                ? 'bg-red-100 text-red-800 border border-red-200' 
                                : 'bg-green-100 text-green-800 border border-green-200'"
                        >
                            {{ venta.anulada ? 'ANULADA' : 'COMPLETADA' }}
                        </span>
                    </div>
                </div>

                <div class="bg-white shadow-sm p-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-gray-50 p-4 rounded-md">
                            <h3 class="text-sm font-bold text-gray-500 uppercase mb-2">Cliente</h3>
                            <p class="text-lg font-medium text-gray-900">
                                {{ venta.cliente.nombre }} {{ venta.cliente.apellido }}
                            </p>
                            <p class="text-gray-600">DNI: {{ venta.cliente.DNI }}</p>
                            </div>
                        <div class="bg-gray-50 p-4 rounded-md">
                            <h3 class="text-sm font-bold text-gray-500 uppercase mb-2">Vendedor</h3>
                            <p class="text-lg font-medium text-gray-900">
                                {{ venta.vendedor?.name ?? 'Sistema' }}
                            </p>
                            <p class="text-gray-600 text-sm">ID Usuario: {{ venta.user_id }}</p>
                        </div>
                    </div>

                    <div v-if="venta.anulada" class="mb-8 bg-red-50 p-4 rounded-md border-l-4 border-red-500">
                        <h3 class="text-red-800 font-bold">Motivo de Anulación:</h3>
                        <p class="text-red-700 mt-1">{{ venta.motivo_anulacion }}</p>
                    </div>

                    <h3 class="text-lg font-medium text-gray-900 mb-4">Items de la Venta</h3>
                    <div class="overflow-x-auto border rounded-lg mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Precio Unit.</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Cant.</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Desc. Item</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="detalle in venta.detalles" :key="detalle.detalle_venta_id">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ detalle.producto?.nombre }} 
                                        <span class="text-gray-400 text-xs block">{{ detalle.producto?.codigo }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 text-right">
                                        {{ formatCurrency(detalle.precio_unitario) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-center font-medium">
                                        {{ parseFloat(detalle.cantidad) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-red-500 text-right">
                                        <span v-if="parseFloat(detalle.descuento_item) > 0">
                                            -{{ formatCurrency(detalle.descuento_item) }}
                                        </span>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right font-bold">
                                        {{ formatCurrency(detalle.subtotal_neto) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end">
                        <div class="w-full md:w-1/2 lg:w-1/3 space-y-2">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Subtotal Items:</span>
                                <span>{{ formatCurrency(venta.subtotal) }}</span>
                            </div>
                            
                            <div v-if="parseFloat(venta.total_descuentos) > 0" class="flex justify-between text-sm text-red-600 font-medium">
                                <span>Total Descuentos:</span>
                                <span>-{{ formatCurrency(venta.total_descuentos) }}</span>
                            </div>

                            <div class="flex justify-between text-xl font-bold text-gray-900 border-t pt-2 mt-2">
                                <span>TOTAL FINAL:</span>
                                <span>{{ formatCurrency(venta.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="venta.observaciones" class="mt-8 pt-4 border-t border-gray-100">
                        <h4 class="text-sm font-bold text-gray-500 mb-1">Observaciones:</h4>
                        <p class="text-gray-700 text-sm italic bg-yellow-50 p-3 rounded">
                            "{{ venta.observaciones }}"
                        </p>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 shadow-sm sm:rounded-b-lg flex justify-between items-center">
                    <SecondaryButton @click="() => window.print()">
                        🖨️ Imprimir Comprobante
                    </SecondaryButton>

                    <DangerButton 
                        v-if="!venta.anulada" 
                        @click="confirmAnulacion"
                        :disabled="formAnular.processing"
                    >
                        Anular Venta
                    </DangerButton>
                </div>
            </div>
        </div>

        <div v-if="confirmingAnulacion" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    ¿Estás seguro de que deseas anular esta venta?
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    Esta acción revertirá el stock y actualizará la cuenta corriente del cliente. Esta acción es irreversible.
                </p>

                <div class="mb-4">
                    <InputLabel for="motivo" value="Motivo de la anulación (Requerido)" />
                    <textarea
                        id="motivo"
                        v-model="formAnular.motivo_anulacion"
                        rows="3"
                        class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1"
                        placeholder="Ej: Error en la carga de items, devolución total..."
                    ></textarea>
                    <InputError :message="formAnular.errors.motivo_anulacion" class="mt-2" />
                </div>

                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="closeModal">
                        Cancelar
                    </SecondaryButton>
                    <DangerButton 
                        @click="anularVenta"
                        :class="{ 'opacity-25': formAnular.processing }"
                        :disabled="formAnular.processing || !formAnular.motivo_anulacion"
                    >
                        Confirmar Anulación
                    </DangerButton>
                </div>
            </div>
        </div>

    </AppLayout>
</template>