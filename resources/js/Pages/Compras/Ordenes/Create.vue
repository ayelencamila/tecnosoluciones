<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    oferta: Object,
    resultado: {
        type: Object,
        default: null
    }
});

const showConfirmModal = ref(false);

// Estados condicionales (Kendall: una vista, múltiples estados)
const mostrarFormulario = computed(() => !props.resultado);
const mostrarResultado = computed(() => !!props.resultado);

const form = useForm({
    oferta_id: props.oferta.id,
    observaciones: '',
});

const submit = () => {
    if (!form.observaciones || form.observaciones.trim().length < 10) {
        form.setError('observaciones', 'El motivo debe tener al menos 10 caracteres para auditoría.');
        return;
    }
    showConfirmModal.value = true;
};

const confirmarGeneracion = () => {
    form.post(route('ordenes.store'), {
        onSuccess: () => {
            showConfirmModal.value = false;
        },
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

const irAOrden = () => {
    router.visit(route('ordenes.show', props.resultado.orden.id));
};

const irAListado = () => {
    router.visit(route('ordenes.index'));
};
</script>

<template>
    <Head title="Generar Orden de Compra" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ mostrarResultado ? 'Resultado - Generar Orden de Compra' : 'Generar Orden de Compra' }}
                </h2>
                <Link v-if="mostrarFormulario" :href="route('ordenes.seleccionar')" 
                      class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    ← Volver
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- ============================================ -->
                <!-- ESTADO: RESULTADO (CU-22 Paso 12)            -->
                <!-- ============================================ -->
                <div v-if="mostrarResultado">
                    
                    <!-- ÉXITO COMPLETO -->
                    <div v-if="resultado.tipo === 'success'" 
                         class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-green-600 px-6 py-8 text-center">
                            <svg class="mx-auto h-16 w-16 text-white mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="text-2xl font-bold text-white">{{ resultado.mensaje }}</h3>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 border border-green-200 dark:border-green-800">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Número de Orden</p>
                                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ resultado.orden.numero_oc }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Estado</p>
                                        <span class="inline-block px-3 py-1 text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100 rounded-full">
                                            {{ resultado.orden.estado }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Proveedor</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ resultado.orden.proveedor }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Total</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(resultado.orden.total) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="text-sm text-blue-800 dark:text-blue-200">
                                        <strong>Próximos pasos:</strong>
                                        <ul class="mt-2 list-disc list-inside space-y-1">
                                            <li>El proveedor recibirá la orden por WhatsApp</li>
                                            <li>Podrá descargar el PDF desde el detalle de la orden</li>
                                            <li>Registre la recepción de mercadería cuando llegue</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-4">
                                <SecondaryButton @click="irAListado">
                                    Ver Todas las Órdenes
                                </SecondaryButton>
                                <PrimaryButton @click="irAOrden">
                                    Ver Detalle de la Orden
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- ÉXITO CON ADVERTENCIAS -->
                    <div v-else-if="resultado.tipo === 'success_with_warnings'" 
                         class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-amber-600 px-6 py-8 text-center">
                            <svg class="mx-auto h-16 w-16 text-white mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <h3 class="text-2xl font-bold text-white">{{ resultado.mensaje }}</h3>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-6 border border-amber-200 dark:border-amber-800">
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Número de Orden</p>
                                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ resultado.orden.numero_oc }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Estado</p>
                                        <span class="inline-block px-3 py-1 text-sm font-semibold bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-100 rounded-full">
                                            {{ resultado.orden.estado }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="border-t border-amber-200 dark:border-amber-700 pt-4">
                                    <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-100 mb-3">Advertencias detectadas:</h4>
                                    <div class="space-y-2">
                                        <div v-for="(adv, index) in resultado.advertencias" :key="index"
                                             class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-amber-300 dark:border-amber-700">
                                            <div class="flex items-start">
                                                <span class="inline-block px-2 py-0.5 text-xs font-mono bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100 rounded mr-3">
                                                    Exc. {{ adv.excepcion }}
                                                </span>
                                                <p class="text-sm text-gray-900 dark:text-gray-100 flex-1">{{ adv.mensaje }}</p>
                                            </div>
                                            <p v-if="adv.detalle" class="mt-2 text-xs text-gray-600 dark:text-gray-400 ml-16">
                                                {{ adv.detalle }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="text-sm text-blue-800 dark:text-blue-200">
                                        <strong>Acciones requeridas:</strong>
                                        <p class="mt-1">Revise el detalle de la orden para completar las acciones pendientes (reenviar PDF, WhatsApp, etc.)</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-4">
                                <SecondaryButton @click="irAListado">
                                    Ver Todas las Órdenes
                                </SecondaryButton>
                                <PrimaryButton @click="irAOrden">
                                    Ir a la Orden y Resolver
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- ERROR CRÍTICO -->
                    <div v-else-if="resultado.tipo === 'error'" 
                         class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-red-600 px-6 py-8 text-center">
                            <svg class="mx-auto h-16 w-16 text-white mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="text-2xl font-bold text-white">{{ resultado.mensaje }}</h3>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-6 border border-red-200 dark:border-red-800">
                                <p class="text-sm text-red-800 dark:text-red-200">
                                    <strong>Detalle del error:</strong>
                                </p>
                                <p class="mt-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 p-3 rounded border border-red-200 dark:border-red-700 font-mono">
                                    {{ resultado.error }}
                                </p>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <strong>Recomendaciones:</strong>
                                </p>
                                <ul class="mt-2 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside space-y-1">
                                    <li>Verifique que la oferta siga en estado "Elegida" o "Pre-aprobada"</li>
                                    <li>Confirme que no se haya generado una orden previamente para esta oferta</li>
                                    <li>Si el problema persiste, contacte al administrador del sistema</li>
                                </ul>
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-4">
                                <SecondaryButton @click="irAListado">
                                    Volver al Listado
                                </SecondaryButton>
                                <PrimaryButton @click="router.visit(route('ordenes.seleccionar'))">
                                    Intentar con Otra Oferta
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ============================================ -->
                <!-- ESTADO: FORMULARIO (Pasos CU-22 2-6)        -->
                <!-- ============================================ -->
                <template v-if="mostrarFormulario">
                    <!-- P2: RESUMEN DE OFERTA -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-indigo-600 px-6 py-4">
                            <h3 class="text-lg font-semibold text-white">Resumen de la Oferta</h3>
                            <p class="text-sm text-indigo-100">Verifique los datos antes de generar la Orden de Compra</p>
                        </div>

                        <div class="p-6 space-y-4">
                            <!-- Proveedor -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Proveedor</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ oferta.proveedor.razon_social }}</p>
                                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                                    <p>CUIT: {{ oferta.proveedor.cuit }}</p>
                                    <p>Email: {{ oferta.proveedor.email }}</p>
                                    <p v-if="oferta.proveedor.telefono">Teléfono: {{ oferta.proveedor.telefono }}</p>
                                </div>
                            </div>

                            <!-- Productos -->
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Productos</p>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Producto</th>
                                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300">Código</th>
                                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300">Cantidad</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">Precio Unit.</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            <tr v-for="detalle in oferta.detalles" :key="detalle.id">
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ detalle.producto.nombre }}</td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-600 dark:text-gray-400">{{ detalle.producto.codigo }}</td>
                                                <td class="px-4 py-3 text-sm text-center font-semibold text-gray-900 dark:text-white">{{ detalle.cantidad_ofrecida }}</td>
                                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(detalle.precio_unitario) }}</td>
                                                <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-white">
                                                    {{ formatCurrency(detalle.cantidad_ofrecida * detalle.precio_unitario) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-indigo-50 dark:bg-indigo-900/20">
                                            <tr>
                                                <td colspan="4" class="px-4 py-3 text-right text-sm font-bold text-indigo-900 dark:text-indigo-100">Total Oferta:</td>
                                                <td class="px-4 py-3 text-right text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(oferta.total_estimado) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- P3: INGRESO DE MOTIVO -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 px-6 py-4">
                            <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">Motivo / Observación</h3>
                            <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">Obligatorio para auditoría y trazabilidad</p>
                        </div>

                        <div class="p-6">
                            <form @submit.prevent="submit">
                                <div>
                                    <InputLabel for="observaciones" value="Motivo para generar esta Orden de Compra *" />
                                    <textarea
                                        id="observaciones"
                                        v-model="form.observaciones"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        rows="4"
                                        placeholder="Ejemplo: Aprobación del Gerente de Compras para reposición de stock urgente..."
                                        required
                                    ></textarea>
                                    <div class="flex items-center justify-between mt-2">
                                        <InputError :message="form.errors.observaciones" class="mt-2" />
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ form.observaciones.length }} / Mínimo 10 caracteres
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-6 flex items-center justify-end space-x-3">
                                    <Link :href="route('ordenes.seleccionar')">
                                        <SecondaryButton type="button">Cancelar</SecondaryButton>
                                    </Link>
                                    <PrimaryButton :disabled="form.processing || form.observaciones.length < 10">
                                        Continuar a Confirmación
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>

            </div>
        </div>

        <!-- P4: MODAL DE CONFIRMACIÓN -->
        <Modal :show="showConfirmModal" @close="showConfirmModal = false" max-width="2xl">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 bg-indigo-100 dark:bg-indigo-900 rounded-full p-3">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="ml-4 text-lg font-bold text-gray-900 dark:text-white">Confirmar Generación de Orden de Compra</h3>
                </div>

                <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 p-4 mb-4">
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        <strong>Atención:</strong> Esta acción es irreversible. Se generará la Orden de Compra y se enviará automáticamente al proveedor por WhatsApp.
                    </p>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Proveedor:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ oferta.proveedor.razon_social }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Productos:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ oferta.detalles.length }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Total:</span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400 text-lg">{{ formatCurrency(oferta.total_estimado) }}</span>
                    </div>
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">Motivo:</span>
                        <p class="mt-1 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 p-2 rounded text-xs">{{ form.observaciones }}</p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end space-x-3">
                    <SecondaryButton @click="showConfirmModal = false" :disabled="form.processing">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton @click="confirmarGeneracion" :disabled="form.processing">
                        <span v-if="form.processing">Generando...</span>
                        <span v-else>Confirmar y Enviar</span>
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
