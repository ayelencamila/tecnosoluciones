<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { debounce } from 'lodash';

const props = defineProps({
  stocks: Object, // Paginador de MODELO STOCK
  categorias: Array,
  filters: Object,
  stats: Object,
});

const form = ref({
  search: props.filters?.search || '',
  categoria_id: props.filters?.categoria_id || '',
  stock_bajo: props.filters?.stock_bajo === 'true',
});

const categoriasOptions = computed(() => [
    { value: '', label: 'Todas las Categorías' },
    ...props.categorias.map(c => ({ value: c.id, label: c.nombre }))
]);

watch(form, debounce(() => {
  router.get(route('productos.stock'), form.value, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300), { deep: true });

const resetFilters = () => {
  form.value = { search: '', categoria_id: '', stock_bajo: false };
};

// Helpers visuales
const getStockColor = (actual, minimo) => {
    if (actual === 0) return 'text-red-600 bg-red-50 border-red-200';
    if (actual <= minimo) return 'text-yellow-600 bg-yellow-50 border-yellow-200';
    return 'text-green-600 bg-green-50 border-green-200';
};
</script>

<template>
  <Head title="Consulta de Stock" />
  <AppLayout>
    <template #header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Consulta de Inventario</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-6 bg-white shadow-sm rounded-lg p-4 flex items-center justify-between border-l-4 border-indigo-500">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Estado del Depósito Principal</h3>
                <p class="text-sm text-gray-500">Monitor de niveles de existencia en tiempo real.</p>
            </div>
            <div class="text-right">
                <span class="block text-2xl font-bold text-red-600">{{ stats?.stockBajo || 0 }}</span>
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Alertas de Stock Bajo</span>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="w-full">
                    <TextInput v-model="form.search" placeholder="Buscar producto..." class="w-full" />
                </div>
                <div class="w-full">
                    <SelectInput v-model="form.categoria_id" :options="categoriasOptions" class="w-full" />
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center p-2 border rounded cursor-pointer hover:bg-gray-50 flex-1 mr-2 bg-white h-[42px]">
                        <input type="checkbox" v-model="form.stock_bajo" class="rounded text-indigo-600 shadow-sm" />
                        <span class="ml-2 text-sm font-medium text-gray-700">Ver solo Quiebres/Bajos</span>
                    </label>
                    <button @click="resetFilters" class="text-sm text-gray-500 underline hover:text-indigo-600">Limpiar</button>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Código</th>
                  <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Producto</th>
                  <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Categoría</th>
                  <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Depósito</th>
                  <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Disponible</th>
                  <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Mínimo</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="stockItem in stocks.data" :key="stockItem.stock_id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ stockItem.producto?.codigo }}</td>
                  <td class="px-6 py-4 text-sm font-medium text-gray-900">
                      {{ stockItem.producto?.nombre }}
                      <span class="block text-xs text-gray-400 font-normal">{{ stockItem.producto?.marca }}</span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500">{{ stockItem.producto?.categoria?.nombre }}</td>
                  <td class="px-6 py-4 text-sm text-gray-500">{{ stockItem.deposito?.nombre }}</td>
                  
                  <td class="px-6 py-4 text-center">
                    <span class="px-3 py-1 inline-flex text-sm font-bold rounded-full border"
                          :class="getStockColor(stockItem.cantidad_disponible, stockItem.stock_minimo)">
                        {{ stockItem.cantidad_disponible }}
                    </span>
                  </td>
                  
                  <td class="px-6 py-4 text-center text-sm text-gray-500">
                    {{ stockItem.stock_minimo }}
                  </td>
                </tr>
                <tr v-if="stocks.data.length === 0">
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        No se encontraron registros de stock.
                    </td>
                </tr>
              </tbody>
            </table>
          </div>
           <div class="px-6 py-4 border-t border-gray-200 bg-gray-50" v-if="stocks.links.length > 3">
                <div class="flex justify-center space-x-1">
                    <template v-for="(link, k) in stocks.links" :key="k">
                        <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 border rounded text-sm" :class="link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'" />
                        <span v-else class="px-3 py-1 border rounded text-sm bg-gray-100 text-gray-400" v-html="link.label"></span>
                    </template>
                </div>
            </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
