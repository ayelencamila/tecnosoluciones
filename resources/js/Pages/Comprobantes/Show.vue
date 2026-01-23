<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    comprobante: Object,
    puedeAnularse: Boolean,
    puedeReemitirse: Boolean,
});

const mostrarModalAnular = ref(false);
const formAnular = useForm({ motivo: '' });

const anular = () => {
    formAnular.post(route('comprobantes.anular', props.comprobante.comprobante_id), {
        onSuccess: () => {
            mostrarModalAnular.value = false;
            formAnular.reset();
        },
    });
};

const reemitir = () => {
    if (confirm('¿Desea reemitir este comprobante?')) {
        useForm({}).post(route('comprobantes.reemitir', props.comprobante.comprobante_id));
    }
};

const verPdf = () => {
    if (props.comprobante.ruta_archivo) {
        window.open(props.comprobante.ruta_archivo, '_blank');
    }
};

const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const estadoClase = (nombre) => ({
    'EMITIDO': 'bg-green-100 text-green-800 border-green-200',
    'ANULADO': 'bg-red-100 text-red-800 border-red-200',
    'REEMPLAZADO': 'bg-yellow-100 text-yellow-800 border-yellow-200',
}[nombre] || 'bg-gray-100 text-gray-800 border-gray-200');
</script>

<template>
    <AppLayout title="Comprobante">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ comprobante.numero_correlativo }}
                </h2>
                <Link :href="route('comprobantes.index')" class="text-sm text-gray-600 hover:text-gray-900">
                    ← Volver
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Encabezado -->
                        <div class="flex justify-between items-start mb-6 pb-6 border-b">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ comprobante.numero_correlativo }}</h3>
                                <p class="text-gray-500">{{ comprobante.tipo_comprobante?.nombre }}</p>
                            </div>
                            <span :class="['px-3 py-1 text-sm font-medium rounded-full border', estadoClase(comprobante.estado_comprobante?.nombre)]">
                                {{ comprobante.estado_comprobante?.nombre }}
                            </span>
                        </div>

                        <!-- Datos -->
                        <dl class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <dt class="text-sm text-gray-500">Fecha de Emisión</dt>
                                <dd class="text-gray-900">{{ formatearFecha(comprobante.fecha_emision) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Emitido por</dt>
                                <dd class="text-gray-900">{{ comprobante.usuario?.name }}</dd>
                            </div>
                        </dl>

                        <!-- Motivo si está anulado -->
                        <div v-if="comprobante.motivo_estado" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm font-medium text-red-800">Motivo:</p>
                            <p class="text-red-700">{{ comprobante.motivo_estado }}</p>
                        </div>

                        <!-- Comprobante original si es reemisión -->
                        <div v-if="comprobante.original" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-800">
                                Reemisión del comprobante: 
                                <Link :href="route('comprobantes.show', comprobante.original.comprobante_id)" class="font-medium underline">
                                    {{ comprobante.original.numero_correlativo }}
                                </Link>
                            </p>
                        </div>

                        <!-- Reemisiones -->
                        <div v-if="comprobante.reemisiones?.length" class="mb-6 p-4 bg-gray-50 border rounded-lg">
                            <p class="text-sm text-gray-600 mb-2">Comprobantes de reemisión:</p>
                            <ul class="space-y-1">
                                <li v-for="r in comprobante.reemisiones" :key="r.comprobante_id">
                                    <Link :href="route('comprobantes.show', r.comprobante_id)" class="text-indigo-600 hover:underline">
                                        {{ r.numero_correlativo }}
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <!-- Acciones -->
                        <div class="flex gap-3 pt-6 border-t">
                            <button
                                v-if="comprobante.ruta_archivo"
                                @click="verPdf"
                                class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                            >
                                Ver PDF
                            </button>
                            <button
                                v-if="puedeAnularse"
                                @click="mostrarModalAnular = true"
                                class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700"
                            >
                                Anular
                            </button>
                            <button
                                v-if="puedeReemitirse"
                                @click="reemitir"
                                class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                            >
                                Reemitir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Anular -->
        <Teleport to="body">
            <div v-if="mostrarModalAnular" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/50" @click="mostrarModalAnular = false"></div>
                <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-semibold mb-4">Anular Comprobante</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Ingrese el motivo de anulación para {{ comprobante.numero_correlativo }}:
                    </p>
                    <textarea
                        v-model="formAnular.motivo"
                        rows="3"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                        placeholder="Motivo (mínimo 10 caracteres)"
                    ></textarea>
                    <p v-if="formAnular.errors.motivo" class="mt-1 text-sm text-red-600">{{ formAnular.errors.motivo }}</p>
                    <div class="flex justify-end gap-3 mt-4">
                        <button @click="mostrarModalAnular = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </button>
                        <button
                            @click="anular"
                            :disabled="formAnular.processing"
                            class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 disabled:opacity-50"
                        >
                            Confirmar Anulación
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
