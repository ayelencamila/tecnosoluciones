<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    oferta: Object, // Oferta a elegir
});

// Formulario de confirmación
const form = useForm({
    confirmar: false,
});

// Confirmar selección (CU-21 Paso 12)
const confirmarSeleccion = () => {
    form.post(route('ofertas.elegir', props.oferta.id), {
        preserveScroll: true,
    });
};

// Helper para formatear moneda
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { 
        style: 'currency', 
        currency: 'ARS',
        minimumFractionDigits: 2 
    }).format(value);
};
</script>

<template>
    <Head :title="`Confirmar Selección - ${oferta.codigo_oferta}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    CU-21 Paso 12: Confirmar Selección de Oferta
                </h2>
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <!-- Banner de advertencia -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 mb-6 rounded-r-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-yellow-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <h3 class="text-yellow-800 font-semibold text-lg">⚠️ Confirmación Requerida</h3>
                        <p class="text-yellow-700 mt-2">
                            Está a punto de <strong>marcar esta oferta como "Elegida"</strong>. Esta acción:
                        </p>
                        <ul class="text-yellow-700 mt-2 ml-4 space-y-1 text-sm">
                            <li>✓ Cambiará el estado de la oferta a <strong>"Elegida"</strong></li>
                            <li>✓ Permitirá generar la Orden de Compra (CU-22)</li>
                            <li>✓ Quedará registrada en el historial de auditoría</li>
                            <li>⚠️ Las demás ofertas para los mismos productos quedarán descartadas</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Resumen de la Oferta a Elegir -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                    <h3 class="text-white font-semibold text-lg">Resumen de Oferta Seleccionada</h3>
                </div>
                
                <div class="p-6 space-y-4">
                    <!-- Datos principales -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <span class="block text-xs font-medium text-gray-500 uppercase mb-1">Código</span>
                            <p class="text-lg font-bold text-gray-900">{{ oferta.codigo_oferta }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-gray-500 uppercase mb-1">Estado Actual</span>
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                {{ oferta.estado.nombre }}
                            </span>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <span class="block text-xs font-medium text-gray-500 uppercase mb-1">Proveedor</span>
                                <p class="text-base font-semibold text-gray-900">{{ oferta.proveedor.razon_social }}</p>
                                <p class="text-sm text-gray-500">CUIT: {{ oferta.proveedor.cuit }}</p>
                            </div>
                            <div>
                                <span class="block text-xs font-medium text-gray-500 uppercase mb-1">Total Estimado</span>
                                <p class="text-2xl font-bold text-indigo-600">
                                    {{ formatCurrency(oferta.total_estimado) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <span class="block text-xs font-medium text-gray-500 uppercase mb-2">Productos Incluidos</span>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <ul class="space-y-2">
                                <li v-for="detalle in oferta.detalles" :key="detalle.id" 
                                    class="flex items-center justify-between text-sm">
                                    <span class="text-gray-700">
                                        <strong>{{ detalle.producto.nombre }}</strong>
                                        <span class="text-gray-500 ml-2">(x{{ detalle.cantidad_ofrecida }})</span>
                                    </span>
                                    <span class="font-semibold text-gray-900">
                                        {{ formatCurrency(detalle.precio_unitario * detalle.cantidad_ofrecida) }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <span class="block text-xs font-medium text-gray-500 uppercase mb-1">Motivo del Registro (CU-21 Paso 7)</span>
                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded">
                            {{ oferta.observaciones || 'Sin observaciones.' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Vista de Control: Confirmar o Cancelar (Kendall) -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">¿Confirma la selección de esta oferta?</h3>
                
                <div class="flex items-center space-x-3 mb-6">
                    <input 
                        type="checkbox" 
                        id="confirmar"
                        v-model="form.confirmar"
                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                    />
                    <label for="confirmar" class="text-sm text-gray-700">
                        He revisado los datos y confirmo que esta es la mejor propuesta para generar la Orden de Compra
                    </label>
                </div>

                <div class="flex items-center justify-between pt-4 border-t">
                    <Link :href="route('ofertas.show', oferta.id)" class="inline-flex items-center text-gray-600 hover:text-gray-900 text-sm font-medium">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Volver al detalle
                    </Link>

                    <div class="flex items-center space-x-3">
                        <Link :href="route('ofertas.index')">
                            <SecondaryButton>
                                Cancelar
                            </SecondaryButton>
                        </Link>
                        
                        <PrimaryButton 
                            @click="confirmarSeleccion"
                            :disabled="!form.confirmar || form.processing"
                            :class="{ 'opacity-50 cursor-not-allowed': !form.confirmar || form.processing }"
                            class="bg-green-600 hover:bg-green-700"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span v-if="form.processing">Procesando...</span>
                            <span v-else>Confirmar y Elegir Oferta</span>
                        </PrimaryButton>
                    </div>
                </div>
            </div>

            <!-- Nota informativa -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-800">
                    <strong>Nota:</strong> Después de confirmar, podrá acceder a la opción "Generar Orden de Compra" desde el detalle de la oferta (CU-21 Paso 14 → CU-22).
                </p>
            </div>
        </div>
    </AppLayout>
</template>
