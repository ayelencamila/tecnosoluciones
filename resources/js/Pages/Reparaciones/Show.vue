<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    reparacion: Object,
});

const showDeleteModal = ref(false);
const deleteForm = useForm({ motivo: '' });

const anularReparacion = () => {
    deleteForm.delete(route('reparaciones.destroy', props.reparacion.reparacionID), {
        preserveScroll: true,
        onSuccess: () => showDeleteModal.value = false,
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getEstadoColor = (nombre) => {
    const n = nombre?.toLowerCase() || '';
    if (n.includes('cancel') || n.includes('anul')) return 'text-red-800 bg-red-100 border border-red-200';
    if (n.includes('listo')) return 'text-green-800 bg-green-100 border border-green-200';
    if (n.includes('entregado')) return 'text-gray-800 bg-gray-100 border border-gray-200';
    if (n.includes('diagn')) return 'text-purple-800 bg-purple-100 border border-purple-200';
    return 'text-blue-800 bg-blue-100 border border-blue-200';
};

const imprimirComprobanteIngreso = () => {
    window.open(route('reparaciones.imprimir-ingreso', props.reparacion.reparacionID), '_blank');
};
</script>

<template>
    <Head :title="`Reparación ${reparacion.codigo_reparacion}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalle de Reparación <span class="font-mono text-indigo-600">{{ reparacion.codigo_reparacion }}</span>
                </h2>
                <span class="px-4 py-1 rounded-full text-sm font-bold uppercase tracking-wide border" :class="getEstadoColor(reparacion.estado?.nombreEstado)">
                    {{ reparacion.estado?.nombreEstado }}
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="flex justify-between items-center">
                    <Link :href="route('reparaciones.index')"><SecondaryButton> &larr; Volver al Listado </SecondaryButton></Link>
                    <div class="space-x-3">
                        <SecondaryButton @click="imprimirComprobanteIngreso" class="bg-blue-50 border-blue-300 text-blue-700 hover:bg-blue-100">
                            🖨️ Comprobante de Ingreso
                        </SecondaryButton>
                        <DangerButton v-if="!['Cancelado', 'Anulado', 'Entregado'].includes(reparacion.estado?.nombreEstado)" @click="showDeleteModal = true">Anular Reparación</DangerButton>
                        <Link v-if="!['Cancelado', 'Anulado'].includes(reparacion.estado?.nombreEstado)" :href="route('reparaciones.edit', reparacion.reparacionID)"><PrimaryButton> Actualizar / Diagnosticar </PrimaryButton></Link>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 space-y-6">
                        
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Información del Dispositivo</h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-500">Equipo</dt>
                                    <dd class="mt-1 text-gray-900 font-semibold">
                                        {{ reparacion.marca?.nombre }} {{ reparacion.modelo?.nombre }}
                                    </dd>
                                </div>
                                <div><dt class="font-medium text-gray-500">Serie / IMEI</dt><dd class="mt-1 text-gray-900 font-mono">{{ reparacion.numero_serie_imei || 'No registrado' }}</dd></div>
                                <div class="sm:col-span-2 bg-red-50 p-3 rounded border border-red-100"><dt class="font-bold text-red-700">Falla Declarada (Cliente)</dt><dd class="mt-1 text-gray-800">{{ reparacion.falla_declarada }}</dd></div>
                                <div class="sm:col-span-2"><dt class="font-medium text-gray-500">Accesorios</dt><dd class="mt-1 text-gray-900">{{ reparacion.accesorios_dejados || 'Ninguno' }}</dd></div>
                            </dl>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4 flex justify-between items-center">
                                Informe Técnico
                                <span class="text-xs font-normal bg-gray-100 px-2 py-1 rounded text-gray-600">Técnico: {{ reparacion.tecnico?.name || 'Sin Asignar' }}</span>
                            </h3>
                            <div v-if="!reparacion.diagnostico_tecnico" class="text-gray-400 italic text-center py-4">Pendiente de diagnóstico.</div>
                            <div v-else class="space-y-4 text-sm">
                                <div><h4 class="font-bold text-gray-700">Diagnóstico / Trabajo Realizado:</h4><p class="text-gray-800 mt-1 whitespace-pre-line">{{ reparacion.diagnostico_tecnico }}</p></div>
                                <div v-if="reparacion.observaciones" class="pt-2 border-t border-gray-100"><h4 class="font-bold text-gray-600">Notas Internas:</h4><p class="text-gray-600 mt-1">{{ reparacion.observaciones }}</p></div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Repuestos y Servicios Utilizados</h3>
                            <div v-if="reparacion.repuestos && reparacion.repuestos.length > 0">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left font-medium text-gray-500">Ítem</th><th class="px-3 py-2 text-right font-medium text-gray-500">Cant.</th><th class="px-3 py-2 text-right font-medium text-gray-500">Unitario</th><th class="px-3 py-2 text-right font-medium text-gray-500">Subtotal</th></tr></thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <tr v-for="item in reparacion.repuestos" :key="item.id">
                                                <td class="px-3 py-2 text-gray-900">{{ item.producto?.nombre }}</td><td class="px-3 py-2 text-gray-900 text-right">{{ item.cantidad }}</td><td class="px-3 py-2 text-gray-500 text-right">${{ Number(item.precio_unitario).toFixed(2) }}</td><td class="px-3 py-2 font-bold text-gray-900 text-right">${{ Number(item.subtotal).toFixed(2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500 italic text-center py-2">No se han cargado repuestos.</div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Cliente</h3>
                            <div class="text-sm">
                                <p class="font-bold text-gray-900 text-lg">{{ reparacion.cliente.apellido }}, {{ reparacion.cliente.nombre }}</p>
                                <p class="text-gray-500 mt-1">DNI: {{ reparacion.cliente.DNI }}</p>
                                <p class="text-gray-500 mt-1"><span class="font-semibold">Tel/WP:</span> {{ reparacion.cliente.whatsapp || reparacion.cliente.telefono || '-' }}</p>
                                <p class="text-gray-500 mt-1"><span class="font-semibold">Email:</span> {{ reparacion.cliente.mail || '-' }}</p>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Fechas</h3>
                            <ul class="space-y-3 text-sm">
                                <li class="flex justify-between"><span class="text-gray-500">Ingreso:</span><span class="font-medium">{{ formatDate(reparacion.fecha_ingreso) }}</span></li>
                                <li class="flex justify-between"><span class="text-gray-500">Promesa:</span><span class="font-medium text-indigo-600">{{ formatDate(reparacion.fecha_promesa) }}</span></li>
                                <li v-if="reparacion.fecha_entrega_real" class="flex justify-between pt-2 border-t"><span class="text-gray-500">Entregado:</span><span class="font-bold text-green-600">{{ formatDate(reparacion.fecha_entrega_real) }}</span></li>
                            </ul>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Evidencia (Fotos)</h3>
                            <div v-if="reparacion.imagenes && reparacion.imagenes.length > 0" class="grid grid-cols-2 gap-2">
                                <div v-for="img in reparacion.imagenes" :key="img.id">
                                    <a :href="'/storage/' + img.ruta_archivo" target="_blank"><img :src="'/storage/' + img.ruta_archivo" class="h-24 w-full object-cover rounded border hover:opacity-75 transition cursor-zoom-in" /></a>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-400 italic text-center">Sin imágenes.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 px-4">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Anular Reparación</h3>
                    <p class="text-sm text-gray-500 mb-4">¿Está seguro? <br><span v-if="reparacion.repuestos && reparacion.repuestos.length > 0" class="font-bold text-red-600 block mt-2 bg-red-50 p-2 rounded border border-red-100">⚠️ Se devolverá el stock de repuestos.</span></p>
                    <div class="mb-4">
                        <InputLabel value="Motivo (Requerido)" class="mb-1" />
                        <textarea v-model="deleteForm.motivo" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Ej: Cliente cancela..."></textarea>
                        <InputError :message="deleteForm.errors.motivo" class="mt-1" />
                    </div>
                    <div class="flex justify-end space-x-3">
                        <SecondaryButton @click="showDeleteModal = false"> Cancelar </SecondaryButton>
                        <DangerButton @click="anularReparacion" :disabled="deleteForm.processing"> Confirmar Anulación </DangerButton>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>