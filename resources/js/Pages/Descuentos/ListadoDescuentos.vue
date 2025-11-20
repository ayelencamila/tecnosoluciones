<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { debounce } from 'lodash';

const props = defineProps({
    descuentos: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('descuentos.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const formatDate = (date) => date ? new Date(date).toLocaleDateString() : 'Indefinido';
</script>

<template>
    <Head title="Gestionar Descuentos" />
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Descuentos (CU-08)</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex justify-between">
                    <TextInput v-model="search" placeholder="Buscar por código..." class="w-1/3" />
                    <Link :href="route('descuentos.create')">
                        <PrimaryButton>+ Nuevo Descuento</PrimaryButton>
                    </Link>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Vigencia</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="desc in descuentos.data" :key="desc.descuento_id">
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600">{{ desc.codigo }}</td>
                                <td class="px-6 py-4 capitalize">{{ desc.tipo.replace('_', ' ') }}</td>
                                <td class="px-6 py-4 font-bold">
                                    {{ desc.tipo === 'porcentaje' ? desc.valor + '%' : '$' + desc.valor }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ formatDate(desc.valido_desde) }} - {{ formatDate(desc.valido_hasta) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="desc.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 rounded-full text-xs font-bold">
                                        {{ desc.activo ? 'ACTIVO' : 'INACTIVO' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('descuentos.edit', desc.descuento_id)" class="text-indigo-600 hover:underline font-bold">Editar</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
