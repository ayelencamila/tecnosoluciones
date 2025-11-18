<script setup>
import { ref, computed } from 'vue'; 
import { Link, useForm, Head } from '@inertiajs/vue3'; 
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
  categorias: Array,
  estados: Array,
  tiposCliente: Array,
  proveedores: Array, 
  errors: Object, 
});

const form = useForm({
  codigo: '',
  nombre: '',
  descripcion: '',
  marca: '',
  unidadMedida: 'UNIDAD',
  categoriaProductoID: '',
  estadoProductoID: props.estados.find(e => e.nombre === 'Activo')?.id || '',
  proveedor_habitual_id: '', // Campo para CU-25
  precios: {}, 
});

// Helper para mostrar error general de precios
const precioError = computed(() => {
    if (props.errors.precios) return props.errors.precios;
    if (props.errors['precios.*.precio']) return props.errors['precios.*.precio'];
    return null;
});

const submitForm = () => {
  const preciosArray = Object.entries(form.precios)
    .filter(([_, precio]) => precio && precio > 0)
    .map(([tipoClienteID, precio]) => ({
      tipoClienteID: parseInt(tipoClienteID),
      precio: parseFloat(precio)
    }));
  
  form.transform((data) => ({
    ...data,
    precios: preciosArray
  }))
  .post(route('productos.store'));
};
</script>

<template>
    <Head title="Registrar Producto" /> 
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Nuevo Producto</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-6">
                    <Link :href="route('productos.index')" class="flex items-center text-gray-600 hover:text-gray-900 transition">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Volver al Catálogo
                    </Link>
                </div>

                <form @submit.prevent="submitForm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <div class="md:col-span-2 space-y-6">
                            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                    <h2 class="text-lg font-medium text-gray-900">Información Básica</h2>
                                </div>
                                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Código / SKU <span class="text-red-500">*</span></label>
                                        <input v-model="form.codigo" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :class="{ 'border-red-500': form.errors.codigo }">
                                        <InputError :message="form.errors.codigo" class="mt-1" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto <span class="text-red-500">*</span></label>
                                        <input v-model="form.nombre" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :class="{ 'border-red-500': form.errors.nombre }">
                                        <InputError :message="form.errors.nombre" class="mt-1" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                                        <input v-model="form.marca" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <InputError :message="form.errors.marca" class="mt-1" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Unidad de Medida <span class="text-red-500">*</span></label>
                                        <select v-model="form.unidadMedida" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="UNIDAD">Unidad (Pieza)</option>
                                            <option value="SERVICIO">Servicio (Hora/Global)</option>
                                            <option value="METRO">Metro</option>
                                            <option value="KG">Kilogramo</option>
                                            <option value="LITRO">Litro</option>
                                        </select>
                                        <InputError :message="form.errors.unidadMedida" class="mt-1" />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                        <textarea v-model="form.descripcion" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                        <InputError :message="form.errors.descripcion" class="mt-1" />
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                                    <h2 class="text-lg font-medium text-gray-900">Lista de Precios</h2>
                                    <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded">Obligatorio</span>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div v-for="tipoCliente in tiposCliente" :key="tipoCliente.id" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                            <label class="block text-sm font-bold text-gray-700 mb-2">{{ tipoCliente.nombre }}</label>
                                            <div class="relative rounded-md shadow-sm">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <span class="text-gray-500 sm:text-sm">$</span>
                                                </div>
                                                <input v-model.number="form.precios[tipoCliente.id]" type="number" step="0.01" min="0" class="block w-full rounded-md border-gray-300 pl-7 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                    <InputError :message="precioError" class="mt-3 text-center font-bold text-red-600" />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                    <h2 class="text-lg font-medium text-gray-900">Clasificación</h2>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                                        <select v-model="form.categoriaProductoID" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">Seleccione...</option>
                                            <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                                        </select>
                                        <InputError :message="form.errors.categoriaProductoID" class="mt-1" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado <span class="text-red-500">*</span></label>
                                        <select v-model="form.estadoProductoID" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option v-for="est in estados" :key="est.id" :value="est.id">{{ est.nombre }}</option>
                                        </select>
                                        <InputError :message="form.errors.estadoProductoID" class="mt-1" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor Habitual</label>
                                        <select v-model="form.proveedor_habitual_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">-- Sin asignar --</option>
                                            <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">{{ prov.razon_social }}</option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">Proveedor por defecto para compras.</p>
                                        <InputError :message="form.errors.proveedor_habitual_id" class="mt-1" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3">
                                <PrimaryButton 
                                    class="w-full justify-center py-3" 
                                    :class="{ 'opacity-25': form.processing }" 
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'Guardando...' : 'Guardar Producto' }}
                                </PrimaryButton>

                                <Link :href="route('productos.index')" class="w-full text-center py-3 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition shadow-sm">
                                    Cancelar
                                </Link>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
