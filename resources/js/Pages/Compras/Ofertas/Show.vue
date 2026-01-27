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

// Modal de generar OC (CU-22)
const showGenerarOCModal = ref(false);
const formGenerarOC = useForm({
    oferta_id: props.oferta.id,
    observaciones: '',
});

// Vista de Resultado/Estado (CU-21 Paso 14 / Kendall: Vista de consulta, NO de acción)
// Elegir oferta se hace en ConfirmarSeleccion.vue (Vista de Control separada)
const irAConfirmarSeleccion = () => {
    router.get(route('ofertas.confirmar-seleccion', props.oferta.id));
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

// Generar OC (CU-22)
const generarOrdenCompra = () => {
    formGenerarOC.post(route('ordenes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showGenerarOCModal.value = false;
            formGenerarOC.reset('observaciones');
        },
    });
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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Gestión de Compras
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Breadcrumb y botón volver -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                            GESTIÓN DE COMPRAS &gt; OFERTAS &gt; DETALLE
                        </p>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                            DETALLE DE OFERTA: {{ oferta.codigo_oferta }}
                        </h1>
                    </div>
                    <Link :href="route('ofertas.index')" 
                          class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">
                        Volver
                    </Link>
                </div>
                
                <AlertMessage v-if="$page.props.flash.success" :message="$page.props.flash.success" type="success" />

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="flex-shrink-0 h-12 w-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ oferta.proveedor.razon_social }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">CUIT: {{ oferta.proveedor.cuit }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="estadoClass(oferta.estado.nombre)">
                                    {{ oferta.estado.nombre }}
                                </span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    Fecha: {{ new Date(oferta.fecha_recepcion).toLocaleDateString('es-AR') }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Estimado</p>
                            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                                ${{ Number(oferta.total_estimado).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs font-medium text-gray-500 uppercase">Motivo del Registro (CU-21 Paso 7)</span>
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

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                        Productos Cotizados
                    </h3>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">Cantidad</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">Precio Unit.</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">Subtotal</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Disponibilidad</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="detalle in oferta.detalles" :key="detalle.id" class="hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ detalle.producto.nombre }}</div>
                                        <span class="text-gray-500 dark:text-gray-400 text-xs block">{{ detalle.producto.codigo }}</span>
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

                    <!-- CU-21: Botón para ir a Vista de Confirmación (Kendall: Separación de vistas) -->
                    <PrimaryButton 
                        v-if="oferta.estado.nombre === 'Pendiente' || oferta.estado.nombre === 'Pre-aprobada'"
                        @click="irAConfirmarSeleccion"
                        class="bg-green-600 hover:bg-green-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Seleccionar esta Oferta
                    </PrimaryButton>
                    
                    <!-- CU-22: Botón Generar OC (Solo si ya está Elegida) -->
                    <Link 
                        v-if="oferta.estado.nombre === 'Elegida' || oferta.estado.nombre === 'Pre-aprobada'"
                        :href="route('ordenes.create', { oferta_id: oferta.id })">
                        <PrimaryButton>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Generar Orden de Compra
                        </PrimaryButton>
                    </Link>
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

        <!-- Modal Generar Orden de Compra (CU-22) -->
        <Modal :show="showGenerarOCModal" @close="showGenerarOCModal = false" max-width="lg">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Generar Orden de Compra
                        </h2>
                        <p class="text-sm text-gray-500">
                            Oferta: {{ oferta.codigo_oferta }} | Proveedor: {{ oferta.proveedor.razon_social }}
                        </p>
                    </div>
                </div>

                <!-- Resumen de la oferta -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Total:</span>
                            <span class="font-bold text-gray-900 ml-2">
                                ${{ Number(oferta.total_estimado).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Productos:</span>
                            <span class="font-medium text-gray-900 ml-2">{{ oferta.detalles.length }} ítems</span>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <InputLabel for="observaciones" value="Instrucciones para el proveedor *" />
                    <textarea
                        id="observaciones"
                        v-model="formGenerarOC.observaciones"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        rows="4"
                        placeholder="Ej: Entregar en depósito central. Horario de recepción: 9 a 17hs. Incluir remito..."
                    ></textarea>
                    <InputError :message="formGenerarOC.errors.observaciones" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500">
                        Mínimo 10 caracteres. Estas instrucciones se incluirán en el PDF y WhatsApp enviado al proveedor.
                    </p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-blue-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-blue-700">
                            <p class="font-medium">Al confirmar se ejecutarán las siguientes acciones:</p>
                            <ul class="mt-1 list-disc list-inside">
                                <li>Se generará la Orden de Compra en PDF</li>
                                <li>Se enviará WhatsApp al proveedor con la OC</li>
                                <li>Recibirás un email de confirmación</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="showGenerarOCModal = false">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton 
                        @click="generarOrdenCompra"
                        :disabled="formGenerarOC.processing || formGenerarOC.observaciones.length < 10">
                        <svg v-if="formGenerarOC.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-if="formGenerarOC.processing">Generando OC...</span>
                        <span v-else>Confirmar y Generar OC</span>
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>