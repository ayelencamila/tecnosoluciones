<script setup>
import { ref } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3'; 
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue'; 
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
  producto: Object,
  movimientos: Object,
  stockTotal: Number,
});

const showDeleteModal = ref(false);
const deleteForm = useForm({ motivo: '' });

const deleteProducto = () => {
  deleteForm.transform((data) => ({ ...data, _method: 'DELETE' }))
  .post(route('productos.darDeBaja', props.producto.id), {
      preserveScroll: true,
      onSuccess: () => showDeleteModal.value = false,
  });
};

const getBadgeClass = (estado) => {
    return estado === 'Activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
};
</script>

<template>
    <Head :title="producto.nombre" />
    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-6 flex justify-between items-center">
                    <Link :href="route('productos.index')" class="text-gray-600 hover:text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Volver
                    </Link>
                    <div class="space-x-3">
                        <Link :href="route('productos.edit', producto.id)" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">
                            Editar Producto
                        </Link>
                        <DangerButton @click="showDeleteModal = true" v-if="producto.estado?.nombre === 'Activo'">
                            Dar de Baja
                        </DangerButton>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Detalle del Producto</h3>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold" :class="getBadgeClass(producto.estado?.nombre)">
                                {{ producto.estado?.nombre }}
                            </span>
                        </div>
                        <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-4">
                            <!-- FOTO DEL PRODUCTO -->
                            <div v-if="producto.foto" class="col-span-2 flex justify-center mb-4">
                                <img :src="`/storage/${producto.foto}`" :alt="producto.nombre" class="w-48 h-48 object-cover rounded-lg border border-gray-200 shadow-sm">
                            </div>
                            
                            <div><dt class="text-sm font-medium text-gray-500">Nombre</dt><dd class="mt-1 text-lg font-semibold text-gray-900">{{ producto.nombre }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Código / SKU</dt><dd class="mt-1 text-md text-gray-900 font-mono bg-gray-50 inline-block px-2 rounded border">{{ producto.codigo }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Categoría</dt><dd class="mt-1 text-md text-gray-900">{{ producto.categoria?.nombre }}</dd></div>
                            
                            <div><dt class="text-sm font-medium text-gray-500">Marca</dt><dd class="mt-1 text-md text-gray-900">{{ producto.marca?.nombre || 'Sin Marca' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Unidad</dt><dd class="mt-1 text-md text-gray-900">{{ producto.unidad_medida?.nombre }} ({{ producto.unidad_medida?.abreviatura }})</dd></div>
                            
                            <!-- TIPO: PRODUCTO O SERVICIO -->
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tipo</dt>
                                <dd class="mt-1">
                                    <span v-if="producto.es_servicio" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        Servicio
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                        Producto Físico
                                    </span>
                                </dd>
                            </div>
                            
                            <div class="col-span-2"><dt class="text-sm font-medium text-gray-500">Descripción</dt><dd class="mt-1 text-md text-gray-900">{{ producto.descripcion || 'Sin descripción' }}</dd></div>
                            
                            <div class="col-span-2 pt-4 border-t">
                                <dt class="text-sm font-medium text-gray-500">Proveedor Habitual</dt>
                                <dd class="mt-1 text-md text-indigo-700 font-medium">{{ producto.proveedor_habitual?.razon_social || 'No asignado' }}</dd>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- INVENTARIO (solo si NO es servicio) -->
                        <div v-if="!producto.es_servicio" class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50"><h3 class="text-sm font-bold text-gray-700 uppercase">Inventario</h3></div>
                            <div class="p-6 text-center">
                                <div class="text-4xl font-bold text-gray-900 mb-1">{{ stockTotal }}</div>
                                <div class="text-sm text-gray-500">Unidades Disponibles</div>
                            </div>
                        </div>
                        <!-- Si ES servicio, mostrar un mensaje -->
                        <div v-else class="bg-purple-50 shadow rounded-lg overflow-hidden border border-purple-200">
                            <div class="px-6 py-4 border-b border-purple-200 bg-purple-100"><h3 class="text-sm font-bold text-purple-700 uppercase">Tipo Servicio</h3></div>
                            <div class="p-6 text-center">
                                <svg class="w-12 h-12 mx-auto text-purple-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <div class="text-sm text-purple-600">Este ítem no maneja stock</div>
                            </div>
                        </div>
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50"><h3 class="text-sm font-bold text-gray-700 uppercase">Precios Vigentes</h3></div>
                            <div class="divide-y divide-gray-100">
                                <div v-for="precio in producto.precios" :key="precio.id" class="px-6 py-3 flex justify-between items-center" v-show="!precio.fechaHasta">
                                    <span class="text-sm text-gray-600">{{ precio.tipo_cliente?.nombreTipo }}</span>
                                    <span class="text-lg font-bold text-gray-900">{{ new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(precio.precio) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        </AppLayout>
</template>