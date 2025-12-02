<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    bonificaciones: Array,
});

const formatMonto = (monto) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
    }).format(monto);
};

const formatFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
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
</script>

<template>
    <Head title="Historial de Bonificaciones" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Historial de Bonificaciones
                </h2>
                <Link :href="route('bonificaciones.index')">
                    <button class="px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-md hover:bg-gray-300 transition">
                        ← Volver a Bonificaciones
                    </button>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Estadísticas -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-500">Total Solicitudes</div>
                        <div class="text-3xl font-bold text-gray-900">{{ stats.total }}</div>
                    </div>
                    <div class="bg-yellow-50 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-yellow-600">Pendientes</div>
                        <div class="text-3xl font-bold text-yellow-900">{{ stats.pendientes }}</div>
                    </div>
                    <div class="bg-green-50 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-green-600">Aprobadas</div>
                        <div class="text-3xl font-bold text-green-900">{{ stats.aprobadas }}</div>
                    </div>
                    <div class="bg-red-50 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-red-600">Rechazadas</div>
                        <div class="text-3xl font-bold text-red-900">{{ stats.rechazadas }}</div>
                    </div>
                    <div class="bg-indigo-50 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-indigo-600">Total Bonificado</div>
                        <div class="text-xl font-bold text-indigo-900">
                            {{ formatMonto(stats.monto_total_bonificado) }}
                        </div>
                    </div>
                </div>

                <!-- Tabla de historial -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Últimas 20 Bonificaciones</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reparación</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">% Sugerido</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">% Aprobado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Decidido Por</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="bonificacion in bonificaciones" :key="bonificacion.bonificacionID" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatFecha(bonificacion.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('bonificaciones.show', bonificacion.bonificacionID)"
                                              class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                            #{{ bonificacion.reparacion.codigo_reparacion }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ bonificacion.reparacion.cliente.nombre }} {{ bonificacion.reparacion.cliente.apellido }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div class="max-w-xs truncate">
                                            {{ bonificacion.motivo_demora?.nombre || 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                        {{ bonificacion.porcentaje_sugerido }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-semibold text-green-600">
                                        {{ bonificacion.porcentaje_aprobado ? bonificacion.porcentaje_aprobado + '%' : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        {{ formatMonto(bonificacion.monto_bonificado) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span :class="getEstadoColor(bonificacion.estado)"
                                              class="px-2 py-1 text-xs font-semibold rounded-full uppercase">
                                            {{ bonificacion.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        {{ bonificacion.aprobada_por?.name || '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
