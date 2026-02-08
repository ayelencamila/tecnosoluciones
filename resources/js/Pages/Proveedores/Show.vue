<script setup>
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import StarRating from '@/Components/StarRating.vue';

const props = defineProps({
    proveedor: Object
});

const currentRating = ref(parseFloat(props.proveedor?.calificacion) || 0);

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
                            <Link :href="route('proveedores.index')" class="text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                    <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                                </svg>
                                Volver al listado
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
                                </div>
                            </div>

                            <!-- Calificación destacada -->
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-amber-500">
                                                <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                                            </svg>
                                            Calificación del Proveedor
                                        </h3>
                                        <p class="text-sm text-amber-700 mt-1">Hacé click en las estrellas para calificar</p>
                                    </div>
                                    <button 
                                        v-if="currentRating > 0"
                                        @click="clearRating"
                                        class="text-xs text-red-600 hover:text-red-800 underline font-medium"
                                        title="Eliminar calificación"
                                    >
                                        Quitar calificación
                                    </button>
                                </div>
                                <div class="mt-3">
                                    <StarRating 
                                        :modelValue="currentRating" 
                                        @update:modelValue="updateRating"
                                        :readonly="false" 
                                    />
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
