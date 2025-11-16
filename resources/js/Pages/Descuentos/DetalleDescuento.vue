<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    descuento: Object,
});

const confirmingDelete = ref(false);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-AR', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
};

const confirmDelete = () => {
    confirmingDelete.value = true;
};

const closeModal = () => {
    confirmingDelete.value = false;
};

const desactivarDescuento = () => {
    router.delete(route('descuentos.destroy', props.descuento.descuento_id), {
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head :title="`Descuento: ${descuento.codigo}`" />
    
    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalle de Descuento: {{ descuento.codigo }}
                </h2>
                <Link :href="route('descuentos.index')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                    &larr; Volver al Listado
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Encabezado con estado -->
                <div class="bg-white shadow-sm sm:rounded-t-lg p-6 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">{{ descuento.codigo }}</h3>
                        <p class="text-gray-600 mt-1">{{ descuento.descripcion }}</p>
                    </div>
                    <div>
                        <span 
                            :class="descuento.activo 
                                ? 'px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-800 border border-green-200' 
                                : 'px-4 py-2 rounded-full text-sm font-bold bg-red-100 text-red-800 border border-red-200'"
                        >
                            {{ descuento.activo ? 'ACTIVO' : 'INACTIVO' }}
                        </span>
                    </div>
                </div>

                <!-- Información del descuento -->
                <div class="bg-white shadow-sm p-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        
                        <div class="bg-gray-50 p-4 rounded-md">
                            <h4 class="text-sm font-bold text-gray-500 uppercase mb-2">Tipo de Descuento</h4>
                            <p class="text-lg font-medium text-gray-900">
                                <span v-if="descuento.tipo === 'porcentaje'" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                                    📊 Porcentaje
                                </span>
                                <span v-else class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full">
                                    💵 Monto Fijo
                                </span>
                            </p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-md">
                            <h4 class="text-sm font-bold text-gray-500 uppercase mb-2">Valor</h4>
                            <p class="text-2xl font-bold text-indigo-600">
                                <span v-if="descuento.tipo === 'porcentaje'">{{ descuento.valor }}%</span>
                                <span v-else>{{ formatCurrency(descuento.valor) }}</span>
                            </p>
                        </div>

                    </div>

                    <!-- Vigencia -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <h4 class="text-sm font-bold text-yellow-800 mb-2">📅 Período de Vigencia</h4>
                        <div class="text-sm text-yellow-700">
                            <p v-if="descuento.valido_desde">
                                <strong>Desde:</strong> {{ formatDate(descuento.valido_desde) }}
                            </p>
                            <p v-if="descuento.valido_hasta">
                                <strong>Hasta:</strong> {{ formatDate(descuento.valido_hasta) }}
                            </p>
                            <p v-if="!descuento.valido_desde && !descuento.valido_hasta" class="text-yellow-600">
                                ⏳ Este descuento no tiene límite de tiempo
                            </p>
                        </div>
                    </div>

                    <!-- Ejemplo de cálculo -->
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                        <h4 class="text-sm font-bold text-indigo-900 mb-2">💡 Ejemplo de Aplicación</h4>
                        <div class="text-sm text-indigo-700 space-y-1">
                            <p><strong>Subtotal de ejemplo:</strong> $1,000.00</p>
                            <p v-if="descuento.tipo === 'porcentaje'">
                                <strong>Descuento aplicado:</strong> ${{ (1000 * descuento.valor / 100).toFixed(2) }} 
                                ({{ descuento.valor }}%)
                            </p>
                            <p v-else>
                                <strong>Descuento aplicado:</strong> {{ formatCurrency(Math.min(descuento.valor, 1000)) }}
                            </p>
                            <p class="text-lg font-bold text-indigo-900 pt-2 border-t border-indigo-300">
                                <strong>Total final:</strong> 
                                <span v-if="descuento.tipo === 'porcentaje'">
                                    ${{ (1000 - (1000 * descuento.valor / 100)).toFixed(2) }}
                                </span>
                                <span v-else>
                                    ${{ (1000 - Math.min(descuento.valor, 1000)).toFixed(2) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Metadatos -->
                    <div class="mt-6 pt-6 border-t border-gray-200 text-xs text-gray-500">
                        <p><strong>Creado:</strong> {{ formatDate(descuento.created_at) }}</p>
                        <p v-if="descuento.updated_at !== descuento.created_at">
                            <strong>Última modificación:</strong> {{ formatDate(descuento.updated_at) }}
                        </p>
                    </div>

                </div>

                <!-- Acciones -->
                <div class="bg-gray-50 px-6 py-4 shadow-sm sm:rounded-b-lg flex justify-between items-center">
                    <Link :href="route('descuentos.edit', descuento.descuento_id)">
                        <PrimaryButton>
                            ✏️ Editar Descuento
                        </PrimaryButton>
                    </Link>

                    <DangerButton 
                        v-if="descuento.activo"
                        @click="confirmDelete"
                    >
                        🚫 Desactivar
                    </DangerButton>
                </div>

            </div>
        </div>

        <!-- Modal de confirmación -->
        <div v-if="confirmingDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    ¿Desactivar este descuento?
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    El descuento "{{ descuento.codigo }}" será marcado como inactivo y no podrá aplicarse en nuevas ventas.
                    Podrás reactivarlo editando el descuento.
                </p>

                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="closeModal">
                        Cancelar
                    </SecondaryButton>
                    <DangerButton @click="desactivarDescuento">
                        Confirmar Desactivación
                    </DangerButton>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
