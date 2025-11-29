<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
  producto: Object,
  categorias: Array,
  estados: Array,
  tiposCliente: Array,
  proveedores: Array, 
  marcas: Array,      // <--- Nuevo
  unidades: Array,    // <--- Nuevo
  errors: Object, 
});

// Inicializar precios actuales
const preciosIniciales = {};
props.tiposCliente.forEach(tipo => {
    const precioActual = props.producto.precios.find(p => p.tipoClienteID == tipo.id && !p.fechaHasta);
    preciosIniciales[tipo.id] = precioActual ? parseFloat(precioActual.precio) : 0;
});

const form = useForm({
  codigo: props.producto.codigo,
  nombre: props.producto.nombre,
  descripcion: props.producto.descripcion || '',
  // IDs de relaciones nuevas
  marca_id: props.producto.marca_id || '',
  unidad_medida_id: props.producto.unidad_medida_id,
  // IDs existentes
  categoriaProductoID: props.producto.categoriaProductoID,
  estadoProductoID: props.producto.estadoProductoID,
  proveedor_habitual_id: props.producto.proveedor_habitual_id || '',
  precios: preciosIniciales,
  motivo: '', 
});

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
  .put(route('productos.update', props.producto.id));
};
</script>

<template>
    <Head :title="`Editar ${producto.nombre}`" />
    <AppLayout>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">
                    Editar Producto: <span class="text-indigo-600">{{ producto.nombre }}</span>
                </h1>
                <Link :href="route('productos.index')" class="flex items-center text-gray-600 hover:text-gray-900 transition">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Volver
                </Link>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="md:col-span-2 space-y-6">
                        <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                                <h2 class="text-lg font-medium text-gray-900">Datos Maestros</h2>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Código / SKU</label>
                                    <input v-model="form.codigo" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :class="{ 'border-red-500': form.errors.codigo }">
                                    <InputError :message="form.errors.codigo" class="mt-1" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                    <input v-model="form.nombre" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :class="{ 'border-red-500': form.errors.nombre }">
                                    <InputError :message="form.errors.nombre" class="mt-1" />
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                                    <select v-model="form.marca_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option :value="null">Sin Marca</option>
                                        <option v-for="m in marcas" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                                    </select>
                                    <InputError :message="form.errors.marca_id" class="mt-1" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Unidad</label>
                                    <select v-model="form.unidad_medida_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nombre }} ({{ u.abreviatura }})</option>
                                    </select>
                                    <InputError :message="form.errors.unidad_medida_id" class="mt-1" />
                                </div>
                            </div>
                            <div class="px-6 pb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                <textarea v-model="form.descripcion" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-yellow-50">
                                <h2 class="text-lg font-medium text-yellow-800">Actualización de Precios</h2>
                            </div>
                            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="tipoCliente in tiposCliente" :key="tipoCliente.id" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">{{ tipoCliente.nombre }}</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"><span class="text-gray-500 sm:text-sm">$</span></div>
                                        <input v-model.number="form.precios[tipoCliente.id]" type="number" step="0.01" min="0" class="block w-full rounded-md border-gray-300 pl-7 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                            <InputError :message="precioError" class="mb-4 text-center font-bold text-red-600" />
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white rounded-lg shadow border border-gray-200 p-6 space-y-4">
                            <h3 class="text-md font-bold text-gray-900 mb-3">Gestión</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                                <select v-model="form.categoriaProductoID" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <select v-model="form.estadoProductoID" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="est in estados" :key="est.id" :value="est.id">{{ est.nombre }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor Habitual</label>
                                <select v-model="form.proveedor_habitual_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option :value="null">-- Ninguno --</option>
                                    <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">{{ prov.razon_social }}</option>
                                </select>
                            </div>

                            <div class="pt-4 border-t border-gray-200 mt-4">
                                <label class="block text-sm font-bold text-gray-800 mb-1">Motivo del Cambio <span class="text-red-500">*</span></label>
                                <input v-model="form.motivo" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500" placeholder="Ej: Actualización de precios...">
                                <InputError :message="form.errors.motivo" class="mt-1" />
                                <p class="text-xs text-gray-500 mt-1">Requerido para auditoría.</p>
                            </div>

                            <div class="flex flex-col gap-3 mt-6">
                                <PrimaryButton class="w-full justify-center py-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Guardar Cambios
                                </PrimaryButton>
                                <Link :href="route('productos.index')" class="w-full text-center py-3 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition shadow-sm">
                                    Cancelar
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </AppLayout>
</template>
