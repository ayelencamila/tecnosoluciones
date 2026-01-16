<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    bonificaciones: Object,
    filtros: Object,
});

const filtroEstado = ref(props.filtros.estado || 'pendiente');

const cambiarFiltro = (estado) => {
    filtroEstado.value = estado;
    router.get(route('bonificaciones.index'), { estado }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getEstadoColor = (estado) => {
    const colores = {
        'pendiente': 'bg-amber-50 text-amber-700 border border-amber-200',
        'aprobada': 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'rechazada': 'bg-rose-50 text-rose-700 border border-rose-200',
    };
    return colores[estado] || 'bg-gray-50 text-gray-700 border border-gray-200';
};

const formatFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatMonto = (monto) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
    }).format(monto);
};
</script>

<template>
    <Head title="Gestión de Bonificaciones" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Bonificaciones
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Breadcrumb + Navegación -->
                <nav class="flex items-center justify-between mb-6">
                    <ol class="flex items-center space-x-2 text-sm">
                        <li>
                            <Link :href="route('dashboard')" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                </svg>
                            </Link>
                        </li>
                        <li class="text-gray-300">/</li>
                        <li class="text-gray-900 font-medium">Bonificaciones</li>
                    </ol>
                    
                    <Link :href="route('bonificaciones.historial')" 
                          class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-indigo-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Ver Historial
                    </Link>
                </nav>

                <!-- Filtros elegantes -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-medium text-gray-500">Filtrar:</span>
                        <div class="flex gap-2">
                            <button 
                                @click="cambiarFiltro('pendiente')"
                                :class="filtroEstado === 'pendiente' 
                                    ? 'bg-amber-50 text-amber-700 border-amber-300 ring-2 ring-amber-200' 
                                    : 'bg-white text-gray-600 border-gray-200 hover:border-amber-200 hover:text-amber-600'"
                                class="px-4 py-2 text-sm font-medium rounded-lg border transition-all">
                                Pendientes
                            </button>
                            <button 
                                @click="cambiarFiltro('aprobada')"
                                :class="filtroEstado === 'aprobada' 
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300 ring-2 ring-emerald-200' 
                                    : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-200 hover:text-emerald-600'"
                                class="px-4 py-2 text-sm font-medium rounded-lg border transition-all">
                                Aprobadas
                            </button>
                            <button 
                                @click="cambiarFiltro('rechazada')"
                                :class="filtroEstado === 'rechazada' 
                                    ? 'bg-rose-50 text-rose-700 border-rose-300 ring-2 ring-rose-200' 
                                    : 'bg-white text-gray-600 border-gray-200 hover:border-rose-200 hover:text-rose-600'"
                                class="px-4 py-2 text-sm font-medium rounded-lg border transition-all">
                                Rechazadas
                            </button>
                            <button 
                                @click="cambiarFiltro('')"
                                :class="!filtroEstado 
                                    ? 'bg-indigo-50 text-indigo-700 border-indigo-300 ring-2 ring-indigo-200' 
                                    : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-200 hover:text-indigo-600'"
                                class="px-4 py-2 text-sm font-medium rounded-lg border transition-all">
                                Todas
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de bonificaciones -->
                <div class="space-y-4">
                    <template v-if="bonificaciones.data.length > 0">
                        <div v-for="bonificacion in bonificaciones.data" 
                             :key="bonificacion.bonificacionID"
                             class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md hover:border-gray-200 transition-all">
                            
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <!-- Header con estado y código -->
                                        <div class="flex items-center gap-3 mb-4">
                                            <span :class="getEstadoColor(bonificacion.estado)"
                                                  class="px-3 py-1.5 text-xs font-semibold rounded-full uppercase tracking-wide">
                                                {{ bonificacion.estado }}
                                            </span>
                                            <span class="text-sm font-mono text-gray-500 bg-gray-50 px-2 py-1 rounded">
                                                #{{ bonificacion.reparacion.codigo_reparacion }}
                                            </span>
                                            <span v-if="bonificacion.dias_excedidos" 
                                                  class="inline-flex items-center gap-1 text-sm text-rose-600 font-medium">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                +{{ bonificacion.dias_excedidos }} días
                                            </span>
                                        </div>

                                        <!-- Info grid -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div class="space-y-2">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                    <span class="text-sm text-gray-900 font-medium">
                                                        {{ bonificacion.reparacion.cliente.nombre }} {{ bonificacion.reparacion.cliente.apellido }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                    Técnico: {{ bonificacion.reparacion.tecnico?.name || 'Sin asignar' }}
                                                </div>
                                                <div v-if="bonificacion.motivo_demora" class="flex items-center gap-2 text-sm text-gray-500">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                                    </svg>
                                                    {{ bonificacion.motivo_demora.nombre }}
                                                </div>
                                            </div>

                                            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                                                <div class="flex justify-between text-sm">
                                                    <span class="text-gray-500">Monto original</span>
                                                    <span class="font-semibold text-gray-900">
                                                        {{ formatMonto(bonificacion.monto_original) }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between text-sm">
                                                    <span class="text-gray-500">Bonificación sugerida</span>
                                                    <span class="font-semibold text-amber-600">
                                                        {{ bonificacion.porcentaje_sugerido }}% 
                                                        <span class="text-gray-400">({{ formatMonto(bonificacion.monto_bonificado) }})</span>
                                                    </span>
                                                </div>
                                                <div v-if="bonificacion.porcentaje_aprobado" 
                                                     class="flex justify-between text-sm pt-2 border-t border-gray-200">
                                                    <span class="text-gray-600 font-medium">Aprobada</span>
                                                    <span class="font-bold text-emerald-600">
                                                        {{ bonificacion.porcentaje_aprobado }}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Justificación -->
                                        <div v-if="bonificacion.justificacion_tecnico" 
                                             class="mt-4 text-sm text-gray-600 bg-slate-50 rounded-lg p-3 border-l-2 border-slate-300">
                                            <span class="font-medium text-gray-700">Justificación:</span> 
                                            {{ bonificacion.justificacion_tecnico }}
                                        </div>

                                        <!-- Footer con fechas -->
                                        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                                            <div class="text-xs text-gray-400 space-x-3">
                                                <span>Solicitud: {{ formatFecha(bonificacion.created_at) }}</span>
                                                <span v-if="bonificacion.fecha_aprobacion">
                                                    • Decisión: {{ formatFecha(bonificacion.fecha_aprobacion) }}
                                                </span>
                                                <span v-if="bonificacion.aprobada_por">
                                                    por {{ bonificacion.aprobada_por.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Acción -->
                                    <div class="ml-6">
                                        <Link :href="route('bonificaciones.show', bonificacion.bonificacionID)"
                                              class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-lg hover:bg-indigo-100 transition-colors">
                                            Ver detalle
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Sin bonificaciones -->
                    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                        <div class="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 mb-1">No hay bonificaciones</h3>
                        <p class="text-sm text-gray-500">
                            No se encontraron bonificaciones 
                            <span v-if="filtroEstado">con estado "{{ filtroEstado }}"</span>
                            <span v-else>registradas</span>.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
