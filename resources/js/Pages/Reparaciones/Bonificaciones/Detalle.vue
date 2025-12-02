<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    bonificacion: Object,
});

const showAprobarModal = ref(false);
const showRechazarModal = ref(false);

const formAprobar = useForm({
    porcentaje_ajustado: props.bonificacion.porcentaje_sugerido,
    observaciones: '',
});

const formRechazar = useForm({
    motivo_rechazo: '',
});

const abrirModalAprobar = () => {
    console.log('Abriendo modal de aprobación');
    showAprobarModal.value = true;
};

const aprobar = () => {
    formAprobar.post(route('bonificaciones.aprobar', props.bonificacion.bonificacionID), {
        onSuccess: () => {
            showAprobarModal.value = false;
        }
    });
};

const rechazar = () => {
    formRechazar.post(route('bonificaciones.rechazar', props.bonificacion.bonificacionID), {
        onSuccess: () => {
            showRechazarModal.value = false;
        }
    });
};

const getEstadoColor = (estado) => {
    const colores = {
        'pendiente': 'bg-yellow-100 text-yellow-800 border-yellow-300',
        'aprobada': 'bg-green-100 text-green-800 border-green-300',
        'rechazada': 'bg-red-100 text-red-800 border-red-300',
    };
    return colores[estado] || 'bg-gray-100 text-gray-800';
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

const formatMonto = (monto) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
    }).format(monto);
};

const calcularMontoFinal = () => {
    const porcentaje = formAprobar.porcentaje_ajustado || 0;
    const monto = (props.bonificacion.monto_original * porcentaje) / 100;
    return formatMonto(monto);
};
</script>

<template>
    <Head title="Detalle de Bonificación" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalle de Bonificación
                </h2>
                <Link :href="route('bonificaciones.index')">
                    <SecondaryButton>
                        ← Volver al Listado
                    </SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Estado y acciones -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <span :class="getEstadoColor(bonificacion.estado)"
                                  class="px-6 py-3 text-lg font-bold rounded-lg border-2 uppercase">
                                {{ bonificacion.estado }}
                            </span>
                            <div>
                                <p class="text-sm text-gray-500">Solicitud de Bonificación</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    Reparación #{{ bonificacion.reparacion.codigo_reparacion }}
                                </p>
                            </div>
                        </div>

                        <div v-if="bonificacion.estado === 'pendiente'" class="flex gap-3">
                            <button @click="abrirModalAprobar"
                                    type="button"
                                    class="px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 transition">
                                ✓ Aprobar
                            </button>
                            <button @click="showRechazarModal = true"
                                    type="button"
                                    class="px-4 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 transition">
                                ✗ Rechazar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Información de la reparación -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Información de la Reparación</h3>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Cliente</h4>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="text-gray-500">Nombre:</span>
                                    <span class="font-semibold text-gray-900 ml-2">
                                        {{ bonificacion.reparacion.cliente.nombre }} {{ bonificacion.reparacion.cliente.apellido }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Teléfono:</span>
                                    <span class="text-gray-900 ml-2">{{ bonificacion.reparacion.cliente.telefono }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Email:</span>
                                    <span class="text-gray-900 ml-2">{{ bonificacion.reparacion.cliente.email || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Equipo y Estado</h4>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="text-gray-500">Equipo:</span>
                                    <span class="font-semibold text-gray-900 ml-2">
                                        {{ bonificacion.reparacion.equipo_marca }} {{ bonificacion.reparacion.equipo_modelo }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Técnico asignado:</span>
                                    <span class="text-gray-900 ml-2">{{ bonificacion.reparacion.tecnico?.name || 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Estado actual:</span>
                                    <span class="text-gray-900 ml-2">{{ bonificacion.reparacion.estado?.nombreEstado || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de la bonificación -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Cálculo de Bonificación</h3>
                    
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-xs text-blue-600 font-medium">Monto Original</div>
                            <div class="text-2xl font-bold text-blue-900">{{ formatMonto(bonificacion.monto_original) }}</div>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-4">
                            <div class="text-xs text-orange-600 font-medium">Porcentaje Sugerido</div>
                            <div class="text-2xl font-bold text-orange-900">{{ bonificacion.porcentaje_sugerido }}%</div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-xs text-green-600 font-medium">Bonificación Sugerida</div>
                            <div class="text-2xl font-bold text-green-900">{{ formatMonto(bonificacion.monto_bonificado) }}</div>
                        </div>
                    </div>

                    <div v-if="bonificacion.porcentaje_aprobado" 
                         class="border-t border-gray-200 pt-4">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-green-700">Bonificación Aprobada</p>
                                    <p class="text-2xl font-bold text-green-900">{{ bonificacion.porcentaje_aprobado }}%</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-green-600">Monto Final</p>
                                    <p class="text-2xl font-bold text-green-900">
                                        {{ formatMonto((bonificacion.monto_original * bonificacion.porcentaje_aprobado) / 100) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2 text-sm">
                        <div v-if="bonificacion.dias_excedidos">
                            <span class="text-gray-500">Días excedidos del SLA:</span>
                            <span class="font-semibold text-red-600 ml-2">+{{ bonificacion.dias_excedidos }} días</span>
                        </div>
                        <div v-if="bonificacion.motivo_demora">
                            <span class="text-gray-500">Motivo de demora:</span>
                            <span class="font-semibold text-gray-900 ml-2">{{ bonificacion.motivo_demora.nombre }}</span>
                        </div>
                    </div>
                </div>

                <!-- Justificación -->
                <div v-if="bonificacion.justificacion_tecnico" class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Justificación del Técnico</h3>
                    <p class="text-sm text-gray-700 bg-gray-50 rounded p-4">
                        {{ bonificacion.justificacion_tecnico }}
                    </p>
                </div>

                <!-- Información de decisión -->
                <div v-if="bonificacion.estado !== 'pendiente'" class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Información de la Decisión (Admin)</h3>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">Decidido por:</span>
                            <span class="font-semibold text-gray-900 ml-2">{{ bonificacion.aprobada_por?.name || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Fecha:</span>
                            <span class="text-gray-900 ml-2">{{ formatFecha(bonificacion.fecha_aprobacion) }}</span>
                        </div>
                        <div v-if="bonificacion.observaciones_aprobacion">
                            <span class="text-gray-500">Observaciones:</span>
                            <p class="text-gray-900 mt-1 bg-gray-50 rounded p-3">{{ bonificacion.observaciones_aprobacion }}</p>
                        </div>
                    </div>
                </div>

                <!-- Decisión del Cliente -->
                <div v-if="bonificacion.estado === 'aprobada'" class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Respuesta del Cliente</h3>
                    
                    <div v-if="bonificacion.decision_cliente === 'pendiente'" 
                         class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 text-yellow-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-semibold">Esperando respuesta del cliente</span>
                        </div>
                        <p class="text-sm text-yellow-700 mt-2">
                            Se envió una notificación por WhatsApp al cliente. Esperando que acepte o cancele la reparación.
                        </p>
                    </div>

                    <div v-else-if="bonificacion.decision_cliente === 'aceptar'" 
                         class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 text-green-800 mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-bold text-lg">Cliente ACEPTÓ continuar con la reparación</span>
                        </div>
                        <div class="space-y-1 text-sm">
                            <div>
                                <span class="text-gray-600">Fecha de respuesta:</span>
                                <span class="text-gray-900 ml-2 font-semibold">{{ formatFecha(bonificacion.fecha_decision_cliente) }}</span>
                            </div>
                            <div v-if="bonificacion.observaciones_decision">
                                <span class="text-gray-600">Observaciones:</span>
                                <p class="text-gray-900 mt-1 bg-white rounded p-2 border border-green-100">
                                    {{ bonificacion.observaciones_decision }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="bonificacion.decision_cliente === 'cancelar'" 
                         class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 text-red-800 mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-bold text-lg">Cliente CANCELÓ la reparación</span>
                        </div>
                        <div class="space-y-1 text-sm">
                            <div>
                                <span class="text-gray-600">Fecha de respuesta:</span>
                                <span class="text-gray-900 ml-2 font-semibold">{{ formatFecha(bonificacion.fecha_decision_cliente) }}</span>
                            </div>
                            <div v-if="bonificacion.observaciones_decision">
                                <span class="text-gray-600">Observaciones:</span>
                                <p class="text-gray-900 mt-1 bg-white rounded p-2 border border-red-100">
                                    {{ bonificacion.observaciones_decision }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Aprobar -->
        <Modal :show="showAprobarModal" @close="showAprobarModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Aprobar Bonificación
                </h2>

                <form @submit.prevent="aprobar">
                    <div class="mb-4">
                        <InputLabel for="porcentaje_ajustado" value="Porcentaje de Bonificación (%)" />
                        <TextInput 
                            id="porcentaje_ajustado" 
                            v-model.number="formAprobar.porcentaje_ajustado" 
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            class="mt-1 block w-full" 
                        />
                        <InputError :message="formAprobar.errors.porcentaje_ajustado" class="mt-2" />
                        <p class="text-sm text-gray-500 mt-1">
                            Monto a bonificar: {{ calcularMontoFinal() }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <InputLabel for="observaciones" value="Observaciones (opcional)" />
                        <textarea 
                            id="observaciones" 
                            v-model="formAprobar.observaciones"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            rows="3"
                        ></textarea>
                        <InputError :message="formAprobar.errors.observaciones" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="showAprobarModal = false" type="button">
                            Cancelar
                        </SecondaryButton>
                        <PrimaryButton 
                            :class="{ 'opacity-25': formAprobar.processing }" 
                            :disabled="formAprobar.processing"
                        >
                            Confirmar Aprobación
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal Rechazar -->
        <Modal :show="showRechazarModal" @close="showRechazarModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Rechazar Bonificación
                </h2>

                <form @submit.prevent="rechazar">
                    <div class="mb-4">
                        <InputLabel for="motivo_rechazo" value="Motivo del Rechazo *" />
                        <textarea 
                            id="motivo_rechazo" 
                            v-model="formRechazar.motivo_rechazo"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            rows="4"
                            required
                        ></textarea>
                        <InputError :message="formRechazar.errors.motivo_rechazo" class="mt-2" />
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="showRechazarModal = false" type="button">
                            Cancelar
                        </SecondaryButton>
                        <DangerButton 
                            :class="{ 'opacity-25': formRechazar.processing }" 
                            :disabled="formRechazar.processing"
                        >
                            Confirmar Rechazo
                        </DangerButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
