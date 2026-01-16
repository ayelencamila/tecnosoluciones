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

const getEstadoConfig = (estado) => {
    const config = {
        'pendiente': { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', icon: '⏳' },
        'aprobada': { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', icon: '✓' },
        'rechazada': { bg: 'bg-rose-50', text: 'text-rose-700', border: 'border-rose-200', icon: '✗' },
    };
    return config[estado] || { bg: 'bg-gray-50', text: 'text-gray-700', border: 'border-gray-200', icon: '?' };
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

const estadoConfig = getEstadoConfig(props.bonificacion.estado);
</script>

<template>
    <Head title="Detalle de Bonificación" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detalle de Bonificación
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Breadcrumb -->
                <nav class="flex items-center mb-6">
                    <ol class="flex items-center space-x-2 text-sm">
                        <li>
                            <Link :href="route('dashboard')" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                </svg>
                            </Link>
                        </li>
                        <li class="text-gray-300">/</li>
                        <li>
                            <Link :href="route('bonificaciones.index')" class="text-gray-500 hover:text-gray-700 transition-colors">
                                Bonificaciones
                            </Link>
                        </li>
                        <li class="text-gray-300">/</li>
                        <li class="text-gray-900 font-medium">
                            #{{ bonificacion.reparacion.codigo_reparacion }}
                        </li>
                    </ol>
                </nav>

                <!-- Header Card con Estado -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-5">
                            <div :class="[estadoConfig.bg, estadoConfig.border]"
                                 class="w-16 h-16 rounded-xl border-2 flex items-center justify-center">
                                <span class="text-2xl">{{ estadoConfig.icon }}</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <span :class="[estadoConfig.bg, estadoConfig.text, estadoConfig.border]"
                                          class="px-3 py-1 text-xs font-bold rounded-full border uppercase tracking-wide">
                                        {{ bonificacion.estado }}
                                    </span>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Solicitud de Bonificación
                                </h3>
                                <p class="text-sm text-gray-500">
                                    Reparación <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">#{{ bonificacion.reparacion.codigo_reparacion }}</span>
                                </p>
                            </div>
                        </div>

                        <div v-if="bonificacion.estado === 'pendiente'" class="flex gap-3">
                            <button @click="abrirModalAprobar"
                                    type="button"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Aprobar
                            </button>
                            <button @click="showRechazarModal = true"
                                    type="button"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-rose-600 font-medium rounded-lg border border-rose-200 hover:bg-rose-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Rechazar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Información del Cliente -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <h4 class="text-sm font-semibold text-gray-900">Cliente</h4>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <span class="text-xs text-gray-400 uppercase tracking-wide">Nombre</span>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ bonificacion.reparacion.cliente.nombre }} {{ bonificacion.reparacion.cliente.apellido }}
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 uppercase tracking-wide">Teléfono</span>
                                <p class="text-sm text-gray-700">{{ bonificacion.reparacion.cliente.telefono }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 uppercase tracking-wide">Email</span>
                                <p class="text-sm text-gray-700">{{ bonificacion.reparacion.cliente.email || '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Equipo -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h4 class="text-sm font-semibold text-gray-900">Equipo y Estado</h4>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <span class="text-xs text-gray-400 uppercase tracking-wide">Equipo</span>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ bonificacion.reparacion.equipo_marca }} {{ bonificacion.reparacion.equipo_modelo }}
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 uppercase tracking-wide">Técnico asignado</span>
                                <p class="text-sm text-gray-700">{{ bonificacion.reparacion.tecnico?.name || '—' }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 uppercase tracking-wide">Estado actual</span>
                                <p class="text-sm text-gray-700">{{ bonificacion.reparacion.estado?.nombreEstado || '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cálculo de Bonificación -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-900">Cálculo de Bonificación</h4>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-slate-50 rounded-xl p-5 text-center border border-slate-100">
                            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide mb-1">Monto Original</p>
                            <p class="text-2xl font-bold text-slate-900">{{ formatMonto(bonificacion.monto_original) }}</p>
                        </div>
                        <div class="bg-amber-50 rounded-xl p-5 text-center border border-amber-100">
                            <p class="text-xs text-amber-600 font-medium uppercase tracking-wide mb-1">% Sugerido</p>
                            <p class="text-2xl font-bold text-amber-700">{{ bonificacion.porcentaje_sugerido }}%</p>
                        </div>
                        <div class="bg-emerald-50 rounded-xl p-5 text-center border border-emerald-100">
                            <p class="text-xs text-emerald-600 font-medium uppercase tracking-wide mb-1">Bonificación</p>
                            <p class="text-2xl font-bold text-emerald-700">{{ formatMonto(bonificacion.monto_bonificado) }}</p>
                        </div>
                    </div>

                    <!-- Aprobación si existe -->
                    <div v-if="bonificacion.porcentaje_aprobado" 
                         class="bg-emerald-50 border border-emerald-200 rounded-xl p-5">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-xs text-emerald-600 font-medium uppercase tracking-wide">Bonificación Aprobada</p>
                                <p class="text-3xl font-bold text-emerald-700 mt-1">{{ bonificacion.porcentaje_aprobado }}%</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-emerald-600 font-medium uppercase tracking-wide">Monto Final</p>
                                <p class="text-3xl font-bold text-emerald-700 mt-1">
                                    {{ formatMonto((bonificacion.monto_original * bonificacion.porcentaje_aprobado) / 100) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Info adicional -->
                    <div class="mt-5 pt-5 border-t border-gray-100 grid grid-cols-2 gap-4">
                        <div v-if="bonificacion.dias_excedidos">
                            <span class="text-xs text-gray-400 uppercase tracking-wide">Días excedidos</span>
                            <p class="text-sm font-semibold text-rose-600">+{{ bonificacion.dias_excedidos }} días</p>
                        </div>
                        <div v-if="bonificacion.motivo_demora">
                            <span class="text-xs text-gray-400 uppercase tracking-wide">Motivo de demora</span>
                            <p class="text-sm font-medium text-gray-900">{{ bonificacion.motivo_demora.nombre }}</p>
                        </div>
                    </div>
                </div>

                <!-- Justificación del Técnico -->
                <div v-if="bonificacion.justificacion_tecnico" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-900">Justificación del Técnico</h4>
                    </div>
                    <p class="text-sm text-gray-700 bg-slate-50 rounded-lg p-4 border-l-2 border-slate-300">
                        {{ bonificacion.justificacion_tecnico }}
                    </p>
                </div>

                <!-- Información de la Decisión (Admin) -->
                <div v-if="bonificacion.estado !== 'pendiente'" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-900">Decisión del Administrador</h4>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs text-gray-400 uppercase tracking-wide">Decidido por</span>
                            <p class="text-sm font-medium text-gray-900">{{ bonificacion.aprobada_por?.name || '—' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 uppercase tracking-wide">Fecha</span>
                            <p class="text-sm text-gray-700">{{ formatFecha(bonificacion.fecha_aprobacion) }}</p>
                        </div>
                    </div>
                    <div v-if="bonificacion.observaciones_aprobacion" class="mt-4">
                        <span class="text-xs text-gray-400 uppercase tracking-wide">Observaciones</span>
                        <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3 mt-1">{{ bonificacion.observaciones_aprobacion }}</p>
                    </div>
                </div>

                <!-- Respuesta del Cliente -->
                <div v-if="bonificacion.estado === 'aprobada'" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-cyan-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-900">Respuesta del Cliente</h4>
                    </div>
                    
                    <!-- Pendiente -->
                    <div v-if="bonificacion.decision_cliente === 'pendiente'" 
                         class="bg-amber-50 border border-amber-200 rounded-xl p-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-amber-800">Esperando respuesta</p>
                                <p class="text-sm text-amber-700">Se envió notificación por WhatsApp al cliente.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Aceptó -->
                    <div v-else-if="bonificacion.decision_cliente === 'aceptar'" 
                         class="bg-emerald-50 border border-emerald-200 rounded-xl p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-lg text-emerald-800">Cliente ACEPTÓ continuar</p>
                                <p class="text-sm text-emerald-600">{{ formatFecha(bonificacion.fecha_decision_cliente) }}</p>
                            </div>
                        </div>
                        <div v-if="bonificacion.observaciones_decision" 
                             class="bg-white rounded-lg p-3 border border-emerald-100">
                            <span class="text-xs text-emerald-600 font-medium">Observaciones:</span>
                            <p class="text-sm text-gray-700 mt-1">{{ bonificacion.observaciones_decision }}</p>
                        </div>
                    </div>

                    <!-- Canceló -->
                    <div v-else-if="bonificacion.decision_cliente === 'cancelar'" 
                         class="bg-rose-50 border border-rose-200 rounded-xl p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-rose-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-lg text-rose-800">Cliente CANCELÓ la reparación</p>
                                <p class="text-sm text-rose-600">{{ formatFecha(bonificacion.fecha_decision_cliente) }}</p>
                            </div>
                        </div>
                        <div v-if="bonificacion.observaciones_decision" 
                             class="bg-white rounded-lg p-3 border border-rose-100">
                            <span class="text-xs text-rose-600 font-medium">Observaciones:</span>
                            <p class="text-sm text-gray-700 mt-1">{{ bonificacion.observaciones_decision }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Aprobar -->
        <Modal :show="showAprobarModal" @close="showAprobarModal = false">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Aprobar Bonificación</h2>
                        <p class="text-sm text-gray-500">Reparación #{{ bonificacion.reparacion.codigo_reparacion }}</p>
                    </div>
                </div>

                <form @submit.prevent="aprobar">
                    <div class="mb-5">
                        <InputLabel for="porcentaje_ajustado" value="Porcentaje de Bonificación (%)" class="text-sm" />
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
                        <div class="mt-2 p-3 bg-emerald-50 rounded-lg">
                            <span class="text-sm text-emerald-700">
                                Monto a bonificar: <strong>{{ calcularMontoFinal() }}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="mb-5">
                        <InputLabel for="observaciones" value="Observaciones (opcional)" class="text-sm" />
                        <textarea 
                            id="observaciones" 
                            v-model="formAprobar.observaciones"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-sm"
                            rows="3"
                            placeholder="Agregue observaciones si lo considera necesario..."
                        ></textarea>
                        <InputError :message="formAprobar.errors.observaciones" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <SecondaryButton @click="showAprobarModal = false" type="button">
                            Cancelar
                        </SecondaryButton>
                        <button 
                            type="submit"
                            :disabled="formAprobar.processing"
                            :class="{ 'opacity-50 cursor-not-allowed': formAprobar.processing }"
                            class="px-5 py-2.5 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors"
                        >
                            Confirmar Aprobación
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal Rechazar -->
        <Modal :show="showRechazarModal" @close="showRechazarModal = false">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-rose-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Rechazar Bonificación</h2>
                        <p class="text-sm text-gray-500">Reparación #{{ bonificacion.reparacion.codigo_reparacion }}</p>
                    </div>
                </div>

                <form @submit.prevent="rechazar">
                    <div class="mb-5">
                        <InputLabel for="motivo_rechazo" value="Motivo del Rechazo *" class="text-sm" />
                        <textarea 
                            id="motivo_rechazo" 
                            v-model="formRechazar.motivo_rechazo"
                            class="mt-1 block w-full border-gray-300 focus:border-rose-500 focus:ring-rose-500 rounded-lg shadow-sm text-sm"
                            rows="4"
                            required
                            placeholder="Explique el motivo por el cual rechaza la bonificación..."
                        ></textarea>
                        <InputError :message="formRechazar.errors.motivo_rechazo" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <SecondaryButton @click="showRechazarModal = false" type="button">
                            Cancelar
                        </SecondaryButton>
                        <DangerButton 
                            :class="{ 'opacity-50 cursor-not-allowed': formRechazar.processing }" 
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
