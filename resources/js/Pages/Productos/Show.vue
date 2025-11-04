<template>
  <AppLayout>
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-4">
          <Link 
            href="/productos"
            class="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al Listado
          </Link>
        </div>
        
        <div class="flex space-x-3">
          <Link
            :href="`/productos/${producto.id}/edit`"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 flex items-center"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Modificar
          </Link>
          
          <button
            @click="showDeleteModal = true"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 flex items-center"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Dar de Baja
          </button>
        </div>
      </div>

      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ producto.nombre }}</h1>
          <p class="mt-1 text-sm text-gray-600">{{ producto.codigo }}</p>
        </div>
        
        <span 
          class="px-4 py-2 text-sm font-semibold rounded-full"
          :class="{
            'bg-green-100 text-green-800': producto.estado?.nombre === 'Activo',
            'bg-red-100 text-red-800': producto.estado?.nombre === 'Inactivo',
            'bg-gray-100 text-gray-800': producto.estado?.nombre === 'Descontinuado'
          }"
        >
          {{ producto.estado?.nombre }}
        </span>
      </div>
    </div>

    <!-- Información General -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <!-- Tarjeta Principal -->
      <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900">Información del Producto</h2>
        </div>
        <div class="px-6 py-4">
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <dt class="text-sm font-medium text-gray-500">Código/SKU</dt>
              <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ producto.codigo }}</dd>
            </div>
            
            <div>
              <dt class="text-sm font-medium text-gray-500">Nombre</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ producto.nombre }}</dd>
            </div>
            
            <div>
              <dt class="text-sm font-medium text-gray-500">Marca</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ producto.marca || 'Sin marca' }}</dd>
            </div>
            
            <div>
              <dt class="text-sm font-medium text-gray-500">Unidad de Medida</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ producto.unidadMedida }}</dd>
            </div>
            
            <div>
              <dt class="text-sm font-medium text-gray-500">Categoría</dt>
              <dd class="mt-1 text-sm text-gray-900">
                {{ producto.categoria?.nombre }}
                <span class="text-xs text-gray-500">({{ producto.categoria?.tipoProducto }})</span>
              </dd>
            </div>
            
            <div>
              <dt class="text-sm font-medium text-gray-500">Estado</dt>
              <dd class="mt-1">
                <span 
                  class="px-3 py-1 text-xs font-semibold rounded-full"
                  :class="{
                    'bg-green-100 text-green-800': producto.estado?.nombre === 'Activo',
                    'bg-red-100 text-red-800': producto.estado?.nombre === 'Inactivo',
                    'bg-gray-100 text-gray-800': producto.estado?.nombre === 'Descontinuado'
                  }"
                >
                  {{ producto.estado?.nombre }}
                </span>
              </dd>
            </div>
            
            <div class="md:col-span-2" v-if="producto.descripcion">
              <dt class="text-sm font-medium text-gray-500">Descripción</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ producto.descripcion }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Tarjeta de Stock -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900">Control de Stock</h2>
        </div>
        <div class="px-6 py-4 space-y-4">
          <div>
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-500">Stock Actual</span>
              <span 
                class="text-2xl font-bold"
                :class="{
                  'text-green-600': producto.stockActual > producto.stockMinimo,
                  'text-yellow-600': producto.stockActual === producto.stockMinimo,
                  'text-red-600': producto.stockActual < producto.stockMinimo
                }"
              >
                {{ producto.stockActual }}
              </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="h-2 rounded-full transition-all"
                :class="{
                  'bg-green-500': producto.stockActual > producto.stockMinimo,
                  'bg-yellow-500': producto.stockActual === producto.stockMinimo,
                  'bg-red-500': producto.stockActual < producto.stockMinimo
                }"
                :style="{ width: `${Math.min((producto.stockActual / (producto.stockMinimo * 2)) * 100, 100)}%` }"
              ></div>
            </div>
          </div>
          
          <div>
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-500">Stock Mínimo</span>
              <span class="text-lg font-semibold text-gray-700">{{ producto.stockMinimo }}</span>
            </div>
          </div>
          
          <div 
            v-if="producto.stockActual < producto.stockMinimo"
            class="bg-red-50 border border-red-200 rounded-md p-3"
          >
            <div class="flex items-start">
              <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
              <div>
                <p class="text-sm font-medium text-red-800">Stock Bajo</p>
                <p class="text-xs text-red-600 mt-1">Considere reabastecer este producto</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Precios por Tipo de Cliente -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Precios por Tipo de Cliente</h2>
      </div>
      <div class="px-6 py-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="precio in producto.precios_vigentes" 
            :key="precio.id"
            class="bg-gray-50 rounded-lg p-4 border border-gray-200"
          >
            <div class="flex justify-between items-start">
              <div>
                <p class="text-sm font-medium text-gray-700">{{ precio.tipo_cliente?.nombre }}</p>
                <p class="text-xs text-gray-500 mt-1">
                  Desde: {{ formatDate(precio.fechaDesde) }}
                </p>
              </div>
              <p class="text-2xl font-bold text-indigo-600">${{ parseFloat(precio.precio).toFixed(2) }}</p>
            </div>
          </div>
        </div>
        
        <div v-if="!producto.precios_vigentes || producto.precios_vigentes.length === 0" class="text-center py-8 text-gray-500">
          No hay precios configurados para este producto
        </div>
      </div>
    </div>

    <!-- Historial de Movimientos de Stock -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Historial de Movimientos de Stock</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referencia</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observaciones</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="mov in movimientos.data" :key="mov.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDateTime(mov.fechaMovimiento) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  class="px-2 py-1 text-xs font-semibold rounded-full"
                  :class="{
                    'bg-green-100 text-green-800': mov.tipoMovimiento === 'ENTRADA',
                    'bg-red-100 text-red-800': mov.tipoMovimiento === 'SALIDA',
                    'bg-blue-100 text-blue-800': mov.tipoMovimiento === 'AJUSTE'
                  }"
                >
                  {{ mov.tipoMovimiento }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold"
                :class="{
                  'text-green-600': mov.tipoMovimiento === 'ENTRADA',
                  'text-red-600': mov.tipoMovimiento === 'SALIDA',
                  'text-blue-600': mov.tipoMovimiento === 'AJUSTE'
                }"
              >
                {{ mov.tipoMovimiento === 'ENTRADA' ? '+' : '-' }}{{ mov.cantidad }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                {{ mov.stockResultante }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ mov.referenciaTabla || '-' }}
                {{ mov.referenciaID ? `#${mov.referenciaID}` : '' }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{ mov.observaciones || '-' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div v-if="!movimientos.data || movimientos.data.length === 0" class="text-center py-8 text-gray-500">
        No hay movimientos de stock registrados
      </div>
      
      <!-- Paginación -->
      <div v-if="movimientos.data && movimientos.data.length > 0" class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Mostrando {{ movimientos.from }} a {{ movimientos.to }} de {{ movimientos.total }} movimientos
          </div>
          <div class="flex space-x-2">
            <Link
              v-for="link in movimientos.links"
              :key="link.label"
              :href="link.url"
              :class="[
                'px-3 py-1 border rounded-md text-sm',
                link.active 
                  ? 'bg-indigo-600 text-white border-indigo-600' 
                  : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
              v-html="link.label"
            >
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Confirmación de Baja -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 text-center mt-4">Dar de Baja Producto</h3>
          <div class="mt-4">
            <p class="text-sm text-gray-500 text-center mb-4">
              ¿Está seguro de dar de baja el producto <strong>{{ producto.nombre }}</strong>?
            </p>
            <textarea
              v-model="deleteForm.motivo"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md"
              placeholder="Motivo de la baja (requerido)"
            ></textarea>
          </div>
          <div class="mt-4 flex space-x-3">
            <button
              @click="showDeleteModal = false"
              class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
            >
              Cancelar
            </button>
            <button
              @click="deleteProducto"
              :disabled="!deleteForm.motivo || deleteForm.motivo.length < 5"
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

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  producto: Object,
  movimientos: Object,
});

const showDeleteModal = ref(false);
const deleteForm = useForm({
  motivo: ''
});

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-AR');
};

const formatDateTime = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleString('es-AR');
};

const deleteProducto = () => {
  if (!deleteForm.motivo || deleteForm.motivo.length < 5) {
    alert('Debe ingresar un motivo de baja (mínimo 5 caracteres)');
    return;
  }
  
  deleteForm.delete(`/productos/${props.producto.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false;
    }
  });
};
</script>
