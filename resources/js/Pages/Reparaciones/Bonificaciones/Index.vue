<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

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
        'pendiente': 'bg-yellow-100 text-yellow-800',
        'aprobada': 'bg-green-100 text-green-800',
        'rechazada': 'bg-red-100 text-red-800',
    };
    return colores[estado] || 'bg-gray-100 text-gray-800';
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
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestión de Bonificaciones
                </h2>
                <Link :href="route('bonificaciones.historial')">
                    <PrimaryButton>
                        📊 Ver Historial
                    </PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filtros -->
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-700">Filtrar por estado:</span>
                        <button 
                            @click="cambiarFiltro('pendiente')"
                            :class="filtroEstado === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600'"
                            class="px-3 py-1 text-sm font-medium rounded-md hover:bg-yellow-50 transition">
                            Pendientes
                        </button>
                        <button 
                            @click="cambiarFiltro('aprobada')"
                            :class="filtroEstado === 'aprobada' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                            class="px-3 py-1 text-sm font-medium rounded-md hover:bg-green-50 transition">
                            Aprobadas
                        </button>
                        <button 
                            @click="cambiarFiltro('rechazada')"
                            :class="filtroEstado === 'rechazada' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-600'"
                            class="px-3 py-1 text-sm font-medium rounded-md hover:bg-red-50 transition">
                            Rechazadas
                        </button>
                        <button 
                            @click="cambiarFiltro('')"
                            :class="!filtroEstado ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-600'"
                            class="px-3 py-1 text-sm font-medium rounded-md hover:bg-indigo-50 transition">
                            Todas
                        </button>
                    </div>
                </div>

                <!-- Lista de bonificaciones -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div v-if="bonificaciones.data.length > 0" class="divide-y divide-gray-200">
                        <div v-for="bonificacion in bonificaciones.data" 
                             :key="bonificacion.bonificacionID"
                             class="p-6 hover:bg-gray-50 transition">
                            
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span :class="getEstadoColor(bonificacion.estado)"
                                              class="px-3 py-1 text-xs font-semibold rounded-full uppercase">
                                            {{ bonificacion.estado }}
                                        </span>
                                        <span class="text-sm font-mono text-gray-600">
                                            Reparación #{{ bonificacion.reparacion.codigo_reparacion }}
                                        </span>
                                        <span v-if="bonificacion.dias_excedidos" 
                                              class="text-sm text-red-600 font-medium">
                                            +{{ bonificacion.dias_excedidos }} días excedidos
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-6 mb-3">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                Cliente: {{ bonificacion.reparacion.cliente.nombre }} 
                                                {{ bonificacion.reparacion.cliente.apellido }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Técnico: {{ bonificacion.reparacion.tecnico?.name || 'N/A' }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Motivo: {{ bonificacion.motivo_demora?.nombre || 'N/A' }}
                                            </p>
                                        </div>

                                        <div class="space-y-1">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500">Monto original:</span>
                                                <span class="font-semibold text-gray-900">
                                                    {{ formatMonto(bonificacion.monto_original) }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500">Bonificación sugerida:</span>
                                                <span class="font-semibold text-orange-600">
                                                    {{ bonificacion.porcentaje_sugerido }}% 
                                                    ({{ formatMonto(bonificacion.monto_bonificado) }})
                                                </span>
                                            </div>
                                            <div v-if="bonificacion.porcentaje_aprobado" 
                                                 class="flex justify-between text-sm border-t border-gray-200 pt-1">
                                                <span class="text-gray-700 font-medium">Bonificación aprobada:</span>
                                                <span class="font-bold text-green-600">
                                                    {{ bonificacion.porcentaje_aprobado }}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="bonificacion.justificacion_tecnico" class="text-sm text-gray-600 bg-gray-50 rounded p-2 mb-2">
                                        <span class="font-medium">Justificación técnico:</span> 
                                        {{ bonificacion.justificacion_tecnico }}
                                    </div>

                                    <div class="text-xs text-gray-400">
                                        Solicitud: {{ formatFecha(bonificacion.created_at) }}
                                        <span v-if="bonificacion.fecha_aprobacion">
                                            | Decisión: {{ formatFecha(bonificacion.fecha_aprobacion) }}
                                        </span>
                                        <span v-if="bonificacion.aprobada_por">
                                            | Por: {{ bonificacion.aprobada_por.name }}
                                        </span>
                                    </div>
                                </div>

                                <div class="ml-4 flex flex-col gap-2">
                                    <Link :href="route('bonificaciones.show', bonificacion.bonificacionID)"
                                          class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition text-center">
                                        Ver Detalle
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sin bonificaciones -->
                    <div v-else class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay bonificaciones</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            No se encontraron bonificaciones con el estado "{{ filtroEstado || 'todos' }}".
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
