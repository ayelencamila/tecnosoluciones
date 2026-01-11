<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import AlertMessage from '@/Components/AlertMessage.vue';

const props = defineProps({
    oferta: Object, // Incluye relaciones: proveedor, detalles.producto, estado, user
});

// Formulario vacío para disparar la generación de OC (CU-22)
const formGenerarOC = useForm({
    observaciones: 'Generación automática desde oferta aprobada.'
});

const generarOrdenCompra = () => {
    if (confirm('¿Estás seguro de que deseas aprobar esta oferta y generar la Orden de Compra?')) {
        // Asumiendo que la ruta será 'ordenes-compra.store-from-oferta' o similar
        // Por ahora, usaremos un placeholder o la ruta que definamos para el CU-22
        // formGenerarOC.post(route('ordenes-compra.generar', props.oferta.id));
        alert('Funcionalidad CU-22 pendiente de implementación en el siguiente paso.');
    }
};

// Helper para clases de estado (Feedback visual)
const estadoClass = (estado) => {
    switch (estado) {
        case 'Pendiente': return 'bg-yellow-100 text-yellow-800';
        case 'Elegida': return 'bg-green-100 text-green-800';
        case 'Procesada': return 'bg-blue-100 text-blue-800';
        case 'Rechazada': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head :title="`Oferta ${oferta.codigo_oferta}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalle de Oferta: {{ oferta.codigo_oferta }}
                </h2>
                <div class="space-x-2">
                    <Link :href="route('ofertas.index')" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                        &larr; Volver al listado
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <AlertMessage v-if="$page.props.flash.success" :message="$page.props.flash.success" type="success" />

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ oferta.proveedor.razon_social }}</h3>
                            <p class="text-sm text-gray-500">CUIT: {{ oferta.proveedor.cuit }}</p>
                            <div class="mt-2 flex items-center space-x-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="estadoClass(oferta.estado.nombre)">
                                    {{ oferta.estado.nombre }}
                                </span>
                                <span class="text-sm text-gray-600">
                                    Fecha: {{ new Date(oferta.fecha_recepcion).toLocaleDateString() }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total Estimado</p>
                            <p class="text-2xl font-bold text-gray-800">
                                ${{ Number(oferta.total_estimado).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs font-medium text-gray-500 uppercase">Observaciones</span>
                            <p class="mt-1 text-sm text-gray-900">{{ oferta.observaciones || 'Sin observaciones.' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-gray-500 uppercase">Archivo Adjunto</span>
                            <div class="mt-1">
                                <a v-if="oferta.archivo_adjunto" 
                                   :href="`/storage/${oferta.archivo_adjunto}`" 
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center text-indigo-600 hover:text-indigo-900 text-sm"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                    Ver Documento
                                </a>
                                <span v-else class="text-sm text-gray-400 italic">No hay archivo adjunto.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Productos Cotizados</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Unit.</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disponibilidad</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="detalle in oferta.detalles" :key="detalle.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ detalle.producto.nombre }}
                                        <span class="text-gray-500 text-xs block">{{ detalle.producto.codigo }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ detalle.cantidad_ofrecida }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        ${{ Number(detalle.precio_unitario).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-right">
                                        ${{ (detalle.cantidad_ofrecida * detalle.precio_unitario).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span v-if="detalle.disponibilidad_inmediata" class="text-green-600 font-semibold flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Inmediata
                                        </span>
                                        <span v-else class="text-orange-600 font-medium">
                                            {{ detalle.dias_entrega }} días de espera
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end space-x-4" v-if="oferta.estado.nombre === 'Pendiente' || oferta.estado.nombre === 'Elegida'">
                    <PrimaryButton @click="generarOrdenCompra" v-if="oferta.estado.nombre !== 'Procesada'">
                        Aprobar y Generar Orden de Compra
                    </PrimaryButton>
                </div>

            </div>
        </div>
    </AppLayout>
</template>