<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    cotizacion: Object,
    resultado: {
        type: Object,
        default: null
    }
});

// Estados condicionales
const mostrarFormulario = computed(() => !props.resultado);
const mostrarResultado = computed(() => !!props.resultado);

// Calcular total de la cotización desde respuestas
const totalCotizacion = computed(() => {
    if (!props.cotizacion?.respuestas) return 0;
    return props.cotizacion.respuestas.reduce((sum, r) => {
        return sum + ((r.precio_unitario || 0) * (r.cantidad_disponible || 0));
    }, 0);
});

// Moneda por defecto ARS
const moneda = computed(() => 'ARS');

const form = useForm({
    cotizacion_id: props.cotizacion?.id,
    motivo: '',
});

const submit = () => {
    // Validación manual del motivo
    if (!form.motivo || form.motivo.trim().length < 10) {
        form.setError('motivo', 'Se requiere un motivo para generar la Orden de Compra.');
        return;
    }
    form.clearErrors();
    form.post(route('ordenes.store'));
};

const cancelar = () => {
    router.visit(route('ordenes.index'));
};

const formatCurrency = (value, currency = 'USD') => {
    const currencyMap = {
        'USD': { locale: 'en-US', currency: 'USD' },
        'ARS': { locale: 'es-AR', currency: 'ARS' },
        'EUR': { locale: 'de-DE', currency: 'EUR' }
    };
    const config = currencyMap[currency] || currencyMap['USD'];
    return new Intl.NumberFormat(config.locale, { 
        style: 'currency', 
        currency: config.currency 
    }).format(value);
};

// Código de la cotización
const codigoCotizacion = computed(() => {
    if (!props.cotizacion) return '';
    return props.cotizacion.solicitud?.codigo_solicitud || `COT-${String(props.cotizacion.id).padStart(4, '0')}`;
});

const irAOrden = () => {
    router.visit(route('ordenes.show', props.resultado.orden.id));
};

const irAListado = () => {
    router.visit(route('ordenes.index'));
};

const descargarPdf = () => {
    // Abrir en nueva pestaña para descargar el PDF
    window.open(route('ordenes.descargar-pdf', props.resultado.orden.id), '_blank');
};
</script>

<template>
    <Head title="Generar Orden de Compra" />

    <AppLayout>
        <!-- HEADER -->
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Gestión de Compras
                    </h2>
                    <p class="text-sm font-medium text-indigo-800 dark:text-indigo-300 tracking-wide mt-1">
                        Órdenes › Generar Orden de Compra
                    </p>
                </div>
                <Link :href="route('ordenes.index')" 
                      class="px-5 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    Volver
                </Link>
            </div>
        </template>

        <div :class="mostrarResultado ? 'min-h-screen bg-slate-700 dark:bg-gray-900' : 'bg-gray-100 dark:bg-gray-900'">
            <!-- ============================================== -->
            <!-- PANTALLA DE RESULTADO (después de submit)     -->
            <!-- Modal centrado sobre fondo oscuro             -->
            <!-- ============================================== -->
            <div v-if="mostrarResultado" class="flex items-center justify-center min-h-screen p-4">
                
                <!-- ========================================== -->
                <!-- ESCENARIO A: ÉXITO TOTAL (Paso 12)        -->
                <!-- ========================================== -->
                <div v-if="resultado.tipo === 'success'" class="bg-white rounded-lg shadow-2xl w-full max-w-2xl overflow-hidden">
                    <!-- Header verde con ícono de check -->
                    <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white tracking-wide">PROCESO COMPLETADO EXITOSAMENTE</h3>
                        </div>
                        <button @click="irAListado" class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Contenido -->
                    <div class="p-6">
                        <!-- Número de OC generada -->
                        <p class="text-gray-700 text-lg mb-1">
                            Se ha generado la Orden de Compra N°: <span class="font-bold text-gray-900">{{ resultado.orden.numero_oc }}</span>.
                        </p>
                        
                        <hr class="my-5 border-gray-200">
                        
                        <!-- Resumen de Acciones del Sistema -->
                        <h4 class="text-sm font-bold text-gray-700 mb-4">Resumen de Acciones del Sistema:</h4>
                        
                        <div class="space-y-3">
                            <!-- PDF generado -->
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-emerald-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Documento PDF generado correctamente.</span>
                            </div>
                            
                            <!-- WhatsApp enviado -->
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-emerald-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <span class="text-gray-700">Enviado por WhatsApp a {{ resultado.orden.proveedor }} </span>
                                    <span class="text-emerald-600 font-medium">({{ resultado.telefono_destino || 'N/A' }})</span>.
                                    <p class="text-gray-500 text-sm">(Fecha/Hora envío: {{ new Date().toLocaleString('es-AR') }})</p>
                                </div>
                            </div>
                            
                            <!-- Email enviado -->
                            <div v-if="resultado.email_enviado" class="flex items-start">
                                <svg class="w-5 h-5 text-emerald-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Enviado por Email a <span class="text-indigo-600 font-medium">{{ resultado.email_destino }}</span>.</span>
                            </div>
                            
                            <!-- Cotización marcada -->
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-emerald-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Cotización vinculada a la Orden de Compra.</span>
                            </div>
                            
                            <!-- Historial actualizado -->
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-emerald-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Historial de operaciones actualizado.</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-center gap-4">
                        <button 
                            @click="irAOrden" 
                            class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md transition-colors">
                            Ver Documento OC
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <button 
                            @click="irAListado" 
                            class="inline-flex items-center px-5 py-2.5 bg-slate-600 hover:bg-slate-700 text-white font-medium rounded-md transition-colors">
                            Volver al Listado
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- ESCENARIO B: FALLO PARCIAL (Excepción 9a) -->
                <!-- ========================================== -->
                <div v-else-if="resultado.tipo === 'success_with_warnings'" class="bg-white rounded-lg shadow-2xl w-full max-w-2xl overflow-hidden">
                    <!-- Header amarillo con ícono de advertencia -->
                    <div class="bg-gradient-to-r from-amber-500 to-amber-400 px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white tracking-wide">ATENCIÓN: OC GENERADA CON ERROR DE ENVÍO</h3>
                        </div>
                        <button @click="irAListado" class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Contenido -->
                    <div class="p-6">
                        <!-- Info de la OC -->
                        <p class="text-gray-700 text-lg mb-1">
                            Se ha generado la Orden de Compra N°: <span class="font-bold text-gray-900">{{ resultado.orden.numero_oc }}</span>
                        </p>
                        <p class="text-gray-600 mb-4">
                            Estado actual de la OC: "<span class="font-semibold">{{ resultado.orden.estado }}</span>"
                        </p>
                        
                        <hr class="my-4 border-gray-200">
                        
                        <!-- Resumen de Acciones del Sistema -->
                        <h4 class="text-sm font-bold text-gray-700 mb-4">Resumen de Acciones del Sistema:</h4>
                        
                        <div class="space-y-3">
                            <!-- PDF generado (siempre OK si llegamos aquí) -->
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-emerald-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Documento PDF generado correctamente.</span>
                            </div>
                            
                            <!-- Errores de envío (WhatsApp y/o Email) -->
                            <template v-for="(adv, index) in resultado.advertencias" :key="index">
                                <!-- Error WhatsApp -->
                                <div v-if="adv.canal === 'whatsapp'" class="bg-red-50 border border-red-200 rounded-md p-3">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        <div>
                                            <span class="text-red-700 font-semibold">FALLO</span>
                                            <span class="text-red-700"> al enviar por WhatsApp a </span>
                                            <span class="text-red-800 font-medium">{{ resultado.orden.proveedor }}</span>.
                                            <p class="text-gray-600 text-sm mt-1">> Detalle técnico: {{ adv.mensaje }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Error Email -->
                                <div v-else-if="adv.canal === 'email'" class="bg-red-50 border border-red-200 rounded-md p-3">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        <div>
                                            <span class="text-red-700 font-semibold">FALLO</span>
                                            <span class="text-red-700"> al enviar por Email a </span>
                                            <span class="text-red-800 font-medium">{{ resultado.email_destino || resultado.orden.proveedor }}</span>.
                                            <p class="text-gray-600 text-sm mt-1">> Detalle técnico: {{ adv.mensaje }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Otro tipo de error -->
                                <div v-else class="bg-red-50 border border-red-200 rounded-md p-3">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        <div>
                                            <span class="text-red-700 font-semibold">Error ({{ adv.excepcion }}):</span>
                                            <span class="text-red-700"> {{ adv.mensaje }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Cotización vinculada -->
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-emerald-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Cotización vinculada a la Orden de Compra.</span>
                            </div>
                            
                            <!-- Error registrado -->
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-emerald-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">Error registrado en el historial.</span>
                            </div>
                        </div>
                        
                        <!-- Acción Requerida -->
                        <div class="mt-5 bg-amber-50 border border-amber-300 rounded-md p-4">
                            <p class="text-amber-900 font-bold text-sm mb-1">ACCIÓN REQUERIDA:</p>
                            <p class="text-amber-800 text-sm">
                                Por favor, descargue la OC y envíela al proveedor por un medio alternativo (Email o WhatsApp manual).
                            </p>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-center gap-4">
                        <button 
                            @click="descargarPdf" 
                            class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Descargar PDF de la OC
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <button 
                            @click="irAOrden" 
                            class="inline-flex items-center px-5 py-2.5 bg-slate-600 hover:bg-slate-700 text-white font-medium rounded-md transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Ir a Gestión de OCs
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- ERROR CRÍTICO (no se pudo crear la OC)    -->
                <!-- ========================================== -->
                <div v-else-if="resultado.tipo === 'error'" class="bg-white rounded-lg shadow-2xl w-full max-w-2xl overflow-hidden">
                    <!-- Header rojo -->
                    <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white tracking-wide">ERROR: NO SE PUDO GENERAR LA OC</h3>
                        </div>
                        <button @click="irAListado" class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Contenido -->
                    <div class="p-6">
                        <p class="text-gray-700 mb-4">
                            Ocurrió un error crítico al intentar generar la Orden de Compra. No se realizó ningún cambio en el sistema.
                        </p>
                        
                        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                            <p class="text-sm font-bold text-red-900 mb-2">Detalle del error:</p>
                            <p class="text-sm text-red-800 bg-white p-3 rounded font-mono border border-red-100">
                                {{ resultado.error }}
                            </p>
                        </div>
                        
                        <p class="text-gray-500 text-sm mt-4">
                            Por favor, intente nuevamente. Si el problema persiste, contacte al administrador del sistema.
                        </p>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-center gap-4">
                        <button 
                            @click="irAListado" 
                            class="inline-flex items-center px-5 py-2.5 bg-slate-600 hover:bg-slate-700 text-white font-medium rounded-md transition-colors">
                            Volver al Listado
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- PANTALLA 2: FORMULARIO DE GENERACIÓN OC       -->
            <!-- ============================================== -->
            <template v-if="mostrarFormulario">

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
                    
                    <!-- TÍTULO DE LA PANTALLA (en el cuerpo) -->
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                            Generar y Enviar Orden de Compra
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Basada en Cotización de {{ cotizacion?.proveedor?.razon_social || 'Proveedor' }}
                        </p>
                    </div>

                    <!-- Advertencia -->
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg px-4 py-3 flex items-start">
                        <svg class="w-5 h-5 text-amber-500 dark:text-amber-400 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            <strong>ADVERTENCIA:</strong> Esta acción generará un documento legal y lo enviará automáticamente.
                        </p>
                    </div>

                    <!-- SECCIÓN 1: RESUMEN DE LA ORDEN A GENERAR -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                        <div class="bg-gray-100 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                            <h2 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                                </svg>
                                RESUMEN DE LA ORDEN A GENERAR
                                <span class="ml-2 text-gray-500 dark:text-gray-400 font-normal text-xs">Vista Previa</span>
                            </h2>
                        </div>

                        <div class="p-6">
                            <!-- Datos del Proveedor en grid horizontal -->
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg p-4 mb-6">
                                <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                    </svg>
                                    PROVEEDOR DESTINO
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Empresa:</span>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                            {{ cotizacion?.proveedor?.razon_social || 'Sin proveedor' }}
                                        </p>
                                        <p v-if="cotizacion?.proveedor?.cuit" class="text-xs text-gray-500 dark:text-gray-400">
                                            CUIT: {{ cotizacion.proveedor.cuit }}
                                        </p>
                                    </div>
                                    <div v-if="cotizacion?.proveedor?.telefono">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">WhatsApp:</span>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1 flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                            </svg>
                                            {{ cotizacion.proveedor.telefono }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de productos con diseño moderno -->
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-indigo-600">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Código</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Descripción</th>
                                            <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Cantidad</th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">Precio Unit.</th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="respuesta in cotizacion?.respuestas" :key="respuesta.id" class="hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-colors">
                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ respuesta.producto?.codigo || '-' }}</td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ respuesta.producto?.nombre || 'Producto' }}</div>
                                                <span v-if="respuesta.plazo_entrega_dias" class="text-xs text-gray-500 dark:text-gray-400">(Entrega: {{ respuesta.plazo_entrega_dias }} días)</span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-center font-semibold text-gray-900 dark:text-white">{{ respuesta.cantidad_disponible }}</td>
                                            <td class="px-6 py-4 text-sm text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(respuesta.precio_unitario, moneda) }}</td>
                                            <td class="px-6 py-4 text-sm text-right font-bold text-gray-900 dark:text-white">
                                                {{ formatCurrency(respuesta.cantidad_disponible * respuesta.precio_unitario, moneda) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Total destacado -->
                            <div class="flex justify-end mt-6">
                                <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg px-6 py-4">
                                    <div class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase mb-1">Total de la Orden</div>
                                    <div class="flex items-baseline">
                                        <span class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(totalCotizacion, moneda) }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">({{ moneda }})</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Referencia a Solicitud -->
                            <div v-if="cotizacion?.solicitud" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="bg-gray-50 dark:bg-gray-750 rounded-lg p-4">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Solicitud de origen:</span>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-2 font-mono">{{ cotizacion.solicitud.codigo_solicitud }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: DATOS DE EJECUCIÓN -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                        <div class="bg-gray-100 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                            <h2 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                </svg>
                                CONFIRMACIÓN Y ENVÍO
                                <span class="ml-2 text-gray-500 dark:text-gray-400 font-normal text-xs">Completar para continuar</span>
                            </h2>
                        </div>

                        <div class="p-6">
                            <form @submit.prevent="submit">
                                <!-- Campo Motivo -->
                                <div>
                                    <label for="motivo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="text-red-500">*</span> Justificación de Generación de OC:
                                    </label>
                                    <textarea
                                        id="motivo"
                                        v-model="form.motivo"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm resize-none"
                                        rows="4"
                                        placeholder="Ej: Cotización seleccionada por mejor precio y plazo de entrega. Autorizada por gerencia."
                                    ></textarea>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Esta justificación quedará registrada en el historial de la orden</p>
                                </div>

                                <!-- Error de validación -->
                                <div v-if="form.errors.motivo" class="mt-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-md px-4 py-3 flex items-start">
                                    <svg class="w-5 h-5 text-red-500 dark:text-red-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm text-red-700 dark:text-red-200">
                                        <strong>Error:</strong> {{ form.errors.motivo }}
                                    </span>
                                </div>

                                <!-- Botones de acción -->
                                <div class="mt-6 flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <button 
                                        type="button"
                                        @click="cancelar"
                                        class="inline-flex items-center px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Cancelar
                                    </button>
                                    
                                    <button 
                                        type="submit"
                                        :disabled="form.processing"
                                        class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-400 disabled:cursor-not-allowed text-white rounded-md text-sm font-bold transition-all shadow-md hover:shadow-lg"
                                    >
                                        <span v-if="form.processing" class="flex items-center">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Procesando...
                                        </span>
                                        <template v-else>
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            CONFIRMAR Y ENVIAR OC
                                        </template>
                                    </button>
                                </div>

                                <!-- Info canales de envío -->
                                <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-3">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        <div class="text-xs text-blue-800 dark:text-blue-200">
                                            <p class="font-medium mb-1">La orden se enviará automáticamente por:</p>
                                            <div class="flex flex-wrap gap-3">
                                                <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                                    </svg>
                                                    WhatsApp
                                                </span>
                                                <span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                    </svg>
                                                    Correo Electrónico
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
