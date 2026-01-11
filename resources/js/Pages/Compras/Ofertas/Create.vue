<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

// Props recibidos del Controlador (OfertaCompraController@create)
const props = defineProps({
    proveedores: Array,
    productos: Array,
    solicitud_id: {
        type: [Number, String],
        default: null
    }
});

// Inicialización del Formulario (Maestro-Detalle)
const form = useForm({
    proveedor_id: '',
    solicitud_id: props.solicitud_id,
    fecha_recepcion: new Date().toISOString().split('T')[0], // Default: Hoy
    validez_hasta: '',
    observaciones: '',
    archivo_adjunto: null,
    // Array dinámico para los ítems (Detalle)
    items: [
        { 
            producto_id: '', 
            cantidad: 1, 
            precio_unitario: 0, 
            disponibilidad_inmediata: true, 
            dias_entrega: 0,
            observaciones: ''
        }
    ]
});

// --- LÓGICA DE NEGOCIO Y UX ---

// Agregar una nueva fila de producto
const addItem = () => {
    form.items.push({
        producto_id: '',
        cantidad: 1,
        precio_unitario: 0,
        disponibilidad_inmediata: true,
        dias_entrega: 0,
        observaciones: ''
    });
};

// Eliminar una fila (validación: no dejar el array vacío)
const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    } else {
        // UX: Si borra el último, solo lo limpia, no lo elimina
        form.items[0].producto_id = '';
        form.items[0].cantidad = 1;
        form.items[0].precio_unitario = 0;
    }
};

// Cálculo Automático del Total (Feedback inmediato para el usuario)
const totalEstimado = computed(() => {
    return form.items.reduce((acc, item) => {
        const subtotal = (parseFloat(item.cantidad) || 0) * (parseFloat(item.precio_unitario) || 0);
        return acc + subtotal;
    }, 0);
});

// Helper para encontrar datos del producto seleccionado (UX: Mostrar precio referencia)
const getProductoInfo = (id) => {
    return props.productos.find(p => p.id == id);
};

// Manejo del envío
const submit = () => {
    form.post(route('ofertas.store'), {
        forceFormData: true, // Crucial para subir archivos con Inertia
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Registrar Oferta de Compra" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Registrar Oferta de Proveedor
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <form @submit.prevent="submit">
                    
                    <!-- Sección: Datos del Documento -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Datos del Documento</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Proveedor -->
                            <div>
                                <InputLabel for="proveedor_id" value="Proveedor *" />
                                <select 
                                    id="proveedor_id" 
                                    name="proveedor_id"
                                    v-model="form.proveedor_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">-- Seleccionar Proveedor --</option>
                                    <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">
                                        {{ prov.razon_social }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.proveedor_id" class="mt-2" />
                            </div>

                            <!-- Fecha Recepción -->
                            <div>
                                <InputLabel for="fecha_recepcion" value="Fecha de Recepción *" />
                                <TextInput 
                                    id="fecha_recepcion" 
                                    name="fecha_recepcion"
                                    type="date" 
                                    v-model="form.fecha_recepcion" 
                                    class="mt-1 block w-full" 
                                />
                                <InputError :message="form.errors.fecha_recepcion" class="mt-2" />
                            </div>

                            <!-- Validez Hasta -->
                            <div>
                                <InputLabel for="validez_hasta" value="Válida hasta (Opcional)" />
                                <TextInput 
                                    id="validez_hasta" 
                                    name="validez_hasta"
                                    type="date" 
                                    v-model="form.validez_hasta" 
                                    class="mt-1 block w-full" 
                                />
                                <InputError :message="form.errors.validez_hasta" class="mt-2" />
                            </div>

                            <!-- Observaciones (textarea) -->
                            <div class="md:col-span-2">
                                <InputLabel for="observaciones" value="Motivo / Observaciones Generales *" />
                                <textarea 
                                    id="observaciones"
                                    name="observaciones" 
                                    v-model="form.observaciones" 
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                    placeholder="Ej: Presupuesto solicitado por WhatsApp para stock mensual..."
                                ></textarea>
                                <InputError :message="form.errors.observaciones" class="mt-2" />
                            </div>

                            <!-- Archivo Adjunto -->
                            <div>
                                <InputLabel for="archivo_adjunto" value="Adjuntar Comprobante (PDF/Img)" />
                                <input 
                                    id="archivo_adjunto"
                                    name="archivo_adjunto"
                                    type="file" 
                                    @input="form.archivo_adjunto = $event.target.files[0]"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                />
                                <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="w-full h-2 mt-2">
                                    {{ form.progress.percentage }}%
                                </progress>
                                <InputError :message="form.errors.archivo_adjunto" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Ítems de la Oferta -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between items-center border-b pb-2 mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Ítems de la Oferta</h3>
                            <div class="text-lg font-bold text-indigo-700">
                                Total Estimado: ${{ totalEstimado.toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                            </div>
                        </div>

                        <InputError :message="form.errors.items" class="mb-4" />

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-1/3">Producto</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Cant.</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-32">Precio Unit.</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-32">Subtotal</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Logística</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in form.items" :key="index">
                                        <!-- Producto -->
                                        <td class="px-3 py-2 align-top">
                                            <select 
                                                :id="`item_producto_${index}`"
                                                :name="`items[${index}][producto_id]`"
                                                v-model="item.producto_id"
                                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                            >
                                                <option value="">Seleccionar...</option>
                                                <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                                                    {{ prod.nombre }} ({{ prod.codigo }})
                                                </option>
                                            </select>
                                            <div v-if="item.producto_id" class="text-xs text-gray-500 mt-1">
                                                Ref. costo: ${{ getProductoInfo(item.producto_id)?.ultimo_precio_compra || 'N/A' }}
                                            </div>
                                            <InputError :message="form.errors[`items.${index}.producto_id`]" class="mt-1" />
                                        </td>

                                        <!-- Cantidad -->
                                        <td class="px-3 py-2 align-top">
                                            <TextInput 
                                                :id="`item_cantidad_${index}`"
                                                :name="`items[${index}][cantidad]`"
                                                type="number" 
                                                v-model="item.cantidad" 
                                                class="block w-full text-sm" 
                                                min="1"
                                            />
                                            <InputError :message="form.errors[`items.${index}.cantidad`]" class="mt-1" />
                                        </td>

                                        <!-- Precio Unitario -->
                                        <td class="px-3 py-2 align-top">
                                            <div class="relative rounded-md shadow-sm">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2">
                                                    <span class="text-gray-500 sm:text-sm">$</span>
                                                </div>
                                                <input 
                                                    :id="`item_precio_${index}`"
                                                    :name="`items[${index}][precio_unitario]`"
                                                    type="number" 
                                                    step="0.01"
                                                    min="0"
                                                    v-model="item.precio_unitario"
                                                    class="block w-full rounded-md border-gray-300 pl-6 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                />
                                            </div>
                                            <InputError :message="form.errors[`items.${index}.precio_unitario`]" class="mt-1" />
                                        </td>

                                        <!-- Subtotal (calculado) -->
                                        <td class="px-3 py-2 align-top pt-3 font-semibold text-gray-700">
                                            ${{ ((item.cantidad * item.precio_unitario) || 0).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                                        </td>

                                        <!-- Logística -->
                                        <td class="px-3 py-2 align-top">
                                            <div class="flex items-center mb-2">
                                                <Checkbox 
                                                    :id="`disponibilidad_${index}`" 
                                                    :name="`items[${index}][disponibilidad_inmediata]`"
                                                    v-model:checked="item.disponibilidad_inmediata" 
                                                />
                                                <label :for="`disponibilidad_${index}`" class="ml-2 text-sm text-gray-600">Stock Inmediato</label>
                                            </div>
                                            
                                            <div v-show="!item.disponibilidad_inmediata" class="flex items-center space-x-2">
                                                <span class="text-xs text-gray-500">Días entrega:</span>
                                                <TextInput 
                                                    :id="`dias_entrega_${index}`"
                                                    :name="`items[${index}][dias_entrega]`"
                                                    type="number" 
                                                    v-model="item.dias_entrega" 
                                                    class="w-16 py-0 px-1 text-sm h-7" 
                                                    min="0"
                                                />
                                            </div>
                                            <InputError :message="form.errors[`items.${index}.dias_entrega`]" class="mt-1" />
                                        </td>

                                        <!-- Botón Eliminar -->
                                        <td class="px-3 py-2 align-middle text-center">
                                            <button 
                                                type="button" 
                                                @click="removeItem(index)"
                                                class="text-red-500 hover:text-red-700 transition duration-150"
                                                title="Quitar ítem"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Botón Agregar Ítem -->
                        <div class="mt-4">
                            <SecondaryButton type="button" @click="addItem">
                                + Agregar Producto
                            </SecondaryButton>
                        </div>
                    </div>

                    <!-- Acciones del Formulario -->
                    <div class="flex items-center justify-end mt-6 gap-4">
                        <Link :href="route('ofertas.index')" class="text-gray-600 hover:text-gray-900 underline text-sm">
                            Cancelar
                        </Link>
                        
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Guardar Oferta
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
