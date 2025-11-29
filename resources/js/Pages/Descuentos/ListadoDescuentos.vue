<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({ 
    descuentos: Object,
    filters: Object 
});

const deleteDescuento = (id) => {
    if(confirm('¿Eliminar este descuento?')) router.delete(route('descuentos.destroy', id));
};
</script>

<template>
    <Head title="Descuentos" />
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Descuentos</h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-between items-center">
                <div class="text-gray-500 text-sm">Administra cupones y reglas de precios.</div>
                <Link :href="route('descuentos.create')"><PrimaryButton>+ Nuevo Descuento</PrimaryButton></Link>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Descripción</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Regla</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Valor</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="d in descuentos.data" :key="d.descuento_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600">{{ d.codigo }}</td>
                                <td class="px-6 py-4 text-sm">{{ d.descripcion }}</td>
                                <td class="px-6 py-4 text-center text-xs">
                                    <span class="block font-bold text-gray-700">{{ d.tipo?.nombre }}</span>
                                    <span class="text-gray-500">{{ d.aplicabilidad?.nombre }}</span>
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-gray-800">
                                    {{ d.tipo?.codigo === 'PORCENTAJE' ? d.valor + '%' : '$' + d.valor }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="d.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 text-xs rounded-full font-semibold">
                                        {{ d.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <Link :href="route('descuentos.edit', d.descuento_id)" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</Link>
                                    <button @click="deleteDescuento(d.descuento_id)" class="text-red-600 hover:text-red-900">Borrar</button>
                                </td>
                            </tr>
                             <tr v-if="descuentos.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">No hay descuentos registrados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>