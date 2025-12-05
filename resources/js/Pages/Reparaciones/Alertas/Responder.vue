<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    alerta: Object,
    motivos: Array,
});

const form = useForm({
    motivoDemoraID: '',
    es_factible: true,
    observaciones: '',
});

const submit = () => {
    form.post(route('alertas.responder', props.alerta.alertaReparacionID));
};

const getTipoAlertaColor = (tipo) => {
    return tipo === 'incumplimiento' 
        ? 'bg-red-100 text-red-800 border-red-300' 
        : 'bg-orange-100 text-orange-800 border-orange-300';
};

const formatFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Responder Alerta de SLA" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Responder Alerta de SLA
                </h2>
                <Link :href="route('alertas.index')">
                    <SecondaryButton>
                        ← Volver a Alertas
                    </SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Información de la alerta -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Detalles de la Alerta</h3>
                        <span :class="getTipoAlertaColor(alerta.tipo_alerta)"
                              class="px-4 py-2 text-sm font-bold rounded-lg border-2 uppercase">
                            {{ alerta.tipo_alerta }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Información del Cliente</h4>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="text-gray-500">Cliente:</span>
                                    <span class="font-semibold text-gray-900 ml-2">
                                        {{ alerta.reparacion.cliente.nombre }} {{ alerta.reparacion.cliente.apellido }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Teléfono:</span>
                                    <span class="text-gray-900 ml-2">{{ alerta.reparacion.cliente.telefono }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Código Reparación:</span>
                                    <span class="font-mono font-semibold text-gray-900 ml-2">
                                        #{{ alerta.reparacion.codigo_reparacion }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Detalles del Equipo</h4>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="text-gray-500">Equipo:</span>
                                    <span class="font-semibold text-gray-900 ml-2">
                                        {{ alerta.reparacion.equipo_marca }} {{ alerta.reparacion.equipo_modelo }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Fecha de ingreso:</span>
                                    <span class="text-gray-900 ml-2">{{ formatFecha(alerta.reparacion.fecha_ingreso) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Estado actual:</span>
                                    <span class="text-gray-900 ml-2">{{ alerta.reparacion.estado?.nombreEstado || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Métricas de SLA -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Estado del SLA</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="text-xs text-blue-600 font-medium">SLA Vigente</div>
                                <div class="text-2xl font-bold text-blue-900">{{ alerta.sla_vigente }} días</div>
                            </div>
                            <div class="bg-orange-50 rounded-lg p-4">
                                <div class="text-xs text-orange-600 font-medium">Días Efectivos</div>
                                <div class="text-2xl font-bold text-orange-900">{{ alerta.dias_efectivos }} días</div>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4">
                                <div class="text-xs text-red-600 font-medium">Días Excedidos</div>
                                <div class="text-2xl font-bold text-red-900">+{{ alerta.dias_excedidos }} días</div>
                            </div>
                        </div>
                    </div>

                    <!-- Falla declarada -->
                    <div v-if="alerta.reparacion.falla_declarada" class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Falla Declarada</h4>
                        <p class="text-sm text-gray-600 bg-gray-50 rounded p-3">
                            {{ alerta.reparacion.falla_declarada }}
                        </p>
                    </div>
                </div>

                <!-- Formulario de respuesta -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Registrar Respuesta</h3>

                    <form @submit.prevent="submit">
                        
                        <div class="mb-6">
                            <InputLabel for="motivoDemoraID" value="Motivo de la Demora *" />
                            <select 
                                id="motivoDemoraID" 
                                v-model="form.motivoDemoraID"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="">Seleccione un motivo...</option>
                                <option v-for="motivo in motivos" 
                                        :key="motivo.motivoDemoraID" 
                                        :value="motivo.motivoDemoraID">
                                    {{ motivo.nombre }}
                                    <span v-if="motivo.requiere_bonificacion"> (Requiere bonificación)</span>
                                </option>
                            </select>
                            <InputError :message="form.errors.motivoDemoraID" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <InputLabel for="es_factible" value="¿Es factible completar la reparación? *" />
                            <div class="mt-2 space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" 
                                           v-model="form.es_factible" 
                                           :value="true"
                                           class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">Sí, es factible completarla</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" 
                                           v-model="form.es_factible" 
                                           :value="false"
                                           class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">No es factible (se consultará al cliente)</span>
                                </label>
                            </div>
                            <InputError :message="form.errors.es_factible" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <InputLabel for="observaciones" value="Observaciones (opcional)" />
                            <textarea 
                                id="observaciones" 
                                v-model="form.observaciones"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                rows="4"
                                placeholder="Detalles adicionales sobre la situación..."
                            ></textarea>
                            <InputError :message="form.errors.observaciones" class="mt-2" />
                            <p class="text-xs text-gray-500 mt-1">
                                Máximo 1000 caracteres
                            </p>
                        </div>

                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Si el motivo seleccionado requiere bonificación y la reparación es factible, 
                                        se generará automáticamente una solicitud de bonificación para aprobación.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <Link :href="route('alertas.index')">
                                <SecondaryButton type="button">
                                    Cancelar
                                </SecondaryButton>
                            </Link>
                            <PrimaryButton 
                                :class="{ 'opacity-25': form.processing }" 
                                :disabled="form.processing"
                            >
                                Enviar Respuesta
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
