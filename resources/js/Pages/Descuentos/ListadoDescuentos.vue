<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { debounce } from 'lodash';

const props = defineProps({
    descuentos: Object,
    filters: Object,
});

// Filtros locales reactivos
const search = ref(props.filters.search || '');
const tipo = ref(props.filters.tipo || '');
const activo = ref(props.filters.activo ?? '');

// Búsqueda con debounce
const debouncedSearch = debounce(() => {
    router.get(route('descuentos.index'), {
        search: search.value,
        tipo: tipo.value,
        activo: activo.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

const applyFilters = () => {
    debouncedSearch();
};

const clearFilters = () => {
    search.value = '';
    tipo.value = '';
    activo.value = '';
    router.get(route('descuentos.index'));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-AR');
};
</script>

<template>
    <Head title="Gestión de Descuentos" />
    
    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestión de Descuentos
                </h2>
                <Link :href="route('descuentos.create')">
                    <PrimaryButton>
                        + Nuevo Descuento
                    </PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filtros -->
                <div class="bg-white shadow-sm sm:rounded-lg mb-6 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <TextInput
                                v-model="search"
                                @input="applyFilters"
                                type="text"
                                placeholder="Buscar por código o descripción..."
                                class="w-full"
                            />
                        </div>
                        <div>
                            <SelectInput
                                v-model="tipo"
                                @change="applyFilters"
                                class="w-full"
                            >
                                <option value="">Todos los tipos</option>
                                <option value="porcentaje">Porcentaje</option>
                                <option value="monto_fijo">Monto Fijo</option>
                            </SelectInput>
                        </div>
                        <div>
                            <SelectInput
                                v-model="activo"
                                @change="applyFilters"
                                class="w-full"
                            >
                                <option value="">Todos los estados</option>
                                <option value="1">Activos</option>
                                <option value="0">Inactivos</option>
                            </SelectInput>
                        </div>
                        <div>
                            <button
                                @click="clearFilters"
                                type="button"
                                class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-sm font-medium"
                            >
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de descuentos -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Vigencia</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="descuento in descuentos.data" :key="descuento.descuento_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ descuento.codigo }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ descuento.descripcion }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <span v-if="descuento.tipo === 'porcentaje'" class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                        Porcentaje
                                    </span>
                                    <span v-else class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">
                                        Monto Fijo
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-right font-medium">
                                    <span v-if="descuento.tipo === 'porcentaje'">{{ descuento.valor }}%</span>
                                    <span v-else>{{ formatCurrency(descuento.valor) }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 text-center">
                                    <div v-if="descuento.valido_desde || descuento.valido_hasta">
                                        <div v-if="descuento.valido_desde">Desde: {{ formatDate(descuento.valido_desde) }}</div>
                                        <div v-if="descuento.valido_hasta">Hasta: {{ formatDate(descuento.valido_hasta) }}</div>
                                    </div>
                                    <span v-else class="text-gray-400">Sin límite</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        :class="descuento.activo 
                                            ? 'px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium' 
                                            : 'px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium'"
                                    >
                                        {{ descuento.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-sm">
                                    <Link
                                        :href="route('descuentos.show', descuento.descuento_id)"
                                        class="text-indigo-600 hover:text-indigo-900 mr-3"
                                    >
                                        Ver
                                    </Link>
                                    <Link
                                        :href="route('descuentos.edit', descuento.descuento_id)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        Editar
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <div v-if="descuentos.links.length > 3" class="bg-gray-50 px-6 py-3 border-t">
                        <div class="flex justify-center space-x-2">
                            <Link
                                v-for="(link, index) in descuentos.links"
                                :key="index"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1 rounded',
                                    link.active 
                                        ? 'bg-indigo-600 text-white' 
                                        : 'bg-white text-gray-700 hover:bg-gray-100',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>

                    <!-- Mensaje si no hay descuentos -->
                    <div v-if="descuentos.data.length === 0" class="text-center py-12 text-gray-500">
                        No se encontraron descuentos con los filtros aplicados.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
