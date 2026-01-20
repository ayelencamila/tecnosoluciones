<script setup>
/**
 * CU-21 Pantalla 2: Formulario de Registro de Oferta
 * 
 * Diseño basado en Kendall & Kendall (8va Ed.) Cap. 12 - Diseño de Entrada Efectiva:
 * - Agrupación Lógica: 4 secciones claramente numeradas
 * - Controles Adecuados: Listas desplegables y selectores de fecha
 * - Campos Obligatorios: Marcados con asterisco (*)
 * - Manejo de Excepciones: Enlaces para administrar proveedores
 */
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    proveedores: Array,
    productos: Array,
    solicitud_id: {
        type: [Number, String],
        default: null
    },
    solicitud: {
        type: Object,
        default: null
    },
    cotizacion_id: {
        type: [Number, String],
        default: null
    },
    datosPrecargados: {
        type: Object,
        default: null
    }
});

// Formulario con estructura para CU-21
const form = useForm({
    proveedor_id: props.datosPrecargados?.proveedor_id || '',
    solicitud_id: props.solicitud_id,
    cotizacion_id: props.cotizacion_id,
    
    // Producto (solo editable si NO hay solicitud)
    producto_id: props.solicitud?.detalles?.[0]?.producto_id || '',
    
    // Sección 2: Detalles de la Oferta
    moneda: 'ARS',
    precio_unitario: props.datosPrecargados?.precio_unitario || 0,
    cantidad: props.solicitud?.detalles?.[0]?.cantidad_sugerida || 1,
    disponibilidad: 'inmediata',
    dias_entrega: 0,
    validez_hasta: '',
    condiciones_proveedor: '',
    
    // Sección 3: Archivo adjunto
    archivo_adjunto: null,
    
    // Sección 4: Justificación (OBLIGATORIO según CU-21 Paso 7)
    motivo_registro: props.datosPrecargados?.observaciones || '',
});

// Determinar si viene de una solicitud (producto precargado)
const tieneSolicitud = computed(() => {
    return props.solicitud !== null && props.solicitud !== undefined;
});

// Producto precargado de la solicitud
const productoPrecargado = computed(() => {
    if (tieneSolicitud.value && props.solicitud?.detalles?.[0]?.producto) {
        return props.solicitud.detalles[0].producto;
    }
    return null;
});

// Cantidad requerida de la solicitud (para mostrar referencia)
const cantidadRequerida = computed(() => {
    if (tieneSolicitud.value && props.solicitud?.detalles?.[0]) {
        return props.solicitud.detalles[0].cantidad_sugerida;
    }
    return null;
});

// Opciones de disponibilidad
const opcionesDisponibilidad = [
    { value: 'inmediata', label: 'Inmediata' },
    { value: 'a_pedido', label: 'A pedido' },
    { value: 'sin_stock', label: 'Sin stock' },
];

// Opciones de moneda
const opcionesMoneda = [
    { value: 'ARS', label: 'ARS' },
    { value: 'USD', label: 'USD' },
];

// Subtotal estimado
const subtotalEstimado = computed(() => {
    return (parseFloat(form.cantidad) || 0) * (parseFloat(form.precio_unitario) || 0);
});

// Contexto de la solicitud (si existe)
const contextoSolicitud = computed(() => {
    if (props.solicitud) {
        const detalles = props.solicitud.detalles || [];
        const producto = detalles[0]?.producto?.nombre || 'Producto no especificado';
        const cantidad = detalles.reduce((acc, d) => acc + (d.cantidad_sugerida || 0), 0);
        return {
            codigo: props.solicitud.codigo_solicitud,
            producto: producto,
            cantidad: cantidad
        };
    }
    return null;
});

// Formatear moneda
const formatCurrency = (value) => {
    return '$ ' + Number(value || 0).toLocaleString('es-AR', { minimumFractionDigits: 2 });
};

// Validar motivo mínimo
const motivoValido = computed(() => {
    return form.motivo_registro.trim().length >= 10;
});

// Manejar selección de archivo
const fileInput = ref(null);
const handleFileSelect = (event) => {
    form.archivo_adjunto = event.target.files[0];
};

// Enviar formulario
const submit = () => {
    form.post(route('ofertas.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
};

// Guardar y registrar otra
const submitYOtra = () => {
    form.post(route('ofertas.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('precio_unitario', 'cantidad', 'disponibilidad', 'dias_entrega', 'archivo_adjunto', 'motivo_registro', 'condiciones_proveedor');
        },
    });
};
</script>

<template>
    <Head :title="contextoSolicitud ? `Registrar Oferta - ${contextoSolicitud.codigo}` : 'Registrar Nueva Oferta'" />

    <AppLayout>
        <!-- HEADER en el slot del layout -->
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate">
                        Registrar Nueva Oferta
                    </h2>
                    <p v-if="contextoSolicitud" class="text-sm text-gray-500 dark:text-gray-400 truncate mt-0.5">
                        Solicitud #{{ contextoSolicitud.codigo }} · {{ contextoSolicitud.producto }} · {{ contextoSolicitud.cantidad }} Unid.
                    </p>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Sin Solicitud Previa
                    </p>
                </div>
                <Link 
                    :href="route('ofertas.index')" 
                    class="flex-shrink-0 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors whitespace-nowrap"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

                <form @submit.prevent="submit" class="space-y-6">
                    
                    <!-- ============================================== -->
                    <!-- SECCIÓN 1: SELECCIÓN DE PROVEEDOR              -->
                    <!-- ============================================== -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-indigo-600 px-6 py-3">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                                1. Selección de Proveedor
                            </h3>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <div class="flex items-center gap-4">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    <span class="text-red-500">*</span> Proveedor:
                                </label>
                                
                                <!-- Si viene precargado -->
                                <div v-if="props.datosPrecargados?.proveedor" 
                                     class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-sm">
                                    {{ props.datosPrecargados.proveedor.razon_social }}
                                    <span class="text-xs text-gray-500 ml-2">(desde cotización)</span>
                                </div>
                                
                                <!-- Selector normal -->
                                <select 
                                    v-else
                                    v-model="form.proveedor_id"
                                    required
                                    class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                >
                                    <option value="">Seleccionar Proveedor de la lista</option>
                                    <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">
                                        {{ prov.razon_social }}
                                    </option>
                                </select>
                            </div>
                            <InputError :message="form.errors.proveedor_id" />
                            
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                ¿El proveedor no aparece? 
                                <Link :href="route('proveedores.index')" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Administrar Proveedores Activos
                                </Link>
                            </p>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- SECCIÓN 2: DETALLES DE LA OFERTA              -->
                    <!-- ============================================== -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-indigo-600 px-6 py-3">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                                2. Detalles de la Oferta
                            </h3>
                        </div>
                        
                        <div class="p-6 space-y-5">
                            <!-- Producto (selector si no hay solicitud, solo lectura si hay) -->
                            <div class="border-b border-gray-200 dark:border-gray-700 pb-5">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <span class="text-red-500">*</span> Producto:
                                </label>
                                
                                <!-- Sin solicitud: dropdown para seleccionar producto -->
                                <template v-if="!tieneSolicitud">
                                    <select 
                                        v-model="form.producto_id"
                                        required
                                        class="w-full md:w-2/3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    >
                                        <option value="">Seleccionar producto...</option>
                                        <option v-for="producto in productos" :key="producto.id" :value="producto.id">
                                            {{ producto.codigo }} - {{ producto.nombre }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.producto_id" class="mt-1" />
                                </template>
                                
                                <!-- Con solicitud: mostrar producto precargado (solo lectura) -->
                                <template v-else-if="productoPrecargado">
                                    <div class="flex items-center gap-3 py-2 px-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-md border border-indigo-200 dark:border-indigo-700">
                                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                        <div>
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ productoPrecargado.codigo }} - {{ productoPrecargado.nombre }}
                                            </span>
                                            <span v-if="cantidadRequerida" class="ml-3 text-xs text-gray-500 dark:text-gray-400">
                                                (Cantidad solicitada: {{ cantidadRequerida }} unidades)
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Grid para campos principales -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <!-- Moneda -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="text-red-500">*</span> Moneda:
                                    </label>
                                    <select 
                                        v-model="form.moneda"
                                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    >
                                        <option v-for="mon in opcionesMoneda" :key="mon.value" :value="mon.value">
                                            {{ mon.label }}
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Precio Unitario -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="text-red-500">*</span> Precio Unitario:
                                    </label>
                                    <div class="flex">
                                        <span class="inline-flex items-center px-3 bg-gray-100 dark:bg-gray-600 border border-r-0 border-gray-300 dark:border-gray-600 rounded-l-md text-sm text-gray-600 dark:text-gray-300">$</span>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0"
                                            v-model="form.precio_unitario"
                                            required
                                            class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-r-md shadow-sm text-sm"
                                        />
                                    </div>
                                    <InputError :message="form.errors.precio_unitario" class="mt-1" />
                                </div>
                                
                                <!-- Cantidad Ofertada -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="text-red-500">*</span> Cantidad Ofertada:
                                    </label>
                                    <input 
                                        type="number" 
                                        min="1"
                                        v-model="form.cantidad"
                                        required
                                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    />
                                    <InputError :message="form.errors.cantidad" class="mt-1" />
                                </div>
                            </div>
                            
                            <!-- Subtotal Estimado -->
                            <div class="flex items-center gap-2 py-2 px-4 bg-gray-50 dark:bg-gray-700 rounded-md">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Subtotal Estimado:</span>
                                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ formatCurrency(subtotalEstimado) }}
                                </span>
                            </div>
                            
                            <!-- Grid para disponibilidad y plazo -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <!-- Disponibilidad -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="text-red-500">*</span> Disponibilidad:
                                    </label>
                                    <select 
                                        v-model="form.disponibilidad"
                                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    >
                                        <option v-for="op in opcionesDisponibilidad" :key="op.value" :value="op.value">
                                            {{ op.label }}
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Plazo Entrega -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="text-red-500">*</span> Plazo Entrega:
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input 
                                            type="number" 
                                            min="0"
                                            v-model="form.dias_entrega"
                                            class="w-20 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-center"
                                        />
                                        <span class="text-sm text-gray-600 dark:text-gray-400">días hábiles a partir de la OC.</span>
                                    </div>
                                </div>
                                
                                <!-- Validez Oferta -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Validez Oferta:
                                    </label>
                                    <input 
                                        type="date" 
                                        v-model="form.validez_hasta"
                                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    />
                                    <InputError :message="form.errors.validez_hasta" class="mt-1" />
                                </div>
                            </div>
                            
                            <!-- Condiciones/Observaciones del Proveedor -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Condiciones/Observaciones del Proveedor:
                                </label>
                                <textarea 
                                    v-model="form.condiciones_proveedor"
                                    rows="3"
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm resize-none"
                                    placeholder="Precio incluye envío a oficinas centrales. Garantía de 2 años."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- SECCIÓN 3: EVIDENCIA ADJUNTA                  -->
                    <!-- ============================================== -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-indigo-600 px-6 py-3">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                                3. Evidencia Adjunta
                            </h3>
                        </div>
                        
                        <div class="p-6">
                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                                Adjuntar Cotización Original (PDF, Imagen, Audio, Texto - Máx 10MB):
                            </p>
                            
                            <div class="flex items-center gap-4">
                                <input 
                                    ref="fileInput"
                                    type="file" 
                                    @change="handleFileSelect"
                                    accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.mp3,.wav,.ogg,.m4a,.txt,.doc,.docx"
                                    class="hidden"
                                />
                                <button 
                                    type="button"
                                    @click="$refs.fileInput.click()"
                                    class="px-4 py-2 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-sm font-medium rounded-md hover:bg-indigo-200 dark:hover:bg-indigo-900 transition-colors"
                                >
                                    Seleccionar Archivo
                                </button>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ form.archivo_adjunto ? form.archivo_adjunto.name : 'Ningún archivo seleccionado' }}
                                </span>
                                <button 
                                    v-if="form.archivo_adjunto"
                                    type="button" 
                                    @click="form.archivo_adjunto = null" 
                                    class="text-gray-400 hover:text-red-500"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <InputError :message="form.errors.archivo_adjunto" class="mt-2" />
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- SECCIÓN 4: JUSTIFICACIÓN DEL REGISTRO         -->
                    <!-- ============================================== -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-indigo-600 px-6 py-3">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                                4. Justificación del Registro
                            </h3>
                        </div>
                        
                        <div class="p-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <span class="text-red-500">*</span> Motivo/Observación Interna:
                            </label>
                            <textarea 
                                v-model="form.motivo_registro"
                                rows="3"
                                required
                                minlength="10"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm resize-none"
                                placeholder="Oferta recibida por email tras solicitud telefónica."
                            ></textarea>
                            
                            <InputError :message="form.errors.motivo_registro" class="mt-2" />
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- BOTONES DE ACCIÓN                             -->
                    <!-- ============================================== -->
                    <div class="flex items-center justify-between pt-2">
                        <Link :href="route('ofertas.index')">
                            <button 
                                type="button"
                                class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                Cancelar
                            </button>
                        </Link>
                        
                        <div class="flex items-center gap-3">
                            <button 
                                type="button"
                                @click="submitYOtra"
                                :disabled="form.processing || !motivoValido"
                                class="px-5 py-2.5 bg-indigo-500 hover:bg-indigo-600 disabled:bg-indigo-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-md shadow-sm transition-colors"
                            >
                                Guardar y Registrar Otra
                            </button>
                            
                            <button 
                                type="submit"
                                :disabled="form.processing || !motivoValido"
                                class="px-5 py-2.5 bg-indigo-700 hover:bg-indigo-800 disabled:bg-indigo-400 disabled:cursor-not-allowed text-white text-sm font-medium rounded-md shadow-sm transition-colors inline-flex items-center"
                            >
                                {{ form.processing ? 'Guardando...' : 'Guardar Oferta' }}
                                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                </form>
            </div>
        </div>
    </AppLayout>
</template>
