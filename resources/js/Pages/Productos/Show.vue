<script setup>
import { ref } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3'; 
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue'; 
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
  producto: Object,
  movimientos: Object, // Collection
  stockTotal: Number, // Dato calculado en el controlador
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
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Nombre</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900">{{ producto.nombre }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Código / SKU</dt>
                                <dd class="mt-1 text-md text-gray-900 font-mono bg-gray-50 inline-block px-2 rounded border">{{ producto.codigo }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Categoría</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ producto.categoria?.nombre }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Marca</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ producto.marca || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Unidad</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ producto.unidadMedida }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Descripción</dt>
                                <dd class="mt-1 text-md text-gray-900">{{ producto.descripcion || 'Sin descripción' }}</dd>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-sm font-bold text-gray-700 uppercase">Inventario</h3>
                            </div>
                            <div class="p-6 text-center">
                                <div class="text-4xl font-bold text-gray-900 mb-1">{{ stockTotal }}</div>
                                <div class="text-sm text-gray-500">Unidades Disponibles</div>
                                <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400 text-left">
                                    Desglose por depósito disponible en la sección "Consultar Stock".
                                </div>
                            </div>
                        </div>

                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-sm font-bold text-gray-700 uppercase">Precios Vigentes</h3>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <div v-for="precio in producto.precios" :key="precio.id" class="px-6 py-3 flex justify-between items-center" v-show="!precio.fechaHasta">
                                    <span class="text-sm text-gray-600">{{ precio.tipo_cliente?.nombreTipo }}</span>
                                    <span class="text-lg font-bold text-gray-900">$ {{ precio.precio }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-96 p-6 transform transition-all">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmar Baja</h3>
                <p class="text-sm text-gray-500 mb-4">
                    ¿Está seguro de que desea dar de baja este producto? Esta acción cambiará su estado a "Inactivo".
                </p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo (Requerido)</label>
                    <textarea v-model="deleteForm.motivo" rows="3" class="w-full rounded border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"></textarea>
                    <InputError :message="deleteForm.errors.motivo" class="mt-1" />
                </div>
                <div class="flex justify-end space-x-3">
                    <button @click="showDeleteModal = false" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button @click="deleteProducto" :disabled="deleteForm.processing" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Confirmar</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>