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
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Gestión de Compras
                    </h2>
                    <p class="text-sm font-medium text-indigo-800 dark:text-indigo-300 tracking-wide mt-1">
                        Ofertas › Registrar Oferta de Compra
                    </p>
                </div>
                <Link :href="route('ofertas.index')" 
                      class="px-5 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- TÍTULO DE LA PANTALLA (en el cuerpo) -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        Registrar Nueva Oferta
                    </h1>
                    <p v-if="contextoSolicitud" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Solicitud #{{ contextoSolicitud.codigo }} · {{ contextoSolicitud.producto }} · {{ contextoSolicitud.cantidad }} Unid.
                    </p>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Sin Solicitud Previa
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    
                    <!-- ============================================== -->
                    <!-- PANEL PRINCIPAL CON TODO EL FORMULARIO        -->
                    <!-- ============================================== -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        
                        <!-- PROVEEDOR Y PRODUCTO -->
                        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Proveedor -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="text-red-500">*</span> Proveedor:
                                    </label>
                                    
                                    <!-- Si viene precargado -->
                                    <div v-if="props.datosPrecargados?.proveedor" 
                                         class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-sm">
                                        {{ props.datosPrecargados.proveedor.razon_social }}
                                        <span class="text-xs text-gray-500 ml-2">(desde cotización)</span>
                                    </div>
                                    
                                    <!-- Selector normal -->
                                    <select 
                                        v-else
                                        v-model="form.proveedor_id"
                                        required
                                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    >
                                        <option value="">Seleccionar Proveedor de la lista</option>
                                        <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">
                                            {{ prov.razon_social }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.proveedor_id" class="mt-1" />
                                    
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                                        ¿El proveedor no aparece? 
                                        <Link :href="route('proveedores.index')" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                            Administrar Proveedores
                                        </Link>
                                    </p>
                                </div>

                                <!-- Producto -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="text-red-500">*</span> Producto:
                                    </label>
                                    
                                    <!-- Sin solicitud: dropdown para seleccionar producto -->
                                    <template v-if="!tieneSolicitud">
                                        <select 
                                            v-model="form.producto_id"
                                            required
                                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
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
                                        <div class="flex items-center gap-3 py-2.5 px-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-md border border-indigo-200 dark:border-indigo-700">
                                            <svg class="w-5 h-5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                    {{ productoPrecargado.codigo }} - {{ productoPrecargado.nombre }}
                                                </div>
                                                <div v-if="cantidadRequerida" class="text-xs text-gray-500 dark:text-gray-400">
                                                    Cantidad solicitada: {{ cantidadRequerida }} unidades
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- DETALLES DE LA OFERTA -->
                        <div class="px-6 py-5">
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                                </svg>
                                Detalles Comerciales
                            </h3>
                            
                            <!-- Grid para campos principales -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-5">
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
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-600 dark:text-gray-300">$</span>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0"
                                            v-model="form.precio_unitario"
                                            required
                                            class="w-full pl-7 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                        />
                                    </div>
                                    <InputError :message="form.errors.precio_unitario" class="mt-1" />
                                </div>
                                
                                <!-- Cantidad Ofertada -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <span class="text-red-500">*</span> Cantidad:
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

                                <!-- Subtotal (solo lectura) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Subtotal:
                                    </label>
                                    <div class="w-full px-4 py-2 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-md">
                                        <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ formatCurrency(subtotalEstimado) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Grid para disponibilidad y plazo -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
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
                                        <span class="text-red-500">*</span> Plazo Entrega (días):
                                    </label>
                                    <input 
                                        type="number" 
                                        min="0"
                                        v-model="form.dias_entrega"
                                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                        placeholder="0"
                                    />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Días hábiles desde la OC</p>
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
                        </div>

                        <!-- CONDICIONES Y ADJUNTOS -->
                        <div class="px-6 py-5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Condiciones del Proveedor -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Condiciones del Proveedor:
                                    </label>
                                    <textarea 
                                        v-model="form.condiciones_proveedor"
                                        rows="3"
                                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-white dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm resize-none"
                                        placeholder="Ej: Precio incluye envío. Garantía de 2 años."
                                    ></textarea>
                                </div>

                                <!-- Archivo adjunto -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Cotización Original (PDF/Imagen - Máx 10MB):
                                    </label>
                                    
                                    <div class="flex items-center gap-3">
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
                                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                                        >
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                            </svg>
                                            Seleccionar
                                        </button>
                                        <div class="flex-1 min-w-0">
                                            <span class="text-sm text-gray-600 dark:text-gray-400 truncate block">
                                                {{ form.archivo_adjunto ? form.archivo_adjunto.name : 'Ningún archivo' }}
                                            </span>
                                        </div>
                                        <button 
                                            v-if="form.archivo_adjunto"
                                            type="button" 
                                            @click="form.archivo_adjunto = null" 
                                            class="text-gray-400 hover:text-red-500 transition-colors"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <InputError :message="form.errors.archivo_adjunto" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <!-- JUSTIFICACIÓN -->
                        <div class="px-6 py-5 border-t border-gray-200 dark:border-gray-700">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <span class="text-red-500">*</span> Justificación / Observación Interna:
                            </label>
                            <textarea 
                                v-model="form.motivo_registro"
                                rows="3"
                                required
                                minlength="10"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm resize-none"
                                placeholder="Ej: Oferta recibida por email tras solicitud telefónica. El proveedor ofrece mejor plazo que la competencia."
                            ></textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mínimo 10 caracteres</p>
                            <InputError :message="form.errors.motivo_registro" class="mt-1" />
                        </div>

                        <!-- BOTONES DE ACCIÓN -->
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-750 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <Link :href="route('ofertas.index')">
                                <button 
                                    type="button"
                                    class="inline-flex items-center px-5 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Cancelar
                                </button>
                            </Link>
                            
                            <div class="flex items-center gap-3">
                                <button 
                                    type="button"
                                    @click="submitYOtra"
                                    :disabled="form.processing || !motivoValido"
                                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white text-sm font-medium rounded-md shadow-sm transition-colors"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Guardar y Registrar Otra
                                </button>
                                
                                <button 
                                    type="submit"
                                    :disabled="form.processing || !motivoValido"
                                    class="inline-flex items-center px-6 py-2.5 bg-indigo-700 hover:bg-indigo-800 disabled:bg-gray-400 disabled:cursor-not-allowed text-white text-sm font-bold rounded-md shadow-md transition-colors"
                                >
                                    <svg v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ form.processing ? 'Guardando...' : 'Guardar Oferta' }}
                                </button>
                            </div>
                        </div>
                    </div>
                    
                </form>
            </div>
        </div>
    </AppLayout>
</template>
