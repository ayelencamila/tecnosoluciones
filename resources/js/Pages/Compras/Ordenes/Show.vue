<script setup>
/**
 * CU-22: Detalle de Orden de Compra
 * 
 * Vista que muestra todos los detalles de una OC generada,
 * con acciones para reenviar WhatsApp, regenerar PDF, confirmar, etc.
 */
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import AlertMessage from '@/Components/AlertMessage.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    orden: Object, // Incluye: proveedor, oferta.solicitud, detalles.producto, estado, usuario
});

// Modal de cancelación
const showCancelarModal = ref(false);
const formCancelar = useForm({
    motivo: '',
});

// Estados de carga para acciones
const procesando = ref({
    whatsapp: false,
    email: false,
    pdf: false,
    confirmar: false,
});

// Helper para clases de estado
const estadoClass = (estado) => {
    if (!estado) return 'bg-gray-100 text-gray-800';
    switch (estado.nombre) {
        case 'Borrador': return 'bg-gray-100 text-gray-800';
        case 'Enviada': return 'bg-blue-100 text-blue-800';
        case 'Envío Fallido': return 'bg-red-100 text-red-800';
        case 'Confirmada': return 'bg-green-100 text-green-800';
        case 'Recibida Parcial': return 'bg-yellow-100 text-yellow-800';
        case 'Recibida Total': return 'bg-emerald-100 text-emerald-800';
        case 'Cancelada': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Formatear fecha
const formatFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Formatear moneda
const formatMoneda = (valor) => {
    return Number(valor).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2,
    });
};

// Acciones
const reenviarWhatsApp = () => {
    if (confirm('¿Reenviar la orden por WhatsApp al proveedor?')) {
        procesando.value.whatsapp = true;
        router.post(route('ordenes.reenviar-whatsapp', props.orden.id), {}, {
            preserveScroll: true,
            onFinish: () => procesando.value.whatsapp = false,
        });
    }
};

const reenviarEmail = () => {
    if (confirm('¿Reenviar la orden por Email al proveedor?')) {
        procesando.value.email = true;
        router.post(route('ordenes.reenviar-email', props.orden.id), {}, {
            preserveScroll: true,
            onFinish: () => procesando.value.email = false,
        });
    }
};

const regenerarPdf = () => {
    if (confirm('¿Regenerar el PDF de esta orden?')) {
        procesando.value.pdf = true;
        router.post(route('ordenes.regenerar-pdf', props.orden.id), {}, {
            preserveScroll: true,
            onFinish: () => procesando.value.pdf = false,
        });
    }
};

const confirmarOrden = () => {
    if (confirm('¿Marcar esta orden como confirmada por el proveedor?')) {
        procesando.value.confirmar = true;
        router.post(route('ordenes.confirmar', props.orden.id), {}, {
            preserveScroll: true,
            onFinish: () => procesando.value.confirmar = false,
        });
    }
};

const cancelarOrden = () => {
    formCancelar.post(route('ordenes.cancelar', props.orden.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCancelarModal.value = false;
            formCancelar.reset();
        },
    });
};

// Verificar si se pueden hacer acciones
const puedeReenviar = () => {
    const estado = props.orden.estado?.nombre;
    return ['Borrador', 'Enviada', 'Envío Fallido'].includes(estado);
};

const puedeConfirmar = () => {
    const estado = props.orden.estado?.nombre;
    return estado === 'Enviada';
};

const puedeCancelar = () => {
    const estado = props.orden.estado?.nombre;
    return !['Cancelada', 'Recibida Parcial', 'Recibida Total'].includes(estado);
};

// CU-23: Verificar si se puede recepcionar mercadería
const puedeRecepcionar = () => {
    const estado = props.orden.estado?.nombre;
    // Solo se puede recepcionar si está Confirmada (Aprobada) o Recibida Parcial
    return ['Confirmada', 'Recibida Parcial'].includes(estado);
};
</script>

<template>
    <Head :title="`OC ${orden.numero_oc}`" />

    <AppLayout>
        <!-- HEADER -->
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Gestión de Compras
                    </h2>
                    <p class="text-sm font-medium text-indigo-800 dark:text-indigo-300 tracking-wide mt-1">
                        Órdenes › Detalle de Orden de Compra
                    </p>
                </div>
                <Link :href="route('ordenes.consulta')" 
                      class="px-5 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Título con número de OC -->
                <div class="mb-2">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        ORDEN DE COMPRA: {{ orden.numero_oc }}
                    </h1>
                </div>
                
                <AlertMessage v-if="$page.props.flash.success" :message="$page.props.flash.success" type="success" />
                <AlertMessage v-if="$page.props.flash.error" :message="$page.props.flash.error" type="error" />

                <!-- Alerta si envío fallido -->
                <div v-if="orden.estado?.nombre === 'Envío Fallido'" 
                     class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>El envío de esta orden falló.</strong> 
                                Use los botones de reenvío para intentar nuevamente.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Barra de acciones -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <Link :href="route('ordenes.index')" 
                              class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">
                            Volver
                        </Link>
                        <div class="flex flex-wrap gap-2">
                            <!-- Descargar PDF -->
                            <a v-if="orden.archivo_pdf" 
                               :href="route('ordenes.descargar-pdf', orden.id)"
                               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Descargar PDF
                            </a>
                            
                            <!-- Reenviar WhatsApp -->
                            <SecondaryButton v-if="puedeReenviar()" 
                                           @click="reenviarWhatsApp" 
                                           :disabled="procesando.whatsapp">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                </svg>
                                {{ procesando.whatsapp ? 'Enviando...' : 'Reenviar WhatsApp' }}
                            </SecondaryButton>

                            <!-- Reenviar Email -->
                            <SecondaryButton v-if="puedeReenviar()" 
                                           @click="reenviarEmail" 
                                           :disabled="procesando.email">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                {{ procesando.email ? 'Enviando...' : 'Reenviar Email' }}
                            </SecondaryButton>

                            <!-- Confirmar -->
                            <PrimaryButton v-if="puedeConfirmar()" 
                                         @click="confirmarOrden" 
                                         :disabled="procesando.confirmar">
                                {{ procesando.confirmar ? 'Confirmando...' : 'Confirmar Recepción' }}
                            </PrimaryButton>

                            <!-- CU-23: Recepcionar Mercadería -->
                            <Link v-if="puedeRecepcionar()"
                                  :href="route('recepciones.create', { orden_compra_id: orden.id })"
                                  class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                Recepcionar Mercadería
                            </Link>

                            <!-- Cancelar -->
                            <DangerButton v-if="puedeCancelar()" @click="showCancelarModal = true">
                                Cancelar OC
                            </DangerButton>
                        </div>
                    </div>
                </div>

                <!-- Cabecera de la orden -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center space-x-3">
                                <h3 class="text-2xl font-bold text-gray-900">{{ orden.numero_oc }}</h3>
                                <span class="px-3 py-1 text-sm font-semibold rounded-full" :class="estadoClass(orden.estado)">
                                    {{ orden.estado?.nombre || 'Sin estado' }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">
                                Generada por: {{ orden.usuario?.name || 'Sistema' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total</p>
                            <p class="text-3xl font-bold text-gray-800">
                                {{ formatMoneda(orden.total_final) }}
                            </p>
                        </div>
                    </div>

                    <!-- Fechas -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 border-t pt-4">
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase">Fecha Emisión</span>
                            <p class="mt-1 text-sm text-gray-900">{{ formatFecha(orden.fecha_emision) }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase">Fecha Envío</span>
                            <p class="mt-1 text-sm text-gray-900">{{ formatFecha(orden.fecha_envio) }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase">Fecha Confirmación</span>
                            <p class="mt-1 text-sm text-gray-900">{{ formatFecha(orden.fecha_confirmacion) }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-gray-500 uppercase">Ref. Oferta</span>
                            <p class="mt-1 text-sm">
                                <Link v-if="orden.oferta" :href="route('ofertas.show', orden.oferta.id)"
                                      class="text-indigo-600 hover:text-indigo-900">
                                    {{ orden.oferta.codigo_oferta || `#${orden.oferta.id}` }}
                                </Link>
                                <span v-else class="text-gray-400">-</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Información del proveedor -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Proveedor
                        </h4>
                        <div class="space-y-2 text-sm">
                            <p><strong class="text-gray-700">Razón Social:</strong> {{ orden.proveedor?.razon_social }}</p>
                            <p><strong class="text-gray-700">CUIT:</strong> {{ orden.proveedor?.cuit || '-' }}</p>
                            <p><strong class="text-gray-700">Teléfono:</strong> {{ orden.proveedor?.telefono || '-' }}</p>
                            <p><strong class="text-gray-700">WhatsApp:</strong> {{ orden.proveedor?.whatsapp || '-' }}</p>
                            <p><strong class="text-gray-700">Email:</strong> {{ orden.proveedor?.email || '-' }}</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Observaciones
                        </h4>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">
                            {{ orden.observaciones || 'Sin observaciones.' }}
                        </p>
                    </div>
                </div>

                <!-- Tabla de productos -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                        Productos Solicitados
                    </h4>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">Cantidad</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">Precio Unit.</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="(detalle, index) in orden.detalles" :key="detalle.id" class="hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ detalle.producto?.nombre || `Producto #${detalle.producto_id}` }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ detalle.producto?.codigo }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ detalle.cantidad_pedida }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ formatMoneda(detalle.precio_unitario) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-right">
                                        {{ formatMoneda(detalle.cantidad_pedida * detalle.precio_unitario) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-right text-sm font-bold text-gray-900 uppercase">
                                        Total
                                    </td>
                                    <td class="px-6 py-4 text-right text-lg font-bold text-gray-900">
                                        {{ formatMoneda(orden.total_final) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- CU-24: Historial de Recepciones -->
                <div v-if="orden.recepciones && orden.recepciones.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        Historial de Recepciones
                    </h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Recepción</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recibido por</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="recepcion in orden.recepciones" :key="recepcion.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ recepcion.numero_recepcion }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatFecha(recepcion.fecha_recepcion) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span :class="recepcion.tipo === 'total' 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-yellow-100 text-yellow-800'"
                                            class="px-2 py-1 text-xs font-medium rounded-full capitalize">
                                            {{ recepcion.tipo }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ recepcion.usuario?.name || '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <Link :href="route('recepciones.show', recepcion.id)" 
                                              class="text-indigo-600 hover:text-indigo-900 text-sm">
                                            Ver detalle
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal de Cancelación -->
        <Modal :show="showCancelarModal" @close="showCancelarModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Cancelar Orden {{ orden.numero_oc }}
                </h2>
                <p class="text-sm text-gray-600 mb-4">
                    Esta acción cancelará la orden de compra. Por favor, indique el motivo.
                </p>
                
                <div class="mb-4">
                    <InputLabel for="motivo" value="Motivo de cancelación *" />
                    <textarea
                        id="motivo"
                        v-model="formCancelar.motivo"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        rows="3"
                        placeholder="Ej: Proveedor no puede cumplir con los plazos, se encontró mejor oferta, etc."
                    ></textarea>
                    <InputError :message="formCancelar.errors.motivo" class="mt-2" />
                </div>

                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="showCancelarModal = false">
                        Volver
                    </SecondaryButton>
                    <DangerButton 
                        @click="cancelarOrden"
                        :disabled="formCancelar.processing || !formCancelar.motivo">
                        <span v-if="formCancelar.processing">Procesando...</span>
                        <span v-else>Confirmar Cancelación</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
