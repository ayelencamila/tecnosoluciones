<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AlertMessage from '@/Components/AlertMessage.vue'; 

const props = defineProps({
    pago: Object, // Viene del PagoController@show
});

// Estado para confirmación de anulación
const confirmingAnulacion = ref(false);
const formAnular = useForm({});

// Formateadores
const formatCurrency = (value) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
const formatDate = (dateString) => new Date(dateString).toLocaleDateString('es-AR', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });

// Acciones
const imprimir = () => window.print();

const anularPago = () => {
    // Llamada a la ruta DELETE que creamos en routes/web.php
    formAnular.delete(route('pagos.anular', props.pago.pago_id), {
        preserveScroll: true,
        onSuccess: () => confirmingAnulacion.value = false,
    });
};
</script>

<template>
    <Head :title="`Recibo ${pago.numero_recibo}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800">Recibo de Pago #{{ pago.numero_recibo }}</h2>
                <Link :href="route('pagos.index')" class="text-sm text-indigo-600 hover:underline">&larr; Volver al Listado</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Mensajes Flash -->
                <AlertMessage 
                    v-if="$page.props.flash?.error" 
                    type="error" 
                    :message="$page.props.flash.error"
                    class="mb-6" 
                />
                <AlertMessage 
                    v-if="$page.props.flash?.success" 
                    type="success" 
                    :message="$page.props.flash.success"
                    class="mb-6" 
                />

                <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
                    
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div>
                            <span class="block text-xs text-gray-500 uppercase tracking-wide">Fecha de Pago</span>
                            <span class="font-bold text-gray-800">{{ formatDate(pago.fecha_pago) }}</span>
                        </div>
                        <div v-if="pago.anulado" class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-bold border border-red-200">
                            ANULADO
                        </div>
                        <div v-else class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-bold border border-green-200">
                            CONFIRMADO
                        </div>
                    </div>

                    <div class="p-8">
                        <div class="text-center mb-8">
                            <span class="block text-sm text-gray-500 uppercase">Monto Recibido</span>
                            <span class="text-4xl font-black text-gray-900" :class="{'line-through text-red-300': pago.anulado}">
                                {{ formatCurrency(pago.monto) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                            <div class="border p-4 rounded bg-gray-50">
                                <h4 class="font-bold text-gray-700 mb-2 border-b pb-1">Cliente</h4>
                                <p class="text-lg">{{ pago.cliente.apellido }}, {{ pago.cliente.nombre }}</p>
                                <p class="text-gray-600">DNI: {{ pago.cliente.DNI }}</p>
                            </div>
                            <div class="border p-4 rounded bg-gray-50">
                                <h4 class="font-bold text-gray-700 mb-2 border-b pb-1">Detalles</h4>
                                <p><span class="font-semibold">Método:</span> <span class="capitalize">{{ pago.metodo_pago }}</span></p>
                                <p><span class="font-semibold">Cajero:</span> {{ pago.cajero?.name ?? 'Sistema' }}</p>
                                <p><span class="font-semibold">Recibo N°:</span> {{ pago.numero_recibo }}</p>
                            </div>
                        </div>

                        <div v-if="pago.observaciones" class="mt-6">
                            <h4 class="font-bold text-gray-700 mb-1">Observaciones:</h4>
                            <p class="text-gray-600 italic bg-yellow-50 p-3 rounded border border-yellow-100">
                                "{{ pago.observaciones }}"
                            </p>
                        </div>
                    </div>

                    <div class="bg-gray-100 px-6 py-4 flex justify-between items-center">
                        <SecondaryButton @click="imprimir">🖨️ Imprimir</SecondaryButton>
                        
                        <div v-if="!pago.anulado">
                            <DangerButton @click="confirmingAnulacion = true" :disabled="formAnular.processing">
                                Anular Pago
                            </DangerButton>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div v-if="confirmingAnulacion" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-2">¿Anular este pago?</h3>
                <p class="text-gray-600 text-sm mb-6">
                    Esta acción revertirá el dinero en la cuenta corriente del cliente. Esta acción no se puede deshacer.
                </p>
                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="confirmingAnulacion = false">Cancelar</SecondaryButton>
                    <DangerButton @click="anularPago" :class="{ 'opacity-25': formAnular.processing }" :disabled="formAnular.processing">
                        Confirmar Anulación
                    </DangerButton>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
<style>
@media print {
    /* Ocultar todo lo que no sea el recibo */
    body * {
        visibility: hidden;
    }
    
    /* Ocultar la barra de navegación, sidebar y botones */
    nav, header, .bg-gray-100.px-6.py-4 { 
        display: none !important; 
    }

    /* Hacer visible solo la tarjeta del recibo */
    .max-w-3xl, .max-w-3xl * {
        visibility: visible;
    }

    /* Posicionar el recibo arriba de todo en la hoja */
    .max-w-3xl {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        box-shadow: none; /* Quitar sombras para ahorrar tinta */
        border: 1px solid #ccc;
    }
    
    /* Asegurar textos en negro para impresoras monocromáticas */
    .text-gray-500, .text-gray-600 {
        color: #000 !important;
    }
}
</style>