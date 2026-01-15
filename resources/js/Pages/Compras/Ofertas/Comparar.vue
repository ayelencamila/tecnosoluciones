<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    producto: Object,
    ofertas: Array,
    filters: Object,
    comparacionSignificativa: {
        type: Boolean,
        default: true
    },
});

// Modal de cancelación (Excepción 12a)
const showCancelarModal = ref(false);
const formCancelar = useForm({
    producto_id: props.producto.id,
    motivo: '',
});

// Ofertas seleccionadas para comparar
const seleccionadas = ref(props.ofertas.map(o => o.id));

// Encontrar mejor precio y mejor plazo
const mejorPrecio = computed(() => {
    const precios = props.ofertas.map(o => {
        const detalle = o.detalles.find(d => d.producto_id === props.producto.id);
        return detalle ? { ofertaId: o.id, precio: parseFloat(detalle.precio_unitario) } : null;
    }).filter(Boolean);
    
    if (precios.length === 0) return null;
    return precios.reduce((min, p) => p.precio < min.precio ? p : min, precios[0]);
});

const mejorPlazo = computed(() => {
    const plazos = props.ofertas.map(o => {
        const detalle = o.detalles.find(d => d.producto_id === props.producto.id);
        if (!detalle) return null;
        const dias = detalle.disponibilidad_inmediata ? 0 : (detalle.dias_entrega || 999);
        return { ofertaId: o.id, dias };
    }).filter(Boolean);
    
    if (plazos.length === 0) return null;
    return plazos.reduce((min, p) => p.dias < min.dias ? p : min, plazos[0]);
});

// Mejor opción global (mejor precio Y mejor plazo)
const mejorOpcionGlobal = computed(() => {
    if (mejorPrecio.value && mejorPlazo.value && mejorPrecio.value.ofertaId === mejorPlazo.value.ofertaId) {
        return mejorPrecio.value.ofertaId;
    }
    return null;
});

// Obtener detalle del producto para una oferta
const getDetalle = (oferta) => {
    return oferta.detalles.find(d => d.producto_id === props.producto.id);
};

// Calcular ahorro porcentual respecto al precio más alto
const calcularAhorro = (precioActual) => {
    const precios = props.ofertas
        .map(o => getDetalle(o)?.precio_unitario)
        .filter(Boolean)
        .map(p => parseFloat(p));
    
    if (precios.length < 2) return 0;
    const maxPrecio = Math.max(...precios);
    if (maxPrecio === 0) return 0;
    
    return ((maxPrecio - precioActual) / maxPrecio * 100).toFixed(1);
};

// Estado de la oferta
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

// Elegir oferta ganadora
const elegirOferta = (oferta) => {
    if (confirm(`¿Confirma elegir la oferta ${oferta.codigo_oferta} de ${oferta.proveedor.razon_social}?`)) {
        router.post(route('ofertas.elegir', oferta.id), {}, {
            preserveScroll: true,
        });
    }
};

// Cancelar evaluación (Excepción 12a)
const cancelarEvaluacion = () => {
    formCancelar.post(route('ofertas.cancelar-evaluacion'), {
        onSuccess: () => {
            showCancelarModal.value = false;
        },
    });
};

// Formateo de moneda
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { 
        style: 'currency', 
        currency: 'ARS',
        minimumFractionDigits: 2 
    }).format(value);
};
</script>

<template>
    <Head :title="`Comparar Ofertas - ${producto.nombre}`" />

    <AppLayout>
        <template #header>
            Comparación de Ofertas
        </template>

        <div class="max-w-7xl mx-auto">
            <!-- Información del Producto -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-6 mb-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-indigo-200 text-sm uppercase tracking-wider">Producto a Comparar</p>
                        <h2 class="text-2xl font-bold mt-1">{{ producto.nombre }}</h2>
                        <p class="text-indigo-200 mt-1">Código: {{ producto.codigo }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-indigo-200 text-sm">Ofertas Comparadas</p>
                        <p class="text-4xl font-bold">{{ ofertas.length }}</p>
                        <p v-if="!comparacionSignificativa" class="text-yellow-300 text-xs mt-1">
                            ⚠️ Solo hay una oferta
                        </p>
                    </div>
                </div>
            </div>

            <!-- Excepción 10a: Mensaje cuando solo hay una oferta -->
            <div v-if="!comparacionSignificativa" class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6 rounded-r-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-amber-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <h3 class="text-amber-800 font-semibold">Comparación no disponible</h3>
                        <p class="text-amber-700 text-sm mt-1">
                            Solo existe <strong>una oferta</strong> para este producto. No es posible realizar una comparación significativa.
                            Puede aprobar o rechazar esta oferta directamente.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Leyenda de indicadores (solo si hay comparación significativa) -->
            <div v-if="comparacionSignificativa" class="bg-white rounded-lg shadow-sm p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-6 text-sm">
                        <span class="flex items-center">
                            <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                            Mejor precio
                        </span>
                        <span class="flex items-center">
                            <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                            Mejor plazo de entrega
                        </span>
                        <span v-if="mejorOpcionGlobal" class="flex items-center">
                            <span class="w-3 h-3 bg-purple-500 rounded-full mr-2"></span>
                            Mejor opción global
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">
                        📊 Ordenado por precio (menor primero), luego por plazo
                    </p>
                </div>
            </div>

            <!-- Tabla Comparativa -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-48">
                                    Criterio
                                </th>
                                <th v-for="(oferta, index) in ofertas" :key="oferta.id" 
                                    class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[200px]"
                                    :class="{ 
                                        'bg-purple-50 border-t-4 border-purple-500': mejorOpcionGlobal === oferta.id,
                                        'bg-green-50': mejorPrecio?.ofertaId === oferta.id && mejorOpcionGlobal !== oferta.id
                                    }">
                                    <div class="space-y-1">
                                        <!-- Ranking (CU-21 Paso 10) -->
                                        <div v-if="comparacionSignificativa" class="mb-2">
                                            <span v-if="index === 0" class="inline-flex items-center px-2 py-1 text-xs font-bold bg-yellow-400 text-yellow-900 rounded-full">
                                                🥇 #1
                                            </span>
                                            <span v-else-if="index === 1" class="inline-flex items-center px-2 py-1 text-xs font-bold bg-gray-300 text-gray-800 rounded-full">
                                                🥈 #2
                                            </span>
                                            <span v-else-if="index === 2" class="inline-flex items-center px-2 py-1 text-xs font-bold bg-amber-600 text-white rounded-full">
                                                🥉 #3
                                            </span>
                                            <span v-else class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-200 text-gray-600 rounded-full">
                                                #{{ index + 1 }}
                                            </span>
                                        </div>
                                        <Link :href="route('ofertas.show', oferta.id)" class="text-indigo-600 hover:text-indigo-800 font-bold">
                                            {{ oferta.codigo_oferta }}
                                        </Link>
                                        <p class="text-gray-500 font-normal normal-case">{{ oferta.proveedor.razon_social }}</p>
                                        <span :class="estadoClass(oferta.estado.nombre)" class="px-2 py-0.5 text-xs rounded-full">
                                            {{ oferta.estado.nombre }}
                                        </span>
                                        <!-- Indicador de mejor opción global -->
                                        <div v-if="mejorOpcionGlobal === oferta.id" class="mt-2">
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-purple-100 text-purple-800 rounded-full">
                                                ⭐ Mejor opción
                                            </span>
                                        </div>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- Precio Unitario -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    💰 Precio Unitario
                                </td>
                                <td v-for="oferta in ofertas" :key="`precio-${oferta.id}`" 
                                    class="px-6 py-4 text-center"
                                    :class="{ 'bg-green-50': mejorPrecio?.ofertaId === oferta.id }">
                                    <div v-if="getDetalle(oferta)">
                                        <span class="text-lg font-bold" :class="mejorPrecio?.ofertaId === oferta.id ? 'text-green-600' : 'text-gray-900'">
                                            {{ formatCurrency(getDetalle(oferta).precio_unitario) }}
                                        </span>
                                        <div v-if="mejorPrecio?.ofertaId === oferta.id" class="flex items-center justify-center mt-1">
                                            <span class="text-green-600 text-xs font-semibold bg-green-100 px-2 py-0.5 rounded-full">
                                                ✓ Mejor precio
                                            </span>
                                        </div>
                                        <div v-else-if="calcularAhorro(getDetalle(oferta).precio_unitario) > 0" class="text-xs text-red-500 mt-1">
                                            +{{ calcularAhorro(getDetalle(oferta).precio_unitario) }}% más caro
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400 text-sm">N/A</span>
                                </td>
                            </tr>

                            <!-- Cantidad Ofrecida -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    📦 Cantidad Ofrecida
                                </td>
                                <td v-for="oferta in ofertas" :key="`cantidad-${oferta.id}`" class="px-6 py-4 text-center">
                                    <span v-if="getDetalle(oferta)" class="text-gray-900 font-semibold">
                                        {{ getDetalle(oferta).cantidad_ofrecida }} unidades
                                    </span>
                                    <span v-else class="text-gray-400 text-sm">N/A</span>
                                </td>
                            </tr>

                            <!-- Disponibilidad -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    🚚 Disponibilidad
                                </td>
                                <td v-for="oferta in ofertas" :key="`disp-${oferta.id}`" 
                                    class="px-6 py-4 text-center"
                                    :class="{ 'bg-blue-50': mejorPlazo?.ofertaId === oferta.id }">
                                    <div v-if="getDetalle(oferta)">
                                        <span v-if="getDetalle(oferta).disponibilidad_inmediata" 
                                              class="inline-flex items-center text-green-600 font-semibold">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Inmediata
                                        </span>
                                        <span v-else class="text-orange-600 font-medium">
                                            {{ getDetalle(oferta).dias_entrega }} días
                                        </span>
                                        <div v-if="mejorPlazo?.ofertaId === oferta.id" class="flex items-center justify-center mt-1">
                                            <span class="text-blue-600 text-xs font-semibold bg-blue-100 px-2 py-0.5 rounded-full">
                                                ✓ Mejor plazo
                                            </span>
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400 text-sm">N/A</span>
                                </td>
                            </tr>

                            <!-- Subtotal -->
                            <tr class="hover:bg-gray-50 bg-gray-25">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    🧮 Subtotal (Producto)
                                </td>
                                <td v-for="oferta in ofertas" :key="`subtotal-${oferta.id}`" class="px-6 py-4 text-center">
                                    <span v-if="getDetalle(oferta)" class="text-lg font-bold text-gray-900">
                                        {{ formatCurrency(getDetalle(oferta).cantidad_ofrecida * getDetalle(oferta).precio_unitario) }}
                                    </span>
                                    <span v-else class="text-gray-400 text-sm">N/A</span>
                                </td>
                            </tr>

                            <!-- Validez -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    📅 Válida hasta
                                </td>
                                <td v-for="oferta in ofertas" :key="`validez-${oferta.id}`" class="px-6 py-4 text-center">
                                    <span v-if="oferta.validez_hasta" class="text-gray-700">
                                        {{ new Date(oferta.validez_hasta).toLocaleDateString('es-AR') }}
                                    </span>
                                    <span v-else class="text-gray-400 text-sm">Sin límite</span>
                                </td>
                            </tr>

                            <!-- Total Oferta Completa -->
                            <tr class="bg-gray-100">
                                <td class="px-6 py-4 text-sm font-bold text-gray-900">
                                    💵 Total Oferta Completa
                                </td>
                                <td v-for="oferta in ofertas" :key="`total-${oferta.id}`" class="px-6 py-4 text-center">
                                    <span class="text-xl font-bold text-indigo-600">
                                        {{ formatCurrency(oferta.total_estimado) }}
                                    </span>
                                </td>
                            </tr>

                            <!-- Observaciones -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    📝 Observaciones
                                </td>
                                <td v-for="oferta in ofertas" :key="`obs-${oferta.id}`" class="px-6 py-4 text-center">
                                    <p v-if="oferta.observaciones" class="text-sm text-gray-600 max-w-xs mx-auto">
                                        {{ oferta.observaciones }}
                                    </p>
                                    <span v-else class="text-gray-400 text-sm">-</span>
                                </td>
                            </tr>

                            <!-- Acciones -->
                            <tr class="bg-gray-50">
                                <td class="px-6 py-4 text-sm font-bold text-gray-900">
                                    Acciones
                                </td>
                                <td v-for="oferta in ofertas" :key="`action-${oferta.id}`" class="px-6 py-4 text-center">
                                    <div class="space-y-2">
                                        <PrimaryButton 
                                            v-if="oferta.estado.nombre === 'Pendiente' || oferta.estado.nombre === 'Pre-aprobada'"
                                            @click="elegirOferta(oferta)"
                                            class="w-full justify-center"
                                            :class="{ 
                                                'bg-green-600 hover:bg-green-700': mejorPrecio?.ofertaId === oferta.id && mejorPlazo?.ofertaId === oferta.id 
                                            }">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Elegir
                                        </PrimaryButton>
                                        
                                        <span v-else-if="oferta.estado.nombre === 'Elegida'" 
                                              class="inline-flex items-center px-3 py-2 text-sm font-semibold text-green-700 bg-green-100 rounded-lg">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Elegida
                                        </span>

                                        <span v-else-if="oferta.estado.nombre === 'Procesada'" 
                                              class="inline-flex items-center px-3 py-2 text-sm font-semibold text-indigo-700 bg-indigo-100 rounded-lg">
                                            Procesada
                                        </span>

                                        <span v-else class="text-gray-400 text-sm">
                                            {{ oferta.estado.nombre }}
                                        </span>

                                        <Link :href="route('ofertas.show', oferta.id)" 
                                              class="block text-indigo-600 hover:text-indigo-800 text-sm">
                                            Ver detalle →
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Volver -->
            <div class="mt-6 flex justify-between items-center">
                <Link :href="route('ofertas.index')" class="text-gray-600 hover:text-gray-800 flex items-center">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver al listado
                </Link>

                <div class="flex items-center space-x-4">
                    <!-- Botón Cancelar Evaluación (Excepción 12a) -->
                    <SecondaryButton @click="showCancelarModal = true" class="text-gray-600">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancelar Evaluación
                    </SecondaryButton>
                    
                    <div class="text-sm text-gray-500">
                        CU-21 Paso 10: Comparación de ofertas
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Cancelar Evaluación (Excepción 12a) -->
        <Modal :show="showCancelarModal" @close="showCancelarModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Cancelar Evaluación de Ofertas
                </h2>
                <p class="text-sm text-gray-600 mb-4">
                    ¿Está seguro que desea cancelar la evaluación? Las ofertas permanecerán en estado 
                    <span class="font-semibold text-yellow-600">"Pendiente"</span> para futuras gestiones.
                </p>
                
                <div class="mb-4">
                    <InputLabel for="motivo" value="Motivo (opcional)" />
                    <textarea
                        id="motivo"
                        v-model="formCancelar.motivo"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        rows="2"
                        placeholder="Ej: Se requiere más información, esperando otras cotizaciones..."
                    ></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <PrimaryButton @click="showCancelarModal = false">
                        Continuar Evaluando
                    </PrimaryButton>
                    <SecondaryButton 
                        @click="cancelarEvaluacion"
                        :disabled="formCancelar.processing">
                        <span v-if="formCancelar.processing">Procesando...</span>
                        <span v-else>Sí, Cancelar</span>
                    </SecondaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
