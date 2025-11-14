<script setup>
import { ref } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3'; 
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue'; 

const props = defineProps({
  producto: Object,
  movimientos: Object,
});

const showDeleteModal = ref(false);
const deleteForm = useForm({
  motivo: '' 
});


const deleteProducto = () => {
  // Ya no usamos alert(). El FormRequest (DarDeBajaProductoRequest)
  // se encargará de validar si el motivo está vacío o es muy corto.
  // Inertia mostrará el error automáticamente en el modal.
  
  deleteForm.transform((data) => ({
    ...data,
    _method: 'DELETE' // Laravel Resource usa DELETE para 'destroy'
  }))
  .post(route('productos.destroy', props.producto.id), { // Usamos POST para enviar el form-data (motivo)
      preserveScroll: true,
      onSuccess: () => {
          showDeleteModal.value = false;
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
        <!-- ... (Todo tu template de Show.vue es excelente y no necesita cambios) ... -->
        <!-- ... (Solo asegúrate de que el modal de baja tenga el InputError) ... -->

        <!-- MODAL DE CONFIRMACIÓN DE BAJA (CU-27) -->
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
                        <!-- KENDALL: Feedback de error en el modal -->
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
