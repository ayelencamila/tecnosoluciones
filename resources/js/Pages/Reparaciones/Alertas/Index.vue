<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    alertas: Object,
});

const alertasPendientes = computed(() => {
    return props.alertas.data.filter(a => !a.leida);
});

const alertasLeidas = computed(() => {
    return props.alertas.data.filter(a => a.leida);
});

const getTipoAlertaColor = (tipoAlerta) => {
    const nombre = tipoAlerta?.nombre || '';
    return nombre === 'sla_excedido' || nombre === 'incumplimiento'
        ? 'bg-red-100 text-red-800' 
        : 'bg-orange-100 text-orange-800';
};

const getTipoAlertaNombre = (tipoAlerta) => {
    return tipoAlerta?.descripcion || 'SLA Excedido';
};

const formatFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Mis Alertas de SLA" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mis Alertas de SLA
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Estadísticas rápidas -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-500">Total Alertas</div>
                        <div class="text-3xl font-bold text-gray-900">{{ alertas.data.length }}</div>
                    </div>
                    <div class="bg-orange-50 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-orange-600">Pendientes</div>
                        <div class="text-3xl font-bold text-orange-900">{{ alertasPendientes.length }}</div>
                    </div>
                    <div class="bg-green-50 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-green-600">Respondidas</div>
                        <div class="text-3xl font-bold text-green-900">{{ alertasLeidas.length }}</div>
                    </div>
                </div>

                <!-- Alertas pendientes -->
                <div v-if="alertasPendientes.length > 0" class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">⚠️ Alertas Pendientes de Respuesta</h3>
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="divide-y divide-gray-200">
                            <div v-for="alerta in alertasPendientes" :key="alerta.alertaReparacionID"
                                 class="p-6 hover:bg-gray-50 transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span :class="getTipoAlertaColor(alerta.tipo_alerta)"
                                                  class="px-3 py-1 text-xs font-semibold rounded-full uppercase">
                                                {{ getTipoAlertaNombre(alerta.tipo_alerta) }}
                                            </span>
                                            <span class="text-sm text-gray-500">
                                                Reparación #{{ alerta.reparacion.codigo_reparacion }}
                                            </span>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <p class="text-sm font-medium text-gray-900">
                                                Cliente: {{ alerta.reparacion.cliente.nombre }} {{ alerta.reparacion.cliente.apellido }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Equipo: {{ alerta.reparacion.equipo_marca }} {{ alerta.reparacion.equipo_modelo }}
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-500">SLA vigente:</span>
                                                <span class="font-semibold text-gray-900 ml-1">{{ alerta.sla_vigente }} días</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Días efectivos:</span>
                                                <span class="font-semibold text-gray-900 ml-1">{{ alerta.dias_efectivos }} días</span>
                                            </div>
                                            <div>
                                                <span class="text-red-600">Días excedidos:</span>
                                                <span class="font-bold text-red-700 ml-1">{{ alerta.dias_excedidos }} días</span>
                                            </div>
                                        </div>

                                        <div class="mt-2 text-xs text-gray-400">
                                            Alerta generada: {{ formatFecha(alerta.created_at) }}
                                        </div>
                                    </div>

                                    <Link :href="route('alertas.show', alerta.alertaReparacionID)"
                                          class="ml-4 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition">
                                        Responder
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertas leídas -->
                <div v-if="alertasLeidas.length > 0">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">✓ Alertas Respondidas</h3>
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="divide-y divide-gray-200">
                            <div v-for="alerta in alertasLeidas" :key="alerta.alertaReparacionID"
                                 class="p-6 opacity-75">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span :class="getTipoAlertaColor(alerta.tipo_alerta)"
                                                  class="px-3 py-1 text-xs font-semibold rounded-full uppercase">
                                                {{ getTipoAlertaNombre(alerta.tipo_alerta) }}
                                            </span>
                                            <span class="text-sm text-gray-500">
                                                Reparación #{{ alerta.reparacion.codigo_reparacion }}
                                            </span>
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded">
                                                Respondida
                                            </span>
                                        </div>
                                        
                                        <div class="text-sm text-gray-600">
                                            Cliente: {{ alerta.reparacion.cliente.nombre }} {{ alerta.reparacion.cliente.apellido }}
                                        </div>

                                        <div class="mt-2 text-xs text-gray-400">
                                            Respondida: {{ formatFecha(alerta.fecha_lectura) }}
                                        </div>
                                    </div>

                                    <Link :href="route('alertas.show', alerta.alertaReparacionID)"
                                          class="ml-4 px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300 transition">
                                        Ver Detalle
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sin alertas -->
                <div v-if="alertas.data.length === 0" 
                     class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay alertas</h3>
                    <p class="mt-1 text-sm text-gray-500">Excelente trabajo, todas las reparaciones están dentro del SLA.</p>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
