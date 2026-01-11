<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import AlertMessage from '@/Components/AlertMessage.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    oferta: Object, // Incluye relaciones: proveedor, detalles.producto, estado, user
});

// Modal de rechazo
const showRechazarModal = ref(false);
const formRechazar = useForm({
    motivo: '',
});

// Elegir oferta (CU-21 Paso 12)
const elegirOferta = () => {
    if (confirm('¿Confirma elegir esta oferta? Podrá generar la Orden de Compra después.')) {
        router.post(route('ofertas.elegir', props.oferta.id), {}, {
            preserveScroll: true,
        });
    }
};

// Rechazar oferta
const rechazarOferta = () => {
    formRechazar.post(route('ofertas.rechazar', props.oferta.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRechazarModal.value = false;
            formRechazar.reset();
        },
    });
};

// Generar OC (CU-22 - pendiente)
const generarOrdenCompra = () => {
    if (confirm('¿Estás seguro de que deseas generar la Orden de Compra para esta oferta?')) {
        // TODO: Implementar en CU-22
        alert('Funcionalidad CU-22 pendiente de implementación.');
    }
};

// Helper para clases de estado (Feedback visual)
const estadoClass = (estado) => {
    switch (estado) {
        case 'Pendiente': return 'bg-yellow-100 text-yellow-800';
        case 'Pre-aprobada': return 'bg-blue-100 text-blue-800';
        case 'Elegida': return 'bg-green-100 text-green-800';
        case 'Procesada': return 'bg-indigo-100 text-indigo-800';
        case 'Rechazada': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head :title="`Oferta ${oferta.codigo_oferta}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalle de Oferta: {{ oferta.codigo_oferta }}
                </h2>
                <div class="space-x-2">
                    <Link :href="route('ofertas.index')" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                        &larr; Volver al listado
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <AlertMessage v-if="$page.props.flash.success" :message="$page.props.flash.success" type="success" />

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ oferta.proveedor.razon_social }}</h3>
                            <p class="text-sm text-gray-500">CUIT: {{ oferta.proveedor.cuit }}</p>
                            <div class="mt-2 flex items-center space-x-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="estadoClass(oferta.estado.nombre)">
                                    {{ oferta.estado.nombre }}
                                </span>
                                <span class="text-sm text-gray-600">
                                    Fecha: {{ new Date(oferta.fecha_recepcion).toLocaleDateString() }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total Estimado</p>
                            <p class="text-2xl font-bold text-gray-800">
                                ${{ Number(oferta.total_estimado).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs font-medium text-gray-500 uppercase">Observaciones</span>
                            <p class="mt-1 text-sm text-gray-900">{{ oferta.observaciones || 'Sin observaciones.' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-gray-500 uppercase">Archivo Adjunto</span>
                            <div class="mt-1">
                                <a v-if="oferta.archivo_adjunto" 
                                   :href="`/storage/${oferta.archivo_adjunto}`" 
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center text-indigo-600 hover:text-indigo-900 text-sm"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                    Ver Documento
                                </a>
                                <span v-else class="text-sm text-gray-400 italic">No hay archivo adjunto.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Productos Cotizados</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Unit.</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disponibilidad</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="detalle in oferta.detalles" :key="detalle.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ detalle.producto.nombre }}
                                        <span class="text-gray-500 text-xs block">{{ detalle.producto.codigo }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ detalle.cantidad_ofrecida }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        ${{ Number(detalle.precio_unitario).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-right">
                                        ${{ (detalle.cantidad_ofrecida * detalle.precio_unitario).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span v-if="detalle.disponibilidad_inmediata" class="text-green-600 font-semibold flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Inmediata
                                        </span>
                                        <span v-else class="text-orange-600 font-medium">
                                            {{ detalle.dias_entrega }} días de espera
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end space-x-4" v-if="oferta.estado.nombre !== 'Procesada' && oferta.estado.nombre !== 'Rechazada'">
                    <!-- Botón Rechazar (Para Pendiente, Pre-aprobada, Elegida) -->
                    <DangerButton 
                        v-if="oferta.estado.nombre !== 'Rechazada'"
                        @click="showRechazarModal = true">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Rechazar Oferta
                    </DangerButton>

                    <!-- Botón Elegir (Solo para Pendiente o Pre-aprobada) -->
                    <PrimaryButton 
                        v-if="oferta.estado.nombre === 'Pendiente' || oferta.estado.nombre === 'Pre-aprobada'"
                        @click="elegirOferta"
                        class="bg-green-600 hover:bg-green-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Elegir Oferta
                    </PrimaryButton>
                    
                    <!-- Botón Generar OC (Solo si ya está Elegida) -->
                    <PrimaryButton 
                        v-if="oferta.estado.nombre === 'Elegida'"
                        @click="generarOrdenCompra">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Generar Orden de Compra
                    </PrimaryButton>
                </div>

                <!-- Estado informativo si ya procesada o rechazada -->
                <div v-else class="flex justify-end">
                    <span v-if="oferta.estado.nombre === 'Procesada'" 
                          class="inline-flex items-center px-4 py-2 text-sm font-semibold text-indigo-700 bg-indigo-100 rounded-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Oferta procesada - OC generada
                    </span>
                    <span v-else-if="oferta.estado.nombre === 'Rechazada'" 
                          class="inline-flex items-center px-4 py-2 text-sm font-semibold text-red-700 bg-red-100 rounded-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        Oferta rechazada
                    </span>
                </div>

            </div>
        </div>

        <!-- Modal de Rechazo -->
        <Modal :show="showRechazarModal" @close="showRechazarModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Rechazar Oferta {{ oferta.codigo_oferta }}
                </h2>
                <p class="text-sm text-gray-600 mb-4">
                    Por favor, indique el motivo del rechazo. Esta acción quedará registrada en auditoría.
                </p>
                
                <div class="mb-4">
                    <InputLabel for="motivo" value="Motivo del rechazo *" />
                    <textarea
                        id="motivo"
                        v-model="formRechazar.motivo"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        rows="3"
                        placeholder="Ej: Precio muy elevado, condiciones no aceptables, etc."
                    ></textarea>
                    <InputError :message="formRechazar.errors.motivo" class="mt-2" />
                </div>

                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="showRechazarModal = false">
                        Cancelar
                    </SecondaryButton>
                    <DangerButton 
                        @click="rechazarOferta"
                        :disabled="formRechazar.processing || !formRechazar.motivo">
                        <span v-if="formRechazar.processing">Procesando...</span>
                        <span v-else>Confirmar Rechazo</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>