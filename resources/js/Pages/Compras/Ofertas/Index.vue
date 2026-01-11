<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { debounce } from 'lodash';

const props = defineProps({
    ofertas: Object,
    filters: Object,
    productosConOfertas: {
        type: Array,
        default: () => []
    },
});

const search = ref(props.filters?.search || '');

// Búsqueda con debounce
watch(search, debounce((value) => {
    router.get(
        route('ofertas.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 300));

const estadoClass = (estado) => {
    switch (estado) {
        case 'Pendiente': return 'bg-yellow-100 text-yellow-800';
        case 'Pre-aprobada': return 'bg-blue-100 text-blue-800';
        case 'Elegida': return 'bg-green-100 text-green-800';
        case 'Procesada': return 'bg-indigo-100 text-indigo-800';
        case 'Rechazada': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Paginación - Mismo patrón que Clientes
const getPaginationLabel = (label, index, totalLinks) => {
    if (index === 0) return '&laquo;';
    if (index === totalLinks - 1) return '&raquo;';
    return label;
};

// Ir a comparar por producto
const compararPorProducto = (productoId) => {
    router.get(route('ofertas.comparar', { producto_id: productoId }));
};
</script>

<template>
    <Head title="Ofertas de Compra" />

    <AppLayout>
        <template #header>
            Gestión de Ofertas de Compra
        </template>

        <div class="max-w-7xl mx-auto">
            
            <!-- Panel de Comparación Rápida (si hay productos con múltiples ofertas) -->
            <div v-if="productosConOfertas && productosConOfertas.length > 0" class="mb-6 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-4 border border-purple-200">
                <h3 class="text-sm font-semibold text-purple-800 mb-3 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Comparar Ofertas por Producto
                </h3>
                <div class="flex flex-wrap gap-2">
                    <button 
                        v-for="prod in productosConOfertas" 
                        :key="prod.id"
                        @click="compararPorProducto(prod.id)"
                        class="inline-flex items-center px-3 py-1.5 text-sm bg-white rounded-lg border border-purple-300 text-purple-700 hover:bg-purple-100 transition-colors">
                        {{ prod.nombre }}
                        <span class="ml-2 px-1.5 py-0.5 text-xs bg-purple-200 text-purple-800 rounded-full">
                            {{ prod.ofertas_count }} ofertas
                        </span>
                    </button>
                </div>
            </div>
                
            <div class="flex justify-between mb-6">
                <div class="w-1/3">
                    <TextInput
                        v-model="search"
                        placeholder="Buscar por código o proveedor..."
                        class="w-full"
                    />
                </div>
                <Link :href="route('ofertas.create')">
                    <PrimaryButton>
                        + Registrar Oferta Manual
                    </PrimaryButton>
                </Link>
            </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Rec.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proveedor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="oferta in ofertas.data" :key="oferta.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                        <Link :href="route('ofertas.show', oferta.id)">
                                            {{ oferta.codigo_oferta }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ new Date(oferta.fecha_recepcion).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ oferta.proveedor.razon_social }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="estadoClass(oferta.estado.nombre)">
                                            {{ oferta.estado.nombre }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                        ${{ Number(oferta.total_estimado).toLocaleString('es-AR', {minimumFractionDigits: 2}) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('ofertas.show', oferta.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            Ver
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="ofertas.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No se encontraron ofertas registradas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200" v-if="ofertas.links && ofertas.links.length > 3">
                        <div class="flex justify-center items-center space-x-1">
                            <template v-for="(link, k) in ofertas.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-medium transition-all duration-150"
                                    :class="link.active 
                                        ? 'bg-indigo-600 text-white shadow-md ring-2 ring-indigo-300' 
                                        : 'bg-white text-gray-600 border border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'"
                                >
                                    <span v-html="getPaginationLabel(link.label, k, ofertas.links.length)"></span>
                                </Link>
                                <span 
                                    v-else 
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-sm text-gray-300 border border-gray-200 cursor-not-allowed" 
                                    v-html="getPaginationLabel(link.label, k, ofertas.links.length)"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>
        </div>
    </AppLayout>
</template>