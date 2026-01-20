<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    oferta: Object,
    resultado: {
        type: Object,
        default: null
    }
});

const showConfirmModal = ref(false);
const MAX_CARACTERES = 500;
const IVA_RATE = 0.21;

// Estados condicionales (Kendall: una vista, múltiples estados)
const mostrarFormulario = computed(() => !props.resultado);
const mostrarResultado = computed(() => !!props.resultado);

// Cálculos financieros (K&K Cap. 12: mostrar desglose completo)
const subtotal = computed(() => {
    if (!props.oferta?.detalles) return 0;
    return props.oferta.detalles.reduce((sum, d) => sum + (d.cantidad_ofrecida * d.precio_unitario), 0);
});

const impuestos = computed(() => subtotal.value * IVA_RATE);
const totalFinal = computed(() => subtotal.value + impuestos.value);

// Caracteres restantes para el motivo
const caracteresRestantes = computed(() => MAX_CARACTERES - form.observaciones.length);

const form = useForm({
    oferta_id: props.oferta?.id,
    observaciones: '',
});

const submit = () => {
    if (!form.observaciones || form.observaciones.trim().length < 10) {
        form.setError('observaciones', 'El motivo debe tener al menos 10 caracteres para auditoría.');
        return;
    }
    showConfirmModal.value = true;
};

const confirmarGeneracion = () => {
    form.post(route('ordenes.store'), {
        onSuccess: () => {
            showConfirmModal.value = false;
        },
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

const irAOrden = () => {
    router.visit(route('ordenes.show', props.resultado.orden.id));
};

const irAListado = () => {
    router.visit(route('ordenes.index'));
};

// Formatear teléfono para mostrar destino WhatsApp
const formatWhatsApp = (telefono) => {
    if (!telefono) return null;
    // Limpiar y formatear
    const clean = telefono.replace(/\D/g, '');
    if (clean.length >= 10) {
        return `+${clean.slice(0, 2)} ${clean.slice(2, 3)} ${clean.slice(3, 5)}... (Verificado)`;
    }
    return telefono;
};
</script>

<template>
    <Head title="Generar Orden de Compra" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ mostrarResultado ? 'Resultado de Operación' : 'Generar Orden de Compra' }}
                </h2>
                <Link v-if="mostrarFormulario" :href="route('ofertas.index')" 
                      class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    ← Volver a Ofertas
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- ============================================== -->
                <!-- PANTALLA 3: RETROALIMENTACIÓN (K&K Cap. 12)   -->
                <!-- ============================================== -->
                <div v-if="mostrarResultado">
                    
                    <!-- OPCIÓN A: ÉXITO COMPLETO -->
                    <div v-if="resultado.tipo === 'success'" 
                         class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        
                        <!-- Encabezado con checkmark -->
                        <div class="bg-emerald-600 px-6 py-6 text-center">
                            <div class="mx-auto w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">ORDEN DE COMPRA GENERADA EXITOSAMENTE</h3>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            <!-- Información de la orden -->
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-6 border border-emerald-200 dark:border-emerald-800">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="text-center">
                                        <p class="text-xs uppercase tracking-wider text-emerald-700 dark:text-emerald-300 mb-1">Número de Orden</p>
                                        <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ resultado.orden.numero_oc }}</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-xs uppercase tracking-wider text-emerald-700 dark:text-emerald-300 mb-1">Total</p>
                                        <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ formatCurrency(resultado.orden.total) }}</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-xs uppercase tracking-wider text-emerald-700 dark:text-emerald-300 mb-1">Fecha / Hora</p>
                                        <p class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">{{ new Date().toLocaleString('es-AR') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Checklist de estado (K&K: retroalimentación visual) -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Estado de la operación</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span class="text-emerald-500 mr-3">✓</span>
                                        <span class="text-gray-900 dark:text-white">Documento PDF generado:</span>
                                        <span class="ml-2 font-bold text-emerald-600 dark:text-emerald-400">OK</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-emerald-500 mr-3">✓</span>
                                        <span class="text-gray-900 dark:text-white">Enviado por WhatsApp:</span>
                                        <span class="ml-2 font-bold text-emerald-600 dark:text-emerald-400">OK</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-emerald-500 mr-3">✓</span>
                                        <span class="text-gray-900 dark:text-white">Notificación email:</span>
                                        <span class="ml-2 font-bold text-emerald-600 dark:text-emerald-400">OK</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-emerald-500 mr-3">✓</span>
                                        <span class="text-gray-900 dark:text-white">Oferta marcada como procesada:</span>
                                        <span class="ml-2 font-bold text-emerald-600 dark:text-emerald-400">OK</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="flex items-center justify-center space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <SecondaryButton @click="irAListado">
                                    Ver Todas las Órdenes
                                </SecondaryButton>
                                <PrimaryButton @click="irAOrden" class="bg-emerald-600 hover:bg-emerald-700">
                                    Ver Detalle de la Orden
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- OPCIÓN B: ÉXITO CON ADVERTENCIAS (Fallo parcial) -->
                    <div v-else-if="resultado.tipo === 'success_with_warnings'" 
                         class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        
                        <div class="bg-amber-500 px-6 py-6 text-center">
                            <div class="mx-auto w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">ORDEN CREADA CON ADVERTENCIAS</h3>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            <!-- Información de la orden -->
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-6 border border-amber-200 dark:border-amber-800">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="text-center">
                                        <p class="text-xs uppercase tracking-wider text-amber-700 dark:text-amber-300 mb-1">Número de Orden</p>
                                        <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">{{ resultado.orden.numero_oc }}</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-xs uppercase tracking-wider text-amber-700 dark:text-amber-300 mb-1">Estado</p>
                                        <span class="inline-block px-4 py-1 text-sm font-bold bg-amber-200 text-amber-900 rounded-full">
                                            {{ resultado.orden.estado }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Checklist de estado con errores -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Estado de la operación</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <span class="mr-3" :class="resultado.advertencias.some(a => a.excepcion === '8a') ? 'text-red-500' : 'text-emerald-500'">
                                            {{ resultado.advertencias.some(a => a.excepcion === '8a') ? '✗' : '✓' }}
                                        </span>
                                        <span class="text-gray-900 dark:text-white">Documento PDF generado:</span>
                                        <span class="ml-2 font-bold" :class="resultado.advertencias.some(a => a.excepcion === '8a') ? 'text-red-600' : 'text-emerald-600'">
                                            {{ resultado.advertencias.some(a => a.excepcion === '8a') ? 'FALLO' : 'OK' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="mr-3" :class="resultado.advertencias.some(a => a.excepcion === '9a') ? 'text-red-500' : 'text-emerald-500'">
                                            {{ resultado.advertencias.some(a => a.excepcion === '9a') ? '✗' : '✓' }}
                                        </span>
                                        <span class="text-gray-900 dark:text-white">Enviado por WhatsApp:</span>
                                        <span class="ml-2 font-bold" :class="resultado.advertencias.some(a => a.excepcion === '9a') ? 'text-red-600' : 'text-emerald-600'">
                                            {{ resultado.advertencias.some(a => a.excepcion === '9a') ? 'FALLO' : 'OK' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="mr-3" :class="resultado.advertencias.some(a => a.excepcion === '9b') ? 'text-red-500' : 'text-emerald-500'">
                                            {{ resultado.advertencias.some(a => a.excepcion === '9b') ? '✗' : '✓' }}
                                        </span>
                                        <span class="text-gray-900 dark:text-white">Notificación email:</span>
                                        <span class="ml-2 font-bold" :class="resultado.advertencias.some(a => a.excepcion === '9b') ? 'text-red-600' : 'text-emerald-600'">
                                            {{ resultado.advertencias.some(a => a.excepcion === '9b') ? 'FALLO' : 'OK' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="mr-3" :class="resultado.advertencias.some(a => a.excepcion === '10a') ? 'text-red-500' : 'text-emerald-500'">
                                            {{ resultado.advertencias.some(a => a.excepcion === '10a') ? '✗' : '✓' }}
                                        </span>
                                        <span class="text-gray-900 dark:text-white">Oferta marcada como procesada:</span>
                                        <span class="ml-2 font-bold" :class="resultado.advertencias.some(a => a.excepcion === '10a') ? 'text-red-600' : 'text-emerald-600'">
                                            {{ resultado.advertencias.some(a => a.excepcion === '10a') ? 'FALLO' : 'OK' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Detalle de las advertencias -->
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                                <h4 class="text-sm font-semibold text-red-900 dark:text-red-100 mb-3">Acciones pendientes:</h4>
                                <ul class="space-y-2">
                                    <li v-for="(adv, index) in resultado.advertencias" :key="index" class="flex items-start text-sm">
                                        <span class="inline-block px-2 py-0.5 text-xs font-mono bg-red-200 text-red-900 rounded mr-3">
                                            {{ adv.excepcion }}
                                        </span>
                                        <span class="text-red-800 dark:text-red-200">{{ adv.mensaje }}</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="flex items-center justify-center space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <SecondaryButton @click="irAListado">
                                    Volver al Listado
                                </SecondaryButton>
                                <PrimaryButton @click="irAOrden" class="bg-amber-600 hover:bg-amber-700">
                                    Ir a la Orden y Resolver
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- ERROR CRÍTICO -->
                    <div v-else-if="resultado.tipo === 'error'" 
                         class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-red-600 px-6 py-6 text-center">
                            <div class="mx-auto w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">ERROR AL GENERAR LA ORDEN</h3>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-6 border border-red-200 dark:border-red-800">
                                <p class="text-sm font-semibold text-red-900 dark:text-red-100 mb-2">Detalle del error:</p>
                                <p class="text-sm text-red-800 dark:text-red-200 bg-white dark:bg-gray-800 p-3 rounded font-mono">
                                    {{ resultado.error }}
                                </p>
                            </div>

                            <div class="flex items-center justify-center space-x-4 pt-4">
                                <SecondaryButton @click="irAListado">
                                    Volver al Listado
                                </SecondaryButton>
                                <Link :href="route('ofertas.index')">
                                    <PrimaryButton>
                                        Intentar con Otra Oferta
                                    </PrimaryButton>
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ============================================== -->
                <!-- PANTALLA 2: FORMULARIO (K&K Cap. 11 y 12)    -->
                <!-- ============================================== -->
                <template v-if="mostrarFormulario">
                    
                    <!-- SECCIÓN 1: RESUMEN DATOS DEL PROVEEDOR (Solo lectura) -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-indigo-600 px-6 py-3">
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 bg-white/20 rounded-full text-white font-bold text-sm mr-3">1</span>
                                <h3 class="text-base font-semibold text-white uppercase tracking-wide">Resumen Datos del Proveedor</h3>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Razón Social</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ oferta.proveedor.razon_social }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">CUIT</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white font-mono">{{ oferta.proveedor.cuit }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Email</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ oferta.proveedor.email || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Teléfono / WhatsApp</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ oferta.proveedor.telefono || '-' }}
                                        <span v-if="oferta.proveedor.telefono" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                            </svg>
                                            Verificado
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: DETALLE DE LA ORDEN (Productos + IVA) -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-indigo-600 px-6 py-3">
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 bg-white/20 rounded-full text-white font-bold text-sm mr-3">2</span>
                                <h3 class="text-base font-semibold text-white uppercase tracking-wide">Detalle de la Orden</h3>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Producto</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Código</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Cant.</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Precio Unit.</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="detalle in oferta.detalles" :key="detalle.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ detalle.producto.nombre }}</td>
                                            <td class="px-4 py-3 text-sm text-center text-gray-500 dark:text-gray-400 font-mono">{{ detalle.producto.codigo }}</td>
                                            <td class="px-4 py-3 text-sm text-center font-bold text-gray-900 dark:text-white">{{ detalle.cantidad_ofrecida }}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(detalle.precio_unitario) }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-white">
                                                {{ formatCurrency(detalle.cantidad_ofrecida * detalle.precio_unitario) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    <!-- Totales con desglose de IVA -->
                                    <tfoot>
                                        <tr class="bg-gray-50 dark:bg-gray-700/50">
                                            <td colspan="4" class="px-4 py-2 text-right text-sm text-gray-600 dark:text-gray-400">Subtotal:</td>
                                            <td class="px-4 py-2 text-right text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(subtotal) }}</td>
                                        </tr>
                                        <tr class="bg-gray-50 dark:bg-gray-700/50">
                                            <td colspan="4" class="px-4 py-2 text-right text-sm text-gray-600 dark:text-gray-400">IVA (21%):</td>
                                            <td class="px-4 py-2 text-right text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(impuestos) }}</td>
                                        </tr>
                                        <tr class="bg-indigo-50 dark:bg-indigo-900/30">
                                            <td colspan="4" class="px-4 py-3 text-right text-base font-bold text-indigo-900 dark:text-indigo-100 uppercase">Total Final:</td>
                                            <td class="px-4 py-3 text-right text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(totalFinal) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 3: DATOS DE LA OPERACIÓN (Input) -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-indigo-600 px-6 py-3">
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 bg-white/20 rounded-full text-white font-bold text-sm mr-3">3</span>
                                <h3 class="text-base font-semibold text-white uppercase tracking-wide">Datos de la Operación</h3>
                            </div>
                        </div>

                        <div class="p-6">
                            <form @submit.prevent="submit">
                                <div>
                                    <InputLabel for="observaciones">
                                        Motivo para generar esta Orden de Compra <span class="text-red-500">*</span>
                                    </InputLabel>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Obligatorio para auditoría y trazabilidad del sistema</p>
                                    <textarea
                                        id="observaciones"
                                        v-model="form.observaciones"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        rows="4"
                                        :maxlength="MAX_CARACTERES"
                                        placeholder="Ejemplo: Reposición de stock aprobada por Gerencia..."
                                        required
                                    ></textarea>
                                    <div class="flex items-center justify-between mt-2">
                                        <InputError :message="form.errors.observaciones" />
                                        <span class="text-xs" :class="caracteresRestantes < 50 ? 'text-amber-600' : 'text-gray-500 dark:text-gray-400'">
                                            {{ caracteresRestantes }} / {{ MAX_CARACTERES }} caracteres restantes
                                        </span>
                                    </div>
                                </div>

                                <!-- Info de destino de envío -->
                                <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                    <p class="text-sm text-blue-800 dark:text-blue-200">
                                        <strong>Destino de envío:</strong><br>
                                        <span class="flex items-center mt-1">
                                            <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                            </svg>
                                            WhatsApp: {{ oferta.proveedor.telefono || 'No disponible' }}
                                        </span>
                                        <span class="flex items-center mt-1">
                                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                            Email: {{ oferta.proveedor.email || 'No disponible' }}
                                        </span>
                                    </p>
                                </div>

                                <!-- Botones de acción -->
                                <div class="mt-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-6">
                                    <Link :href="route('ofertas.index')">
                                        <SecondaryButton type="button">
                                            Cancelar
                                        </SecondaryButton>
                                    </Link>
                                    <PrimaryButton 
                                        :disabled="form.processing || form.observaciones.length < 10"
                                        class="bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Confirmar y Enviar OC
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>

                </template>

            </div>
        </div>

        <!-- MODAL DE CONFIRMACIÓN FINAL (K&K: antes de acción irreversible) -->
        <Modal :show="showConfirmModal" @close="showConfirmModal = false" max-width="lg">
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="mx-auto w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">¿Confirmar generación de OC?</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Esta acción es irreversible. Se generará la orden y se enviará al proveedor.</p>
                </div>

                <!-- Resumen -->
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6 text-sm">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Proveedor:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ oferta.proveedor.razon_social }}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Productos:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ oferta.detalles.length }} item(s)</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
                        <span class="text-gray-600 dark:text-gray-400">Total con IVA:</span>
                        <span class="font-bold text-lg text-indigo-600 dark:text-indigo-400">{{ formatCurrency(totalFinal) }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <SecondaryButton @click="showConfirmModal = false" :disabled="form.processing">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton 
                        @click="confirmarGeneracion" 
                        :disabled="form.processing"
                        class="bg-emerald-600 hover:bg-emerald-700">
                        <span v-if="form.processing">Procesando...</span>
                        <span v-else>Confirmar y Enviar</span>
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
