<script setup>
/**
 * CU-21 Pantalla 3: Comparativa y Selección de Ofertas
 * 
 * Diseño basado en Kendall & Kendall (8va Ed.) Cap. 11 - Diseño de Salida Efectiva:
 * - Formato Tabular: Es el diseño más eficiente para comparar atributos similares
 * - Ordenamiento Automático (Paso 10): Columnas ordenadas por precio más bajo
 * - Resaltado Visual: Negritas y colores para destacar mejor valor
 * - Acción Clara: Botón "Elegir esta Oferta" al pie de cada columna
 * 
 * CU-21 Pasos 10, 11 y 12:
 * - Paso 10: Sistema ordena por precio y plazo para visualización comparativa
 * - Paso 11: Usuario visualiza comparativa y selecciona propuesta deseada
 * - Paso 12: Sistema marca oferta como "Elegida" o "Pre-aprobada"
 */
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    producto: Object,
    solicitud: {
        type: Object,
        default: null
    },
    ofertas: Array,
    filters: Object,
    comparacionSignificativa: { type: Boolean, default: true },
});

// Modal de confirmación
const mostrarModalConfirmacion = ref(false);
const mostrarModalExito = ref(false);
const ofertaSeleccionada = ref(null);
const ofertaElegida = ref(null);

// Obtener detalle del producto para una oferta
const getDetalle = (oferta) => {
    // Si la oferta tiene detalles, buscar el del producto
    if (oferta.detalles && oferta.detalles.length > 0) {
        return oferta.detalles.find(d => d.producto_id === props.producto?.id) || oferta.detalles[0];
    }
    // Si no tiene detalles, usar los campos directos de la oferta
    return {
        precio_unitario: oferta.precio_unitario,
        cantidad_ofrecida: oferta.cantidad,
        disponibilidad: oferta.disponibilidad,
        dias_entrega: oferta.dias_entrega,
        disponibilidad_inmediata: oferta.disponibilidad === 'inmediata',
    };
};

// Mejor precio (menor valor)
const mejorPrecio = computed(() => {
    const precios = props.ofertas.map(o => {
        const detalle = getDetalle(o);
        return detalle ? parseFloat(detalle.precio_unitario) : Infinity;
    });
    return Math.min(...precios);
});

// Mejor plazo (menor días)
const mejorPlazo = computed(() => {
    const plazos = props.ofertas.map(o => {
        const detalle = getDetalle(o);
        if (!detalle) return Infinity;
        return detalle.disponibilidad_inmediata ? 0 : (detalle.dias_entrega || Infinity);
    });
    return Math.min(...plazos);
});

// Verificar si es mejor precio
const esMejorPrecio = (oferta) => {
    const detalle = getDetalle(oferta);
    return detalle && parseFloat(detalle.precio_unitario) === mejorPrecio.value;
};

// Verificar si es mejor plazo
const esMejorPlazo = (oferta) => {
    const detalle = getDetalle(oferta);
    if (!detalle) return false;
    const dias = detalle.disponibilidad_inmediata ? 0 : (detalle.dias_entrega || Infinity);
    return dias === mejorPlazo.value;
};

// Formatear moneda
const formatCurrency = (value, moneda = 'ARS') => {
    const symbol = moneda === 'USD' ? 'USD ' : '$ ';
    return symbol + Number(value || 0).toLocaleString('es-AR', { minimumFractionDigits: 2 });
};

// Formatear fecha
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

// Verificar si validez está próxima a vencer (menos de 5 días)
const validezProxima = (fecha) => {
    if (!fecha) return false;
    const hoy = new Date();
    const validez = new Date(fecha);
    const diffDias = Math.ceil((validez - hoy) / (1000 * 60 * 60 * 24));
    return diffDias > 0 && diffDias <= 5;
};

// Obtener label de disponibilidad
const getDisponibilidadLabel = (detalle) => {
    if (!detalle) return '-';
    if (detalle.disponibilidad_inmediata || detalle.disponibilidad === 'inmediata') {
        return 'Inmediata';
    }
    if (detalle.disponibilidad === 'sin_stock') {
        return 'Sin stock';
    }
    return 'A pedido';
};

// Obtener plazo de entrega
const getPlazoEntrega = (detalle) => {
    if (!detalle) return '-';
    if (detalle.disponibilidad_inmediata || detalle.disponibilidad === 'inmediata') {
        return '0 días hábiles';
    }
    return `${detalle.dias_entrega || '-'} días hábiles`;
};

// Seleccionar oferta (mostrar modal)
const seleccionarOferta = (oferta) => {
    ofertaSeleccionada.value = oferta;
    mostrarModalConfirmacion.value = true;
};

// Confirmar elección (CU-21 Paso 12)
const confirmarEleccion = () => {
    if (!ofertaSeleccionada.value) return;
    
    router.post(route('ofertas.elegir', ofertaSeleccionada.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Guardar la oferta elegida para mostrar en modal de éxito
            ofertaElegida.value = ofertaSeleccionada.value;
            mostrarModalConfirmacion.value = false;
            // Mostrar modal de éxito (Pantalla 4)
            mostrarModalExito.value = true;
        },
    });
};

// Cerrar modal de éxito y seguir gestionando
const seguirGestionando = () => {
    mostrarModalExito.value = false;
    ofertaElegida.value = null;
};

// Ir a generar orden de compra
const generarOrdenCompra = () => {
    mostrarModalExito.value = false;
    // Navegar a la pantalla de crear orden de compra con la oferta elegida
    router.visit(route('ordenes-compra.create', { oferta_id: ofertaElegida.value?.id }));
};

// Cancelar comparativa (Excepción 12a)
const cancelarComparativa = () => {
    router.visit(route('ofertas.index'));
};

// Obtener total (calculado o de la oferta)
const getTotal = (oferta) => {
    if (oferta.total_estimado) return oferta.total_estimado;
    const detalle = getDetalle(oferta);
    if (!detalle) return 0;
    return (parseFloat(detalle.precio_unitario) || 0) * (parseFloat(detalle.cantidad_ofrecida || oferta.cantidad) || 1);
};

// Obtener cantidad
const getCantidad = (oferta) => {
    const detalle = getDetalle(oferta);
    return detalle?.cantidad_ofrecida || oferta.cantidad || 1;
};

// Obtener tipo de adjunto
const getTipoAdjunto = (archivo) => {
    if (!archivo) return null;
    const ext = archivo.split('.').pop().toLowerCase();
    if (['pdf'].includes(ext)) return 'PDF';
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'IMG';
    if (['mp3', 'wav', 'ogg', 'm4a'].includes(ext)) return 'Audio';
    if (['doc', 'docx', 'txt'].includes(ext)) return 'Doc';
    return 'Archivo';
};

// Nombre corto del proveedor para header
const getNombreCorto = (nombre) => {
    if (!nombre) return '-';
    if (nombre.length <= 15) return nombre;
    return nombre.substring(0, 12) + '...';
};
</script>

<template>
    <Head :title="`Comparativa de Ofertas - ${producto?.nombre || 'Producto'}`" />

    <AppLayout>
        <!-- HEADER consistente -->
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Gestión de Compras
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Breadcrumb, título y botón volver -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
                            GESTIÓN DE COMPRAS &gt; OFERTAS &gt; COMPARATIVA
                        </p>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                            COMPARATIVA DE OFERTAS
                        </h1>
                        <div v-if="producto" class="mt-1 flex items-center gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ producto.nombre }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-500">•</span>
                            <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ getCantidad(ofertas[0]) }} Unidades</span>
                            <span v-if="solicitud" class="text-xs text-gray-500 dark:text-gray-500">•</span>
                            <span v-if="solicitud" class="text-sm text-gray-600 dark:text-gray-400">Solicitud #{{ solicitud.codigo_solicitud }}</span>
                        </div>
                    </div>
                    <Link 
                        :href="route('ofertas.index')" 
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors"
                    >
                        Volver
                    </Link>
                </div>

                <!-- Info de contexto -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Se encontraron <strong class="text-indigo-600 dark:text-indigo-400">{{ ofertas.length }} ofertas válidas</strong>.
                        Ordenadas por <strong class="underline">Precio y Plazo de Entrega</strong>.
                    </p>
                </div>

                <!-- ALERTA: Comparación no significativa (Excepción 10a) -->
                <div v-if="!comparacionSignificativa" 
                     class="bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-amber-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            <strong>Nota:</strong> Solo existe una oferta. No hay suficientes para una comparación significativa.
                        </p>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- TABLA COMPARATIVA (Estilo mockup indigo)      -->
                <!-- ============================================== -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <!-- Header con fondo indigo -->
                            <thead>
                                <tr class="bg-indigo-600">
                                    <th class="px-4 py-3 text-left text-xs font-bold text-white uppercase tracking-wider border-r border-indigo-500 w-44">
                                        Característica
                                    </th>
                                    <th v-for="(oferta, idx) in ofertas" :key="oferta.id"
                                        class="px-4 py-3 text-center text-xs font-bold text-white uppercase tracking-wider border-r border-indigo-500 last:border-r-0">
                                        <div>OFERTA #{{ idx + 1 }}</div>
                                        <div class="font-normal text-indigo-200 text-xs mt-0.5">
                                            ({{ getNombreCorto(oferta.proveedor?.razon_social) }})
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Fila: Proveedor -->
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-750 border-r border-gray-200 dark:border-gray-600">
                                        Proveedor
                                    </td>
                                    <td v-for="oferta in ofertas" :key="`prov-${oferta.id}`"
                                        class="px-4 py-3 text-sm text-center text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                        {{ oferta.proveedor?.razon_social || '-' }}
                                    </td>
                                </tr>
                                
                                <!-- Fila: Fecha Oferta -->
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-750 border-r border-gray-200 dark:border-gray-600">
                                        Fecha Recepción
                                    </td>
                                    <td v-for="oferta in ofertas" :key="`fecha-${oferta.id}`"
                                        class="px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                        {{ formatDate(oferta.fecha_recepcion || oferta.created_at) }}
                                    </td>
                                </tr>
                                
                                <!-- Fila: Precio Unitario (destacado con indigo) -->
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-750 border-r border-gray-200 dark:border-gray-600">
                                        <span class="text-red-500 mr-1">*</span> Precio Unit. ({{ ofertas[0]?.moneda || 'ARS' }})
                                    </td>
                                    <td v-for="oferta in ofertas" :key="`precio-${oferta.id}`"
                                        class="px-4 py-3 text-center border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                        <span class="text-base font-bold"
                                              :class="esMejorPrecio(oferta) 
                                                  ? 'text-indigo-600 dark:text-indigo-400' 
                                                  : 'text-gray-900 dark:text-white'">
                                            <span v-if="esMejorPrecio(oferta)">*</span>{{ formatCurrency(getDetalle(oferta)?.precio_unitario || 0, oferta.moneda) }}<span v-if="esMejorPrecio(oferta)">*</span>
                                        </span>
                                    </td>
                                </tr>
                                
                                <!-- Fila: Total -->
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-750 border-r border-gray-200 dark:border-gray-600">
                                        <span class="text-red-500 mr-1">*</span> Total ({{ getCantidad(ofertas[0]) }} Unid.)
                                    </td>
                                    <td v-for="oferta in ofertas" :key="`total-${oferta.id}`"
                                        class="px-4 py-3 text-center border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                        <span class="text-base font-bold"
                                              :class="esMejorPrecio(oferta) 
                                                  ? 'text-indigo-600 dark:text-indigo-400' 
                                                  : 'text-gray-900 dark:text-white'">
                                            <span v-if="esMejorPrecio(oferta)">*</span>{{ formatCurrency(getTotal(oferta), oferta.moneda) }}<span v-if="esMejorPrecio(oferta)">*</span>
                                        </span>
                                    </td>
                                </tr>
                                
                                <!-- Fila: Disponibilidad -->
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-750 border-r border-gray-200 dark:border-gray-600">
                                        Disponibilidad
                                    </td>
                                    <td v-for="oferta in ofertas" :key="`disp-${oferta.id}`"
                                        class="px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                        {{ getDisponibilidadLabel(getDetalle(oferta)) }}
                                    </td>
                                </tr>
                                
                                <!-- Fila: Plazo de Entrega (destacado con indigo) -->
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-750 border-r border-gray-200 dark:border-gray-600">
                                        <span class="text-red-500 mr-1">*</span> Plazo de Entrega
                                    </td>
                                    <td v-for="oferta in ofertas" :key="`plazo-${oferta.id}`"
                                        class="px-4 py-3 text-center border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                        <span class="font-semibold"
                                              :class="esMejorPlazo(oferta) 
                                                  ? 'text-indigo-600 dark:text-indigo-400' 
                                                  : 'text-gray-900 dark:text-white'">
                                            <span v-if="esMejorPlazo(oferta)">*</span>{{ getPlazoEntrega(getDetalle(oferta)) }}<span v-if="esMejorPlazo(oferta)">*</span>
                                        </span>
                                    </td>
                                </tr>
                                
                                <!-- Fila: Validez Oferta -->
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-750 border-r border-gray-200 dark:border-gray-600">
                                        Validez Oferta
                                    </td>
                                    <td v-for="oferta in ofertas" :key="`validez-${oferta.id}`"
                                        class="px-4 py-3 text-sm text-center border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                        <span :class="validezProxima(oferta.validez_hasta) 
                                            ? 'text-amber-600 dark:text-amber-400 font-medium' 
                                            : 'text-gray-700 dark:text-gray-300'">
                                            {{ oferta.validez_hasta ? `Hasta ${formatDate(oferta.validez_hasta)}` : '-' }}
                                            <svg v-if="validezProxima(oferta.validez_hasta)" xmlns="http://www.w3.org/2000/svg" class="inline-block h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        </span>
                                    </td>
                                </tr>
                                
                                <!-- Fila: Condiciones -->
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-750 border-r border-gray-200 dark:border-gray-600">
                                        Condiciones
                                    </td>
                                    <td v-for="oferta in ofertas" :key="`cond-${oferta.id}`"
                                        class="px-4 py-3 text-xs text-center text-gray-600 dark:text-gray-400 border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                        {{ oferta.condiciones_proveedor || '-' }}
                                    </td>
                                </tr>
                                
                                <!-- Fila: Adjunto -->
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-750 border-r border-gray-200 dark:border-gray-600">
                                        Adjunto
                                    </td>
                                    <td v-for="oferta in ofertas" :key="`adj-${oferta.id}`"
                                        class="px-4 py-3 text-sm text-center border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                        <a v-if="oferta.archivo_adjunto" 
                                           :href="`/storage/${oferta.archivo_adjunto}`" 
                                           target="_blank"
                                           class="inline-flex items-center text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                            [Ver {{ getTipoAdjunto(oferta.archivo_adjunto) }}]
                                        </a>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                </tr>
                                
                                <!-- Fila: Observación Interna -->
                                <tr class="bg-gray-50 dark:bg-gray-750">
                                    <td class="px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600 uppercase text-xs">
                                        Observación Interna
                                    </td>
                                    <td v-for="oferta in ofertas" :key="`obs-${oferta.id}`"
                                        class="px-4 py-3 text-xs text-center text-gray-500 dark:text-gray-400 italic border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                        "{{ oferta.motivo_registro || '-' }}"
                                    </td>
                                </tr>
                                
                                <!-- Fila: BOTÓN SELECCIÓN -->
                                <tr>
                                    <td class="px-4 py-4 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-600"></td>
                                    <td v-for="oferta in ofertas" :key="`accion-${oferta.id}`"
                                        class="px-4 py-4 text-center bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                        <button 
                                            @click="seleccionarOferta(oferta)"
                                            :disabled="oferta.estado?.nombre === 'Elegida'"
                                            class="w-full px-4 py-2.5 font-bold text-sm rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            :class="oferta.estado?.nombre === 'Elegida'
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-300'
                                                : 'bg-indigo-600 hover:bg-indigo-700 text-white'">
                                            {{ oferta.estado?.nombre === 'Elegida' ? '✓ ELEGIDA' : 'ELEGIR ESTA OFERTA' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- CANCELAR COMPARATIVA (estilo botón centrado) -->
                <!-- ============================================== -->
                <div class="flex justify-center">
                    <button 
                        @click="cancelarComparativa"
                        class="px-6 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md transition-colors">
                        Cancelar Comparativa
                    </button>
                </div>

            </div>
        </div>

        <!-- ============================================== -->
        <!-- MODAL DE CONFIRMACIÓN (Antes de elegir)       -->
        <!-- ============================================== -->
        <Teleport to="body">
            <div v-if="mostrarModalConfirmacion" 
                 class="fixed inset-0 z-50 overflow-y-auto"
                 aria-labelledby="modal-title" 
                 role="dialog" 
                 aria-modal="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Overlay -->
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                         @click="mostrarModalConfirmacion = false"></div>

                    <!-- Modal -->
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
                        <div class="px-6 pt-5 pb-4">
                            <!-- Icono -->
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30">
                                <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            
                            <div class="mt-4 text-center">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="modal-title">
                                    Confirmar Selección de Oferta
                                </h3>
                                <div class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                                    <p>
                                        ¿Desea elegir la oferta de <strong>{{ ofertaSeleccionada?.proveedor?.razon_social }}</strong> 
                                        por <strong class="text-indigo-600 dark:text-indigo-400">{{ formatCurrency(getTotal(ofertaSeleccionada), ofertaSeleccionada?.moneda) }}</strong>?
                                    </p>
                                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                        La oferta será marcada como "Elegida" y quedará lista para generar la Orden de Compra.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-750 px-6 py-4 flex flex-col sm:flex-row gap-3 justify-end">
                            <button 
                                @click="mostrarModalConfirmacion = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                Cancelar
                            </button>
                            <button 
                                @click="confirmarEleccion"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition-colors">
                                Confirmar Elección
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ============================================== -->
        <!-- PANTALLA 4: MODAL DE ÉXITO (Pasos 12-14)      -->
        <!-- K&K: Feedback inmediato y positivo             -->
        <!-- ============================================== -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="mostrarModalExito" 
                     class="fixed inset-0 z-50 overflow-y-auto"
                     aria-labelledby="modal-exito-title" 
                     role="dialog" 
                     aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <!-- Overlay con efecto blur -->
                        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

                        <!-- Modal con animación -->
                        <Transition
                            enter-active-class="transition ease-out duration-300"
                            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-active-class="transition ease-in duration-200"
                            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <div v-if="mostrarModalExito" 
                                 class="relative bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-xl w-full">
                                
                                <!-- HEADER VERDE CON ICONO (estilo mockup mejorado) -->
                                <div class="bg-gradient-to-r from-emerald-500 to-green-600 px-6 py-5 relative">
                                    <!-- Botón cerrar -->
                                    <button 
                                        @click="seguirGestionando"
                                        class="absolute top-4 right-4 text-white/80 hover:text-white transition-colors">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    
                                    <div class="flex items-center">
                                        <!-- Icono animado de éxito -->
                                        <div class="flex-shrink-0 mr-4">
                                            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center ring-4 ring-white/30">
                                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold text-white" id="modal-exito-title">
                                                ¡Oferta Seleccionada con Éxito!
                                            </h3>
                                            <p class="text-emerald-100 text-sm mt-0.5">
                                                Paso 12 completado
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- CONTENIDO -->
                                <div class="px-6 py-5">
                                    <!-- Mensaje principal con datos resaltados -->
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-4">
                                        <p class="text-gray-700 dark:text-gray-200 leading-relaxed">
                                            La oferta de 
                                            <strong class="text-gray-900 dark:text-white">{{ ofertaElegida?.proveedor?.razon_social }}</strong> 
                                            por 
                                            <strong class="text-emerald-600 dark:text-emerald-400 text-lg">{{ formatCurrency(getTotal(ofertaElegida), ofertaElegida?.moneda) }}</strong> 
                                            ha sido marcada como 
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                                "Elegida"
                                            </span>
                                            <span v-if="solicitud"> para la solicitud <strong class="text-indigo-600 dark:text-indigo-400">#{{ solicitud.codigo_solicitud }}</strong></span>.
                                        </p>
                                    </div>
                                    
                                    <!-- Info adicional con icono -->
                                    <div class="flex items-start text-sm text-gray-500 dark:text-gray-400 mb-5">
                                        <svg class="w-5 h-5 text-gray-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span>Se ha registrado la operación y el motivo en el historial de auditoría.</span>
                                    </div>
                                    
                                    <!-- Separador -->
                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-5">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                                            ¿Qué desea hacer ahora?
                                        </p>
                                        
                                        <!-- Botones de acción -->
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <button 
                                                @click="seguirGestionando"
                                                class="flex-1 px-5 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 hover:border-gray-400 dark:hover:border-gray-500 transition-all duration-200 flex items-center justify-center">
                                                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                                </svg>
                                                Seguir Gestionando Ofertas
                                            </button>
                                            <button 
                                                @click="generarOrdenCompra"
                                                class="flex-1 px-5 py-3 text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 rounded-lg shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/40 transition-all duration-200 flex items-center justify-center">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                Generar Orden de Compra Ahora
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Footer con tips -->
                                <div class="bg-indigo-50 dark:bg-indigo-900/20 px-6 py-3 border-t border-indigo-100 dark:border-indigo-800">
                                    <p class="text-xs text-indigo-600 dark:text-indigo-400 flex items-center">
                                        <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        <span><strong>Tip:</strong> También puede generar la Orden de Compra más tarde desde el panel de ofertas elegidas.</span>
                                    </p>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
