<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    comprobante: Object,
    datosEntidad: Object,
    urlImpresion: String,
    puedeAnularse: Boolean,
    puedeReemitirse: Boolean,
});

const mostrarModalAnular = ref(false);
const mostrarModalReemitir = ref(false);
const formAnular = useForm({ motivo: '' });
const formReemitir = useForm({ motivo: '' });

const anular = () => {
    formAnular.post(route('comprobantes.anular', props.comprobante.comprobante_id), {
        onSuccess: () => {
            mostrarModalAnular.value = false;
            formAnular.reset();
        },
    });
};

const reemitir = () => {
    formReemitir.post(route('comprobantes.reemitir', props.comprobante.comprobante_id), {
        onSuccess: () => {
            mostrarModalReemitir.value = false;
            formReemitir.reset();
        },
    });
};

const verComprobante = () => {
    if (props.urlImpresion) {
        window.open(props.urlImpresion, '_blank');
    }
};

const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const formatearMonto = (monto) => {
    if (!monto) return '-';
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
    }).format(monto);
};

const estadoClase = (nombre) => ({
    'EMITIDO': 'bg-green-100 text-green-800 border-green-200',
    'ANULADO': 'bg-red-100 text-red-800 border-red-200',
    'REEMPLAZADO': 'bg-yellow-100 text-yellow-800 border-yellow-200',
}[nombre] || 'bg-gray-100 text-gray-800 border-gray-200');

const tipoIcono = (codigo) => ({
    'TICKET': { bg: 'bg-blue-100', text: 'text-blue-600', icon: 'cart' },
    'RECIBO_PAGO': { bg: 'bg-green-100', text: 'text-green-600', icon: 'cash' },
    'INGRESO_REPARACION': { bg: 'bg-purple-100', text: 'text-purple-600', icon: 'wrench' },
    'ENTREGA_REPARACION': { bg: 'bg-orange-100', text: 'text-orange-600', icon: 'check' },
    'NOTA_CREDITO_INTERNA': { bg: 'bg-red-100', text: 'text-red-600', icon: 'minus' },
    'ORDEN_COMPRA': { bg: 'bg-indigo-100', text: 'text-indigo-600', icon: 'truck' },
}[codigo] || { bg: 'bg-gray-100', text: 'text-gray-600', icon: 'document' });
</script>

<template>
    <AppLayout title="Comprobante">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    <!-- Icono documento -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ comprobante.numero_correlativo }}
                </h2>
                <Link :href="route('comprobantes.index')" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver al listado
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Card principal -->
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Encabezado -->
                        <div class="flex justify-between items-start mb-6 pb-6 border-b">
                            <div class="flex items-start gap-4">
                                <div :class="['w-12 h-12 rounded-lg flex items-center justify-center', tipoIcono(comprobante.tipo_comprobante?.codigo).bg]">
                                    <!-- Icono según tipo -->
                                    <svg xmlns="http://www.w3.org/2000/svg" :class="['h-6 w-6', tipoIcono(comprobante.tipo_comprobante?.codigo).text]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900 font-mono">{{ comprobante.numero_correlativo }}</h3>
                                    <p class="text-gray-500">{{ comprobante.tipo_comprobante?.nombre }}</p>
                                </div>
                            </div>
                            <span :class="['px-3 py-1.5 text-sm font-medium rounded-full border', estadoClase(comprobante.estado_comprobante?.nombre)]">
                                {{ comprobante.estado_comprobante?.nombre }}
                            </span>
                        </div>

                        <!-- Datos del comprobante -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Fecha de Emisión</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatearFecha(comprobante.fecha_emision) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Emitido por</dt>
                                    <dd class="mt-1 text-gray-900">{{ comprobante.usuario?.name || 'Sistema' }}</dd>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tipo de Entidad</dt>
                                    <dd class="mt-1 text-gray-900">{{ datosEntidad?.tipo || 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">ID Entidad</dt>
                                    <dd class="mt-1 text-gray-900">#{{ datosEntidad?.id || 'N/A' }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Motivo si está anulado -->
                        <div v-if="comprobante.motivo_estado" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-red-800">Motivo de {{ comprobante.estado_comprobante?.nombre?.toLowerCase() }}:</p>
                                    <p class="mt-1 text-red-700">{{ comprobante.motivo_estado }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Comprobante original si es reemisión -->
                        <div v-if="comprobante.original" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <p class="text-sm text-blue-800">
                                    Este es una reemisión del comprobante: 
                                    <Link :href="route('comprobantes.show', comprobante.original.comprobante_id)" class="font-medium underline hover:text-blue-900">
                                        {{ comprobante.original.numero_correlativo }}
                                    </Link>
                                </p>
                            </div>
                        </div>

                        <!-- Reemisiones -->
                        <div v-if="comprobante.reemisiones?.length" class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <p class="text-sm font-medium text-gray-600 mb-2">Comprobantes de reemisión generados:</p>
                            <ul class="space-y-1">
                                <li v-for="r in comprobante.reemisiones" :key="r.comprobante_id" class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    <Link :href="route('comprobantes.show', r.comprobante_id)" class="text-indigo-600 hover:underline">
                                        {{ r.numero_correlativo }}
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <!-- Acciones -->
                        <div class="flex flex-wrap gap-2 pt-6 border-t">
                            <!-- Ver/Imprimir Comprobante -->
                            <button
                                v-if="urlImpresion"
                                @click="verComprobante"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded hover:bg-indigo-700 transition-colors"
                                title="Abre el comprobante en nueva pestaña para imprimir"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Imprimir
                            </button>
                            
                            <!-- Anular -->
                            <button
                                v-if="puedeAnularse"
                                @click="mostrarModalAnular = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                Anular
                            </button>
                            
                            <!-- Reemitir -->
                            <button
                                v-if="puedeReemitirse"
                                @click="mostrarModalReemitir = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Reemitir
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card Datos de la Entidad -->
                <div v-if="datosEntidad" class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Datos de {{ datosEntidad.tipo }}
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-if="datosEntidad.cliente">
                                <dt class="text-sm font-medium text-gray-500">Cliente</dt>
                                <dd class="mt-1 text-gray-900">{{ datosEntidad.cliente }}</dd>
                            </div>
                            <div v-if="datosEntidad.numero || datosEntidad.codigo">
                                <dt class="text-sm font-medium text-gray-500">{{ datosEntidad.tipo === 'Reparacion' ? 'Código' : 'Número' }}</dt>
                                <dd class="mt-1 text-gray-900 font-mono">{{ datosEntidad.numero || datosEntidad.codigo }}</dd>
                            </div>
                            <div v-if="datosEntidad.vendedor || datosEntidad.cajero || datosEntidad.tecnico">
                                <dt class="text-sm font-medium text-gray-500">{{ datosEntidad.tipo === 'Venta' ? 'Vendedor' : (datosEntidad.tipo === 'Pago' ? 'Cajero' : 'Técnico') }}</dt>
                                <dd class="mt-1 text-gray-900">{{ datosEntidad.vendedor || datosEntidad.cajero || datosEntidad.tecnico }}</dd>
                            </div>
                            <div v-if="datosEntidad.total || datosEntidad.monto || datosEntidad.costo_total">
                                <dt class="text-sm font-medium text-gray-500">Monto</dt>
                                <dd class="mt-1 text-gray-900 font-semibold">{{ formatearMonto(datosEntidad.total || datosEntidad.monto || datosEntidad.costo_total) }}</dd>
                            </div>
                            <div v-if="datosEntidad.estado">
                                <dt class="text-sm font-medium text-gray-500">Estado</dt>
                                <dd class="mt-1 text-gray-900">{{ datosEntidad.estado }}</dd>
                            </div>
                            <div v-if="datosEntidad.medio_pago">
                                <dt class="text-sm font-medium text-gray-500">Medio de Pago</dt>
                                <dd class="mt-1 text-gray-900">{{ datosEntidad.medio_pago }}</dd>
                            </div>
                        </div>

                        <!-- Link a entidad original -->
                        <div v-if="datosEntidad.ruta_ver" class="mt-4 pt-4 border-t">
                            <Link 
                                :href="datosEntidad.ruta_ver" 
                                class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Ver {{ datosEntidad.tipo }} completo/a
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Anular -->
        <Teleport to="body">
            <div v-if="mostrarModalAnular" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/50" @click="mostrarModalAnular = false"></div>
                <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Anular Comprobante</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Ingrese el motivo de anulación para <span class="font-mono font-semibold">{{ comprobante.numero_correlativo }}</span>:
                    </p>
                    <textarea
                        v-model="formAnular.motivo"
                        rows="3"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                        placeholder="Describa el motivo de anulación (mínimo 10 caracteres)"
                    ></textarea>
                    <p v-if="formAnular.errors.motivo" class="mt-1 text-sm text-red-600">{{ formAnular.errors.motivo }}</p>
                    <div class="flex justify-end gap-3 mt-4">
                        <button @click="mostrarModalAnular = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-md transition-colors">
                            Cancelar
                        </button>
                        <button
                            @click="anular"
                            :disabled="formAnular.processing || formAnular.motivo.length < 10"
                            class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Confirmar Anulación
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Reemitir -->
        <Teleport to="body">
            <div v-if="mostrarModalReemitir" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/50" @click="mostrarModalReemitir = false"></div>
                <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Reemitir Comprobante</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Ingrese el motivo de reemisión para <span class="font-mono font-semibold">{{ comprobante.numero_correlativo }}</span>:
                    </p>
                    <textarea
                        v-model="formReemitir.motivo"
                        rows="3"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Describa el motivo de reemisión (mínimo 10 caracteres)"
                    ></textarea>
                    <p v-if="formReemitir.errors.motivo" class="mt-1 text-sm text-red-600">{{ formReemitir.errors.motivo }}</p>
                    <p class="mt-2 text-xs text-gray-500">
                        Se generará un nuevo comprobante y el actual quedará marcado como REEMPLAZADO.
                    </p>
                    <div class="flex justify-end gap-3 mt-4">
                        <button @click="mostrarModalReemitir = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-md transition-colors">
                            Cancelar
                        </button>
                        <button
                            @click="reemitir"
                            :disabled="formReemitir.processing || formReemitir.motivo.length < 10"
                            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Confirmar Reemisión
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
