<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
  producto: Object,
  categorias: Array,
  estados: Array,
  tiposCliente: Array,
  proveedores: Array, 
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
  marca: props.producto.marca || '',
  unidadMedida: props.producto.unidadMedida,
  categoriaProductoID: props.producto.categoriaProductoID,
  estadoProductoID: props.producto.estadoProductoID,
  proveedor_habitual_id: props.producto.proveedor_habitual_id || '',
  precios: preciosIniciales,
  motivo: '', 
  // Ya no enviamos stock_minimo para editar
});

// Variable solo para visualización
const stockMinimoVisual = props.producto.stocks && props.producto.stocks.length > 0 
                  ? props.producto.stocks[0].stock_minimo 
                  : 0;

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
                                    <input v-model="form.marca" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <InputError :message="form.errors.marca" class="mt-1" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Unidad</label>
                                    <select v-model="form.unidadMedida" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="UNIDAD">Unidad</option>
                                        <option value="SERVICIO">Servicio</option>
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

                        <div class="bg-yellow-50 rounded-lg shadow border border-yellow-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-yellow-100 flex items-center">
                                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <h2 class="text-lg font-medium text-yellow-800">Auditoría de Cambios</h2>
                            </div>
                            <div class="p-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Motivo de la modificación <span class="text-red-500">*</span>
                                </label>
                                <textarea 
                                    v-model="form.motivo" 
                                    rows="2" 
                                    placeholder="Ej: Actualización de precios por inflación..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                    :class="{ 'border-red-500': form.errors.motivo }"
                                ></textarea>
                                <InputError :message="form.errors.motivo" class="mt-1" />
                                <p class="text-xs text-gray-500 mt-1">Este motivo quedará registrado en el historial de auditoría.</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white rounded-lg shadow border border-gray-200 p-6 space-y-4">
                            <h3 class="text-md font-bold text-gray-900 mb-3">Clasificación</h3>
                            
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
                                    <option value="">-- Sin asignar --</option>
                                    <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">{{ prov.razon_social }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-md font-bold text-gray-800 mb-3 flex items-center justify-between">
                                Inventario
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Informativo</span>
                            </h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Stock Mínimo Configurado</label>
                                <div class="text-lg font-semibold text-gray-900">
                                    {{ stockMinimoVisual }} <span class="text-sm text-gray-400 font-normal">u.</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">
                                    Para modificar niveles de alerta o ajustar cantidades, diríjase al módulo de 
                                    <Link :href="route('productos.stock')" class="text-indigo-600 hover:underline font-bold">Gestión de Stock</Link>.
                                </p>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                            <h3 class="text-md font-bold text-gray-900 mb-3">Precios</h3>
                            <div class="space-y-3">
                                <div v-for="tipoCliente in tiposCliente" :key="tipoCliente.id">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">{{ tipoCliente.nombre }}</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-gray-500 sm:text-sm">$</span>
                                        </div>
                                        <input v-model.number="form.precios[tipoCliente.id]" type="number" step="0.01" min="0" class="block w-full rounded-md border-gray-300 pl-7 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                            <InputError :message="precioError" class="mt-2" />
                        </div>

                        <div class="flex flex-col gap-3">
                            <PrimaryButton class="w-full justify-center py-3" :disabled="form.processing">
                                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                            </PrimaryButton>
                            <Link :href="route('productos.index')" class="w-full text-center py-3 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition shadow-sm">
                                Cancelar
                            </Link>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
