<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    producto: Object,
    ofertas: Array,
    filters: Object,
    comparacionSignificativa: { type: Boolean, default: true },
});

// Mejor precio
const mejorPrecio = computed(() => {
    const precios = props.ofertas.map(o => {
        const detalle = o.detalles.find(d => d.producto_id === props.producto.id);
        return detalle ? { ofertaId: o.id, precio: parseFloat(detalle.precio_unitario) } : null;
    }).filter(Boolean);
    if (precios.length === 0) return null;
    return precios.reduce((min, p) => p.precio < min.precio ? p : min, precios[0]);
});

// Mejor plazo
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

// Mejor opción global
const mejorOpcionGlobal = computed(() => {
    if (mejorPrecio.value && mejorPlazo.value && mejorPrecio.value.ofertaId === mejorPlazo.value.ofertaId) {
        return mejorPrecio.value.ofertaId;
    }
    return null;
});

const getDetalle = (oferta) => oferta.detalles.find(d => d.producto_id === props.producto.id);

const calcularAhorro = (precioActual) => {
    const precios = props.ofertas.map(o => getDetalle(o)?.precio_unitario).filter(Boolean).map(p => parseFloat(p));
    if (precios.length < 2) return 0;
    const maxPrecio = Math.max(...precios);
    if (maxPrecio === 0) return 0;
    return ((maxPrecio - precioActual) / maxPrecio * 100).toFixed(1);
};

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

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', minimumFractionDigits: 2 }).format(value);
};
</script>

<template>
    <Head :title="`Comparar Ofertas - ${producto.nombre}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Comparación de Ofertas</h2>
                <Link :href="route('ofertas.index')" class="text-sm text-gray-600 hover:text-gray-900">
                    ← Volver
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- ENCABEZADO -->
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Comparar Ofertas</h2>
                    <p class="text-gray-600">Selecciona la mejor oferta para el producto.</p>
                </div>

                <!-- PRODUCTO -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ producto.nombre }}</h3>
                            <p class="text-sm text-gray-600 mt-1">Código: {{ producto.codigo }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Ofertas disponibles</p>
                        <p class="text-4xl font-bold text-indigo-600">{{ ofertas.length }}</p>
                    </div>
                </div>

                <!-- ALERTA: Una sola oferta -->
                <div v-if="!comparacionSignificativa" class="bg-amber-50 border-l-4 border-amber-500 rounded-lg p-4 mb-8">
                    <p class="text-sm text-amber-800">
                        <strong>Atención:</strong> Solo existe una oferta para este producto.
                    </p>
                </div>

                <!-- CARDS DE OFERTAS -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div v-for="(oferta, index) in ofertas" :key="oferta.id" 
                         class="bg-white rounded-lg shadow-md overflow-hidden border-2 transition-all duration-200 hover:shadow-xl"
                         :class="{
                             'border-indigo-500': mejorOpcionGlobal === oferta.id,
                             'border-green-500': mejorPrecio?.ofertaId === oferta.id && mejorOpcionGlobal !== oferta.id,
                             'border-gray-200': mejorPrecio?.ofertaId !== oferta.id && mejorOpcionGlobal !== oferta.id
                         }">
                        
                        <!-- Header con badge -->
                        <div class="px-4 py-3 text-center relative"
                             :class="{
                                 'bg-gradient-to-r from-indigo-500 to-indigo-600': mejorOpcionGlobal === oferta.id,
                                 'bg-gradient-to-r from-green-500 to-green-600': mejorPrecio?.ofertaId === oferta.id && mejorOpcionGlobal !== oferta.id,
                                 'bg-gradient-to-r from-gray-600 to-gray-700': mejorPrecio?.ofertaId !== oferta.id && mejorOpcionGlobal !== oferta.id
                             }">
                            
                            <!-- Badge posición -->
                            <div class="absolute top-2 left-2">
                                <span v-if="mejorOpcionGlobal === oferta.id" 
                                      class="inline-block px-3 py-1 text-xs font-bold bg-white text-indigo-600 rounded-full shadow">
                                    MEJOR OPCIÓN
                                </span>
                                <span v-else-if="mejorPrecio?.ofertaId === oferta.id" 
                                      class="inline-block px-3 py-1 text-xs font-bold bg-white text-green-600 rounded-full shadow">
                                    MEJOR PRECIO
                                </span>
                                <span v-else-if="mejorPlazo?.ofertaId === oferta.id" 
                                      class="inline-block px-3 py-1 text-xs font-bold bg-white text-blue-600 rounded-full shadow">
                                    MEJOR PLAZO
                                </span>
                            </div>

                            <div class="pt-6">
                                <Link :href="route('ofertas.show', oferta.id)" 
                                      class="text-lg font-bold text-white hover:underline">
                                    {{ oferta.codigo_oferta }}
                                </Link>
                                <p class="text-sm text-white opacity-90 mt-1">{{ oferta.proveedor.razon_social }}</p>
                            </div>
                        </div>

                        <!-- Precio destacado -->
                        <div class="px-6 py-6 bg-gray-50 text-center border-b border-gray-200">
                            <div v-if="getDetalle(oferta)">
                                <div class="text-4xl font-bold"
                                     :class="{
                                         'text-green-600': mejorPrecio?.ofertaId === oferta.id,
                                         'text-indigo-600': mejorOpcionGlobal === oferta.id && mejorPrecio?.ofertaId !== oferta.id,
                                         'text-gray-900': mejorPrecio?.ofertaId !== oferta.id && mejorOpcionGlobal !== oferta.id
                                     }">
                                    {{ formatCurrency(getDetalle(oferta).precio_unitario) }}
                                </div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider mt-1">Precio Unitario</p>
                                <div v-if="parseFloat(calcularAhorro(getDetalle(oferta).precio_unitario)) > 0" 
                                     class="mt-2 inline-block px-3 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded-full">
                                    +{{ calcularAhorro(getDetalle(oferta).precio_unitario) }}% más caro
                                </div>
                            </div>
                        </div>

                        <!-- Detalles -->
                        <div class="px-6 py-5 space-y-3">
                            <!-- Cantidad -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    Cantidad:
                                </div>
                                <span v-if="getDetalle(oferta)" class="text-sm font-semibold text-gray-900">
                                    {{ getDetalle(oferta).cantidad_ofrecida }} unidades
                                </span>
                            </div>

                            <!-- Entrega -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    Entrega:
                                </div>
                                <span v-if="getDetalle(oferta)">
                                    <span v-if="getDetalle(oferta).disponibilidad_inmediata" 
                                          class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded">
                                        Inmediata
                                    </span>
                                    <span v-else class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-orange-100 text-orange-700 rounded">
                                        {{ getDetalle(oferta).dias_entrega }} días
                                    </span>
                                </span>
                            </div>

                            <!-- Validez -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Validez:
                                </div>
                                <span v-if="oferta.validez_hasta" class="text-sm font-medium text-gray-900">
                                    {{ new Date(oferta.validez_hasta).toLocaleDateString('es-AR') }}
                                </span>
                                <span v-else class="text-xs text-gray-500">Sin límite</span>
                            </div>

                            <!-- Divider -->
                            <hr class="border-gray-200">

                            <!-- Total -->
                            <div class="flex items-center justify-between pt-2">
                                <span class="text-sm font-semibold text-gray-700">Total Oferta:</span>
                                <span class="text-xl font-bold text-indigo-600">
                                    {{ formatCurrency(oferta.total_estimado) }}
                                </span>
                            </div>

                            <!-- Estado -->
                            <div class="pt-2">
                                <span :class="estadoClass(oferta.estado.nombre)" 
                                      class="block text-center px-3 py-2 text-xs font-semibold rounded">
                                    {{ oferta.estado.nombre }}
                                </span>
                            </div>
                        </div>

                        <!-- Footer con acción -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <Link :href="route('ofertas.show', oferta.id)" 
                                  class="block w-full text-center px-4 py-2 text-sm font-semibold rounded-lg transition-colors"
                                  :class="{
                                      'bg-indigo-600 text-white hover:bg-indigo-700': mejorOpcionGlobal === oferta.id,
                                      'bg-green-600 text-white hover:bg-green-700': mejorPrecio?.ofertaId === oferta.id && mejorOpcionGlobal !== oferta.id,
                                      'bg-gray-600 text-white hover:bg-gray-700': mejorPrecio?.ofertaId !== oferta.id && mejorOpcionGlobal !== oferta.id
                                  }">
                                Ver Detalles
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Mensaje final -->
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Compare las ofertas y seleccione la mejor opción para su compra.
                    </p>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
