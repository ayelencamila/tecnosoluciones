<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import axios from 'axios';

const props = defineProps({
    alerta: Object,
    motivos: Array,
});

// Lista local de motivos (para actualizar sin recargar página)
const motivosLocales = ref([...props.motivos]);

const form = useForm({
    motivoDemoraID: '',
    es_factible: true,
    observaciones: '',
});

// Estado del modal de configuración
const showConfigModal = ref(false);
const configLoading = ref(false);
const configError = ref('');

// Formulario para nuevo motivo
const nuevoMotivo = ref({
    codigo: '',
    nombre: '',
    descripcion: '',
    requiere_bonificacion: false,
    pausa_sla: false,
});

// Computed para motivos activos
const motivosActivos = computed(() => {
    return motivosLocales.value.filter(m => m.activo);
});

const submit = () => {
    form.post(route('alertas.responder', props.alerta.alertaReparacionID));
};

const getTipoAlertaColor = (tipo) => {
    return tipo === 'sla_excedido' || tipo === 'incumplimiento'
        ? 'bg-red-100 text-red-700' 
        : 'bg-amber-100 text-amber-700';
};

const formatFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

// --- Funciones para configuración de motivos ---

const openConfigModal = async () => {
    showConfigModal.value = true;
    configError.value = '';
    await cargarMotivos();
};

const closeConfigModal = () => {
    showConfigModal.value = false;
    resetNuevoMotivo();
};

const cargarMotivos = async () => {
    try {
        configLoading.value = true;
        const response = await axios.get('/api/motivos-demora');
        motivosLocales.value = response.data;
    } catch (error) {
        configError.value = 'Error al cargar motivos';
    } finally {
        configLoading.value = false;
    }
};

const resetNuevoMotivo = () => {
    nuevoMotivo.value = {
        codigo: '',
        nombre: '',
        descripcion: '',
        requiere_bonificacion: false,
        pausa_sla: false,
    };
};

const generarCodigo = () => {
    // Generar código automático basado en el nombre
    const base = nuevoMotivo.value.nombre
        .toUpperCase()
        .replace(/[^A-Z0-9]/g, '_')
        .substring(0, 10);
    nuevoMotivo.value.codigo = `MOT_${base}_${Date.now().toString().slice(-4)}`;
};

const agregarMotivo = async () => {
    if (!nuevoMotivo.value.nombre.trim()) {
        configError.value = 'El nombre es requerido';
        return;
    }

    // Generar código si está vacío
    if (!nuevoMotivo.value.codigo.trim()) {
        generarCodigo();
    }

    try {
        configLoading.value = true;
        configError.value = '';
        
        const response = await axios.post('/api/motivos-demora', {
            ...nuevoMotivo.value,
            activo: true,
        });
        
        motivosLocales.value.push(response.data);
        resetNuevoMotivo();
    } catch (error) {
        configError.value = error.response?.data?.message || 'Error al crear motivo';
    } finally {
        configLoading.value = false;
    }
};

const toggleMotivo = async (motivo) => {
    try {
        await axios.patch(`/api/motivos-demora/${motivo.motivoDemoraID}/toggle`);
        motivo.activo = !motivo.activo;
    } catch (error) {
        configError.value = 'Error al cambiar estado del motivo';
    }
};

const eliminarMotivo = async (motivo) => {
    if (!confirm(`¿Desactivar el motivo "${motivo.nombre}"?`)) {
        return;
    }

    try {
        await axios.delete(`/api/motivos-demora/${motivo.motivoDemoraID}`);
        motivo.activo = false;
    } catch (error) {
        configError.value = 'Error al desactivar motivo';
    }
};
</script>

<template>
    <Head title="Responder Alerta de SLA" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Responder Alerta de SLA
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Breadcrumb sutil -->
                <nav class="mb-6">
                    <Link :href="route('alertas.index')" class="inline-flex items-center text-sm text-gray-500 hover:text-indigo-600 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Volver a mis alertas
                    </Link>
                </nav>

                <!-- Card principal con información de la alerta -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <!-- Header de la card -->
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Reparación</p>
                                <h3 class="text-lg font-semibold text-gray-900 font-mono">
                                    #{{ alerta.reparacion.codigo_reparacion }}
                                </h3>
                            </div>
                            <span :class="getTipoAlertaColor(alerta.tipo_alerta?.nombre)"
                                  class="px-4 py-2 text-xs font-semibold rounded-full uppercase tracking-wide">
                                {{ alerta.tipo_alerta?.descripcion || 'SLA Excedido' }}
                            </span>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Columna izquierda: Cliente y Equipo -->
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">Cliente</h4>
                                    <p class="text-base font-medium text-gray-900">
                                        {{ alerta.reparacion.cliente.nombre }} {{ alerta.reparacion.cliente.apellido }}
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">{{ alerta.reparacion.cliente.telefono }}</p>
                                </div>

                                <div>
                                    <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">Equipo</h4>
                                    <p class="text-base font-medium text-gray-900">
                                        {{ alerta.reparacion.equipo_marca }} {{ alerta.reparacion.equipo_modelo }}
                                    </p>
                                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                                        <span>Ingreso: {{ formatFecha(alerta.reparacion.fecha_ingreso) }}</span>
                                        <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                        <span>{{ alerta.reparacion.estado?.nombreEstado || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna derecha: Métricas SLA -->
                            <div>
                                <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">Estado del SLA</h4>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="text-center p-4 rounded-lg bg-slate-50">
                                        <p class="text-2xl font-bold text-slate-700">{{ alerta.sla_vigente }}</p>
                                        <p class="text-xs text-slate-500 mt-1">SLA (días)</p>
                                    </div>
                                    <div class="text-center p-4 rounded-lg bg-amber-50">
                                        <p class="text-2xl font-bold text-amber-600">{{ alerta.dias_efectivos }}</p>
                                        <p class="text-xs text-amber-600 mt-1">Transcurridos</p>
                                    </div>
                                    <div class="text-center p-4 rounded-lg bg-red-50">
                                        <p class="text-2xl font-bold text-red-600">+{{ alerta.dias_excedidos }}</p>
                                        <p class="text-xs text-red-600 mt-1">Excedidos</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Falla declarada -->
                        <div v-if="alerta.reparacion.falla_declarada" class="mt-6 pt-6 border-t border-gray-100">
                            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">Falla declarada</h4>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                {{ alerta.reparacion.falla_declarada }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card del formulario -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">Registrar respuesta</h3>
                        <p class="text-sm text-gray-500 mt-1">Indique el motivo de la demora para esta reparación</p>
                    </div>

                    <form @submit.prevent="submit" class="p-6">
                        <!-- Motivo de demora -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <InputLabel for="motivoDemoraID" value="Motivo de la demora" class="text-sm font-medium text-gray-700" />
                                <button
                                    type="button"
                                    @click="openConfigModal"
                                    class="inline-flex items-center text-xs text-gray-400 hover:text-indigo-600 transition-colors"
                                    title="Configurar motivos"
                                >
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Configurar
                                </button>
                            </div>
                            <select 
                                id="motivoDemoraID" 
                                v-model="form.motivoDemoraID"
                                class="block w-full border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                required
                            >
                                <option value="">Seleccione un motivo...</option>
                                <option v-for="motivo in motivosActivos" 
                                        :key="motivo.motivoDemoraID" 
                                        :value="motivo.motivoDemoraID">
                                    {{ motivo.nombre }}
                                    <span v-if="motivo.requiere_bonificacion"> (Requiere bonificación)</span>
                                </option>
                            </select>
                            <InputError :message="form.errors.motivoDemoraID" class="mt-2" />
                        </div>

                        <!-- Factibilidad -->
                        <div class="mb-6">
                            <InputLabel value="¿Es factible completar la reparación?" class="text-sm font-medium text-gray-700 mb-3" />
                            <div class="grid grid-cols-2 gap-3">
                                <label 
                                    class="relative flex items-center p-4 rounded-lg border-2 cursor-pointer transition-all"
                                    :class="form.es_factible === true 
                                        ? 'border-green-500 bg-green-50' 
                                        : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <input type="radio" 
                                           v-model="form.es_factible" 
                                           :value="true"
                                           class="sr-only">
                                    <div class="flex items-center">
                                        <div class="w-5 h-5 rounded-full border-2 mr-3 flex items-center justify-center transition-colors"
                                             :class="form.es_factible === true 
                                                 ? 'border-green-500 bg-green-500' 
                                                 : 'border-gray-300'">
                                            <svg v-if="form.es_factible === true" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Sí, es factible</p>
                                            <p class="text-xs text-gray-500">Se puede completar</p>
                                        </div>
                                    </div>
                                </label>

                                <label 
                                    class="relative flex items-center p-4 rounded-lg border-2 cursor-pointer transition-all"
                                    :class="form.es_factible === false 
                                        ? 'border-red-500 bg-red-50' 
                                        : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <input type="radio" 
                                           v-model="form.es_factible" 
                                           :value="false"
                                           class="sr-only">
                                    <div class="flex items-center">
                                        <div class="w-5 h-5 rounded-full border-2 mr-3 flex items-center justify-center transition-colors"
                                             :class="form.es_factible === false 
                                                 ? 'border-red-500 bg-red-500' 
                                                 : 'border-gray-300'">
                                            <svg v-if="form.es_factible === false" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">No es factible</p>
                                            <p class="text-xs text-gray-500">Consultar al cliente</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <InputError :message="form.errors.es_factible" class="mt-2" />
                        </div>

                        <!-- Observaciones -->
                        <div class="mb-6">
                            <InputLabel for="observaciones" value="Observaciones" class="text-sm font-medium text-gray-700 mb-2" />
                            <textarea 
                                id="observaciones" 
                                v-model="form.observaciones"
                                class="block w-full border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors resize-none"
                                rows="3"
                                placeholder="Detalles adicionales sobre la situación (opcional)"
                            ></textarea>
                            <InputError :message="form.errors.observaciones" class="mt-2" />
                        </div>

                        <!-- Nota informativa -->
                        <div class="mb-8 p-4 rounded-lg bg-amber-50 border border-amber-100">
                            <div class="flex">
                                <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <p class="ml-3 text-sm text-amber-700">
                                    Si el motivo requiere bonificación y la reparación es factible, se generará automáticamente una solicitud para aprobación del administrador.
                                </p>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                            <Link :href="route('alertas.index')" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                                Cancelar
                            </Link>
                            <PrimaryButton 
                                :class="{ 'opacity-50': form.processing }" 
                                :disabled="form.processing"
                                class="px-6"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Enviar respuesta
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <!-- Modal de Configuración de Motivos -->
        <Teleport to="body">
            <div v-if="showConfigModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Overlay -->
                    <div class="fixed inset-0 transition-opacity" @click="closeConfigModal">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>

                    <!-- Modal -->
                    <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-lg shadow-xl">
                        <!-- Header -->
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Configurar Motivos de Demora
                                </h3>
                                <button @click="closeConfigModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-4 max-h-96 overflow-y-auto">
                            <!-- Error message -->
                            <div v-if="configError" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md text-red-700 text-sm">
                                {{ configError }}
                            </div>

                            <!-- Loading -->
                            <div v-if="configLoading" class="text-center py-8">
                                <svg class="animate-spin h-8 w-8 mx-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>

                            <div v-else>
                                <!-- Formulario para agregar nuevo motivo -->
                                <div class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                                    <h4 class="text-sm font-semibold text-indigo-900 mb-3">➕ Agregar Nuevo Motivo</h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="col-span-2">
                                            <input 
                                                type="text"
                                                v-model="nuevoMotivo.nombre"
                                                placeholder="Nombre del motivo *"
                                                class="w-full text-sm border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                        </div>
                                        <div class="col-span-2">
                                            <input 
                                                type="text"
                                                v-model="nuevoMotivo.descripcion"
                                                placeholder="Descripción (opcional)"
                                                class="w-full text-sm border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                        </div>
                                        <div>
                                            <label class="flex items-center text-sm">
                                                <input 
                                                    type="checkbox"
                                                    v-model="nuevoMotivo.requiere_bonificacion"
                                                    class="border-gray-300 rounded text-indigo-600 focus:ring-indigo-500"
                                                >
                                                <span class="ml-2 text-gray-700">Requiere bonificación</span>
                                            </label>
                                        </div>
                                        <div>
                                            <label class="flex items-center text-sm">
                                                <input 
                                                    type="checkbox"
                                                    v-model="nuevoMotivo.pausa_sla"
                                                    class="border-gray-300 rounded text-indigo-600 focus:ring-indigo-500"
                                                >
                                                <span class="ml-2 text-gray-700">Pausa el SLA</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex justify-end">
                                        <button 
                                            type="button"
                                            @click="agregarMotivo"
                                            :disabled="!nuevoMotivo.nombre.trim()"
                                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                        >
                                            Agregar Motivo
                                        </button>
                                    </div>
                                </div>

                                <!-- Lista de motivos existentes -->
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Motivos Existentes</h4>
                                <div class="space-y-2">
                                    <div 
                                        v-for="motivo in motivosLocales" 
                                        :key="motivo.motivoDemoraID"
                                        class="flex items-center justify-between p-3 rounded-lg border transition-colors"
                                        :class="motivo.activo ? 'bg-white border-gray-200' : 'bg-gray-50 border-gray-100 opacity-60'"
                                    >
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-900">{{ motivo.nombre }}</span>
                                                <span v-if="motivo.requiere_bonificacion" 
                                                      class="px-2 py-0.5 text-xs bg-orange-100 text-orange-700 rounded-full">
                                                    Bonificación
                                                </span>
                                                <span v-if="motivo.pausa_sla" 
                                                      class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full">
                                                    Pausa SLA
                                                </span>
                                            </div>
                                            <p v-if="motivo.descripcion" class="text-xs text-gray-500 mt-1">
                                                {{ motivo.descripcion }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2 ml-4">
                                            <!-- Toggle activo/inactivo -->
                                            <button 
                                                type="button"
                                                @click="toggleMotivo(motivo)"
                                                class="p-1.5 rounded-md transition-colors"
                                                :class="motivo.activo 
                                                    ? 'text-green-600 hover:bg-green-50' 
                                                    : 'text-gray-400 hover:bg-gray-100'"
                                                :title="motivo.activo ? 'Desactivar' : 'Activar'"
                                            >
                                                <svg v-if="motivo.activo" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="motivosLocales.length === 0" class="text-center py-8 text-gray-500">
                                        No hay motivos de demora configurados
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                            <button 
                                type="button"
                                @click="closeConfigModal"
                                class="px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
