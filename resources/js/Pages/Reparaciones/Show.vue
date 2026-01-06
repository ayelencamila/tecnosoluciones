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

const imprimirComprobanteEntrega = () => {
    window.open(route('reparaciones.imprimir-entrega', props.reparacion.reparacionID), '_blank');
};
</script>

<template>
    <Head :title="`Reparación ${reparacion.codigo_reparacion}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Detalle de Reparación <span class="font-mono text-indigo-600">{{ reparacion.codigo_reparacion }}</span>
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        <span class="font-medium">Ingreso:</span> {{ formatDate(reparacion.fecha_ingreso) }}
                        <span v-if="reparacion.tecnico" class="ml-4"><span class="font-medium">Técnico:</span> {{ reparacion.tecnico.name }}</span>
                    </p>
                </div>
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
                        <SecondaryButton 
                            v-if="['Reparado', 'Entregado'].includes(reparacion.estado?.nombreEstado)" 
                            @click="imprimirComprobanteEntrega" 
                            class="bg-green-50 border-green-300 text-green-700 hover:bg-green-100">
                            🖨️ Comprobante de Entrega
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
                                
                                <!-- Gran Total destacado según Kendall: alineación derecha, visualmente prominente -->
                                <div class="mt-4 bg-indigo-50 border-2 border-indigo-200 rounded-lg p-4">
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-700">Subtotal Repuestos:</span>
                                            <span class="font-semibold text-gray-900">${{ reparacion.repuestos.reduce((sum, item) => sum + Number(item.subtotal), 0).toFixed(2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-700">Mano de Obra:</span>
                                            <span class="font-semibold text-gray-900">${{ Number(reparacion.costo_mano_obra || 0).toFixed(2) }}</span>
                                        </div>
                                        <div class="flex justify-between pt-2 border-t-2 border-indigo-300">
                                            <span class="text-lg font-bold text-indigo-900">TOTAL A COBRAR:</span>
                                            <span class="text-xl font-bold text-indigo-900">${{ (reparacion.repuestos.reduce((sum, item) => sum + Number(item.subtotal), 0) + Number(reparacion.costo_mano_obra || 0)).toFixed(2) }}</span>
                                        </div>
                                    </div>
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
                                <div class="mt-2 flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500"><span class="font-semibold">Tel/WP:</span> {{ reparacion.cliente.whatsapp || reparacion.cliente.telefono || '-' }}</p>
                                    </div>
                                    <a 
                                        v-if="reparacion.cliente.whatsapp || reparacion.cliente.telefono"
                                        :href="`https://wa.me/${(reparacion.cliente.whatsapp || reparacion.cliente.telefono).replace(/[^0-9]/g, '')}`"
                                        target="_blank"
                                        class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold rounded-md transition">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        WhatsApp
                                    </a>
                                </div>
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