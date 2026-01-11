<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    proveedores: Array,
    productos: Array,
    solicitud_id: {
        type: [Number, String],
        default: null
    }
});

const form = useForm({
    proveedor_id: '',
    solicitud_id: props.solicitud_id,
    fecha_recepcion: new Date().toISOString().split('T')[0],
    validez_hasta: '',
    observaciones: '',
    archivo_adjunto: null,
    items: [
        { 
            producto_id: '', 
            cantidad: 1, 
            precio_unitario: 0, 
            disponibilidad_inmediata: true, 
            dias_entrega: 0
        }
    ]
});

const addItem = () => {
    form.items.push({
        producto_id: '',
        cantidad: 1,
        precio_unitario: 0,
        disponibilidad_inmediata: true,
        dias_entrega: 0
    });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    } else {
        form.items[0] = { producto_id: '', cantidad: 1, precio_unitario: 0, disponibilidad_inmediata: true, dias_entrega: 0 };
    }
};

const totalEstimado = computed(() => {
    return form.items.reduce((acc, item) => {
        return acc + ((parseFloat(item.cantidad) || 0) * (parseFloat(item.precio_unitario) || 0));
    }, 0);
});

const cantidadItems = computed(() => form.items.filter(i => i.producto_id).length);

const submit = () => {
    form.post(route('ofertas.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Registrar Oferta de Compra" />

    <AppLayout>
        <template #header>
            Registrar Oferta de Proveedor
        </template>

        <div class="max-w-6xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">
                
                <!-- Cabecera con resumen -->
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="bg-white/20 p-3 rounded-lg">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">Nueva Oferta de Compra</h2>
                                <p class="text-indigo-200 text-sm">Complete los datos del presupuesto recibido</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-indigo-200 text-xs uppercase tracking-wide">Total Estimado</p>
                            <p class="text-3xl font-bold">${{ totalEstimado.toLocaleString('es-AR', {minimumFractionDigits: 2}) }}</p>
                            <p class="text-indigo-200 text-sm">{{ cantidadItems }} producto(s)</p>
                        </div>
                    </div>
                </div>

                <!-- Grid Principal: Datos + Archivo -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Datos del Documento (2 columnas) -->
                    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Datos del Documento
                            </h3>
                        </div>
                        <div class="p-6 space-y-5">
                            <!-- Proveedor -->
                            <div>
                                <InputLabel for="proveedor_id" value="Proveedor" class="text-gray-700 font-medium" />
                                <select 
                                    id="proveedor_id" 
                                    v-model="form.proveedor_id"
                                    class="mt-1.5 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-gray-900"
                                >
                                    <option value="">Seleccionar proveedor...</option>
                                    <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">
                                        {{ prov.razon_social }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.proveedor_id" class="mt-1" />
                            </div>

                            <!-- Fechas -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="fecha_recepcion" value="Fecha de Recepción" class="text-gray-700 font-medium" />
                                    <TextInput 
                                        id="fecha_recepcion" 
                                        type="date" 
                                        v-model="form.fecha_recepcion" 
                                        class="mt-1.5 block w-full" 
                                    />
                                    <InputError :message="form.errors.fecha_recepcion" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel for="validez_hasta" value="Válida hasta" class="text-gray-700 font-medium" />
                                    <TextInput 
                                        id="validez_hasta" 
                                        type="date" 
                                        v-model="form.validez_hasta" 
                                        class="mt-1.5 block w-full" 
                                    />
                                    <InputError :message="form.errors.validez_hasta" class="mt-1" />
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div>
                                <InputLabel for="observaciones" value="Observaciones" class="text-gray-700 font-medium" />
                                <textarea 
                                    id="observaciones"
                                    v-model="form.observaciones" 
                                    rows="3"
                                    class="mt-1.5 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm resize-none" 
                                    placeholder="Condiciones especiales, notas del proveedor..."
                                ></textarea>
                                <InputError :message="form.errors.observaciones" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Archivo Adjunto (1 columna) -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                Comprobante
                            </h3>
                        </div>
                        <div class="p-6">
                            <div 
                                class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition-colors cursor-pointer"
                                @click="$refs.fileInput.click()"
                            >
                                <input 
                                    ref="fileInput"
                                    type="file" 
                                    @input="form.archivo_adjunto = $event.target.files[0]"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="hidden"
                                />
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600">
                                    <span class="font-medium text-indigo-600">Click para subir</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">PDF, JPG o PNG</p>
                            </div>
                            
                            <div v-if="form.archivo_adjunto" class="mt-4 p-3 bg-indigo-50 rounded-lg flex items-center justify-between">
                                <div class="flex items-center space-x-2 text-sm">
                                    <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-700 truncate max-w-[150px]">{{ form.archivo_adjunto.name }}</span>
                                </div>
                                <button type="button" @click="form.archivo_adjunto = null" class="text-gray-400 hover:text-red-500">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="w-full h-1.5 mt-3 rounded">
                                {{ form.progress.percentage }}%
                            </progress>
                            <InputError :message="form.errors.archivo_adjunto" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Tabla de Ítems -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Detalle de Productos
                        </h3>
                        <button 
                            type="button" 
                            @click="addItem"
                            class="inline-flex items-center px-3 py-1.5 border border-indigo-600 text-indigo-600 text-sm font-medium rounded-lg hover:bg-indigo-50 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Agregar
                        </button>
                    </div>
                    
                    <InputError :message="form.errors.items" class="px-6 pt-4" />

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">Producto</th>
                                    <th class="px-4 py-3 w-28">Cantidad</th>
                                    <th class="px-4 py-3 w-36">Precio Unit.</th>
                                    <th class="px-4 py-3 w-32 text-right">Subtotal</th>
                                    <th class="px-4 py-3 w-44">Disponibilidad</th>
                                    <th class="px-4 py-3 w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-gray-50/50">
                                    <!-- Producto -->
                                    <td class="px-6 py-4">
                                        <select 
                                            v-model="item.producto_id"
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                                        >
                                            <option value="">Seleccionar producto...</option>
                                            <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                                                {{ prod.codigo }} - {{ prod.nombre }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors[`items.${index}.producto_id`]" class="mt-1" />
                                    </td>

                                    <!-- Cantidad -->
                                    <td class="px-4 py-4">
                                        <TextInput 
                                            type="number" 
                                            v-model="item.cantidad" 
                                            class="block w-full text-sm text-center" 
                                            min="1"
                                        />
                                        <InputError :message="form.errors[`items.${index}.cantidad`]" class="mt-1" />
                                    </td>

                                    <!-- Precio Unitario -->
                                    <td class="px-4 py-4">
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                            <input 
                                                type="number" 
                                                step="0.01"
                                                min="0"
                                                v-model="item.precio_unitario"
                                                class="block w-full rounded-lg border-gray-300 pl-7 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </div>
                                        <InputError :message="form.errors[`items.${index}.precio_unitario`]" class="mt-1" />
                                    </td>

                                    <!-- Subtotal -->
                                    <td class="px-4 py-4 text-right">
                                        <span class="text-sm font-semibold text-gray-900">
                                            ${{ ((item.cantidad * item.precio_unitario) || 0).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                                        </span>
                                    </td>

                                    <!-- Disponibilidad -->
                                    <td class="px-4 py-4">
                                        <div class="flex items-center space-x-3">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" v-model="item.disponibilidad_inmediata" class="sr-only peer">
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500"></div>
                                            </label>
                                            <span v-if="item.disponibilidad_inmediata" class="text-xs text-green-600 font-medium">Inmediato</span>
                                            <div v-else class="flex items-center space-x-1">
                                                <input 
                                                    type="number" 
                                                    v-model="item.dias_entrega" 
                                                    class="w-12 text-xs text-center rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1" 
                                                    min="1"
                                                />
                                                <span class="text-xs text-gray-500">días</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Eliminar -->
                                    <td class="px-4 py-4 text-center">
                                        <button 
                                            type="button" 
                                            @click="removeItem(index)"
                                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Eliminar"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <!-- Footer con total -->
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-500">
                                        Total de la Oferta:
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <span class="text-lg font-bold text-indigo-600">
                                            ${{ totalEstimado.toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                                        </span>
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <Link :href="route('ofertas.index')" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                        ← Volver al listado
                    </Link>
                    
                    <PrimaryButton 
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }" 
                        :disabled="form.processing"
                        class="px-6 py-2.5"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Guardando...' : 'Guardar Oferta' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
