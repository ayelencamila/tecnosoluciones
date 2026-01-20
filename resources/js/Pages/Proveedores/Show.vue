<script setup>
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import StarRating from '@/Components/StarRating.vue';

const props = defineProps({
    proveedor: Object
});

const currentRating = ref(props.proveedor?.calificacion || 0);

// Guardar automáticamente cuando cambia la calificación
const updateRating = (newRating) => {
    currentRating.value = newRating;
    
    router.patch(route('proveedores.actualizar-calificacion', props.proveedor.id), {
        calificacion: newRating
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            console.log('Calificación guardada:', newRating);
        }
    });
};

// Función para quitar/resetear la calificación
const clearRating = () => {
    if (confirm('¿Estás seguro de que deseas eliminar la calificación?')) {
        updateRating(0);
    }
};
</script>

<template>
    <Head title="Detalles del Proveedor" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Loading state -->
                <div v-if="!proveedor" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500">Cargando datos del proveedor...</p>
                </div>

                <!-- Content -->
                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Botones de acción -->
                        <div class="flex justify-between items-center mb-6">
                            <Link :href="route('proveedores.index')" class="text-blue-600 hover:text-blue-800">
                                ← Volver al listado
                            </Link>
                            <Link 
                                :href="route('proveedores.edit', proveedor.id)" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Editar Proveedor
                            </Link>
                        </div>

                        <!-- Información del Proveedor -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Información General</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Razón Social</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ proveedor.razon_social }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">CUIT</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ proveedor.cuit }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ proveedor.email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ proveedor.telefono }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Forma de Pago Preferida</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ proveedor.forma_pago_preferida }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Plazo de Entrega Estimado</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ proveedor.plazo_entrega_estimado }} días</p>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <label class="block text-sm font-medium text-gray-700">Calificación</label>
                                            <span class="text-xs text-gray-500">(Click para calificar)</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <StarRating 
                                                :modelValue="currentRating" 
                                                @update:modelValue="updateRating"
                                                :readonly="false" 
                                            />
                                            <button 
                                                v-if="currentRating > 0"
                                                @click="clearRating"
                                                class="text-xs text-red-600 hover:text-red-800 underline"
                                                title="Eliminar calificación"
                                            >
                                                Quitar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dirección -->
                            <div v-if="proveedor.direccion">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Dirección</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Calle</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ proveedor.direccion.calle }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Altura</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ proveedor.direccion.altura || 'S/N' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Localidad</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ proveedor.direccion.localidad?.nombre }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Provincia</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ proveedor.direccion.localidad?.provincia?.nombre }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
