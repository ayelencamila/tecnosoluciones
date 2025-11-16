<script setup>
import { ref } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3'; 
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue'; 

// Las props se reciben correctamente
const props = defineProps({
  producto: Object,
  movimientos: Object,
});

// Tu lógica del modal de baja (está perfecta)
const showDeleteModal = ref(false);
const deleteForm = useForm({
  motivo: '' 
});

const deleteProducto = () => {
  deleteForm.transform((data) => ({
    ...data,
    _method: 'POST'
  }))
  .post(route('productos.darDeBaja', props.producto.id), {
      preserveScroll: true,
      onSuccess: () => {
          showDeleteModal.value = false;
          // Aquí podrías redirigir al index si lo deseas
          // router.visit(route('productos.index'));
      },
      onError: () => {
          // El error del motivo aparecerá solo
      }
  });
};
</script>

<template>
    <Head :title="producto.nombre" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detalle del Producto: {{ producto.nombre }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-2xl font-bold mb-4">{{ producto.nombre }}</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600">Descripción:</p>
                            <p class="text-gray-800">{{ producto.descripcion || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Stock Actual:</p>
                            <p class="text-lg font-semibold">{{ producto.stock }}</p>
                        </div>
                         <div>
                            <p class="text-gray-600">Precio:</p>
                            <p class="text-lg font-semibold">${{ producto.precio }}</p>
                        </div>
                        </div>

                    <hr class="my-6">

                    <div class="flex space-x-4">
                        <Link :href="route('productos.edit', producto.id)" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Editar
                        </Link>
                        <button 
                            @click="showDeleteModal = true"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Dar de Baja
                        </button>
                    </div>
                </div>

                <div class="mt-8 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-xl font-semibold mb-4">Historial de Movimientos</h4>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsable</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="movimientos.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay movimientos registrados.</td>
                                </tr>
                                <tr v-for="mov in movimientos" :key="mov.id">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ new Date(mov.created_at).toLocaleString() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ mov.tipo_movimiento }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ mov.cantidad }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ mov.user?.name || 'Sistema' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 text-center mt-4">Dar de Baja Producto</h3>
                    <div class="mt-4">
                        <textarea
                            v-model="deleteForm.motivo"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md"
                            :class="{'border-red-500': deleteForm.errors.motivo}"
                            placeholder="Motivo de la baja (requerido, mín. 5 caracteres)"
                        ></textarea>
                        <InputError :message="deleteForm.errors.motivo" class="mt-1" />
                    </div>
                    <div class="mt-4 flex space-x-3">
                        <button @click="showDeleteModal = false" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                            Cancelar
                        </button>
                        <button
                            @click="deleteProducto"
                            :disabled="deleteForm.processing"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50"
                        >
                            Confirmar Baja
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>