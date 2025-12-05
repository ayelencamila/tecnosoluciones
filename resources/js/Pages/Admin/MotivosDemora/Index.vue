<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    motivos: Array
});

const deleteMotivo = (motivo) => {
    if (confirm(`¿Estás seguro de desactivar "${motivo.nombre}"?`)) {
        router.delete(route('admin.motivos-demora.destroy', motivo.motivoDemoraID));
    }
};

const toggleMotivo = (motivo) => {
    router.patch(route('admin.motivos-demora.toggle', motivo.motivoDemoraID), {}, {
        preserveScroll: true
    });
};

// Función para mover arriba/abajo
const moveUp = (index) => {
    if (index === 0) return;
    const newOrder = [...props.motivos];
    [newOrder[index - 1], newOrder[index]] = [newOrder[index], newOrder[index - 1]];
    updateOrder(newOrder);
};

const moveDown = (index) => {
    if (index === props.motivos.length - 1) return;
    const newOrder = [...props.motivos];
    [newOrder[index], newOrder[index + 1]] = [newOrder[index + 1], newOrder[index]];
    updateOrder(newOrder);
};

const updateOrder = (orderedMotivos) => {
    const payload = orderedMotivos.map((m, idx) => ({
        motivoDemoraID: m.motivoDemoraID,
        orden: idx + 1
    }));

    router.post(route('admin.motivos-demora.reorder'), { motivos: payload }, {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Gestión de Motivos de Demora" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Motivos de Demora de Reparaciones</h2>
                <Link :href="route('admin.motivos-demora.create')">
                    <PrimaryButton>
                        + Nuevo Motivo
                    </PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    
                    <div class="p-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Bonificación</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pausa SLA</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(motivo, index) in motivos" :key="motivo.motivoDemoraID" :class="!motivo.activo ? 'opacity-50' : ''">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center space-x-1">
                                            <span>{{ motivo.orden }}</span>
                                            <div class="flex flex-col ml-2">
                                                <button @click="moveUp(index)" :disabled="index === 0" class="text-xs text-gray-400 hover:text-indigo-600 disabled:opacity-30">▲</button>
                                                <button @click="moveDown(index)" :disabled="index === motivos.length - 1" class="text-xs text-gray-400 hover:text-indigo-600 disabled:opacity-30">▼</button>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-700">{{ motivo.codigo }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ motivo.nombre }}
                                        <div v-if="motivo.descripcion" class="text-xs text-gray-500 mt-1">{{ motivo.descripcion }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span v-if="motivo.requiere_bonificacion" class="text-green-600 font-bold">✓</span>
                                        <span v-else class="text-gray-300">–</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span v-if="motivo.pausa_sla" class="text-blue-600 font-bold">✓</span>
                                        <span v-else class="text-gray-300">–</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <button @click="toggleMotivo(motivo)" :class="motivo.activo ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'" class="px-2 py-1 text-xs leading-5 font-semibold rounded-full transition">
                                            {{ motivo.activo ? 'Activo' : 'Inactivo' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                        <Link :href="route('admin.motivos-demora.edit', motivo.motivoDemoraID)" class="text-indigo-600 hover:text-indigo-900">Editar</Link>
                                        <button @click="deleteMotivo(motivo)" class="text-red-600 hover:text-red-900">Desactivar</button>
                                    </td>
                                </tr>
                                <tr v-if="motivos.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No hay motivos de demora registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
