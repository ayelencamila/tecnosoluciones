<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    nombre: '',
    descripcion: '',
    tipo: 'gasto',
    activo: true,
});

const submit = () => {
    form.post(route('admin.categorias-gasto.store'));
};
</script>

<template>
    <AppLayout>
        <Head title="Nueva Categoría de Gasto" />

        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('admin.categorias-gasto.index')"
                    class="text-gray-500 hover:text-gray-700"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Nueva Categoría de Gasto
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Crear una nueva categoría para clasificar gastos o pérdidas
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.nombre"
                                type="text"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: Alquiler, Servicios, etc."
                            />
                            <p v-if="form.errors.nombre" class="mt-1 text-sm text-red-600">{{ form.errors.nombre }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Descripción
                            </label>
                            <textarea
                                v-model="form.descripcion"
                                rows="2"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Descripción opcional de la categoría..."
                            ></textarea>
                            <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">{{ form.errors.descripcion }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="form.tipo"
                                        type="radio"
                                        value="gasto"
                                        class="text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-gray-700">Gasto operativo</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="form.tipo"
                                        type="radio"
                                        value="perdida"
                                        class="text-red-600 focus:ring-red-500"
                                    />
                                    <span class="text-sm text-gray-700">Pérdida</span>
                                </label>
                            </div>
                            <p v-if="form.errors.tipo" class="mt-1 text-sm text-red-600">{{ form.errors.tipo }}</p>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    v-model="form.activo"
                                    type="checkbox"
                                    class="rounded text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="text-sm text-gray-700">Categoría activa</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <Link
                                :href="route('admin.categorias-gasto.index')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Guardar Categoría
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
