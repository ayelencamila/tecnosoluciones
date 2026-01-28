<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    categorias: Array,
});

const form = useForm({
    categoria_gasto_id: '',
    fecha: new Date().toISOString().split('T')[0],
    descripcion: '',
    monto: '',
    comprobante: '',
    observaciones: '',
});

const submit = () => {
    form.post(route('gastos.store'));
};

const categoriasGastos = computed(() => props.categorias.filter(c => c.tipo === 'gasto'));
const categoriasPerdidas = computed(() => props.categorias.filter(c => c.tipo === 'perdida'));
</script>

<template>
    <AppLayout>
        <Head title="Registrar Gasto" />

        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('gastos.index')"
                    class="text-gray-500 hover:text-gray-700"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Registrar Gasto o Pérdida
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Ingrese los datos del gasto o pérdida a registrar
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
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.categoria_gasto_id"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="">Seleccione una categoría</option>
                                <optgroup label="Gastos Operativos">
                                    <option v-for="cat in categoriasGastos" :key="cat.categoria_gasto_id" :value="cat.categoria_gasto_id">
                                        {{ cat.nombre }}
                                    </option>
                                </optgroup>
                                <optgroup label="Pérdidas">
                                    <option v-for="cat in categoriasPerdidas" :key="cat.categoria_gasto_id" :value="cat.categoria_gasto_id">
                                        {{ cat.nombre }}
                                    </option>
                                </optgroup>
                            </select>
                            <p v-if="form.errors.categoria_gasto_id" class="mt-1 text-sm text-red-600">{{ form.errors.categoria_gasto_id }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Fecha <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.fecha"
                                    type="date"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                <p v-if="form.errors.fecha" class="mt-1 text-sm text-red-600">{{ form.errors.fecha }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Monto <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                                    <input
                                        v-model="form.monto"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="0.00"
                                    />
                                </div>
                                <p v-if="form.errors.monto" class="mt-1 text-sm text-red-600">{{ form.errors.monto }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Descripción <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.descripcion"
                                type="text"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: Pago de luz mes de enero"
                            />
                            <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">{{ form.errors.descripcion }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nº Comprobante / Factura
                            </label>
                            <input
                                v-model="form.comprobante"
                                type="text"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: FAC-0001-00012345"
                            />
                            <p v-if="form.errors.comprobante" class="mt-1 text-sm text-red-600">{{ form.errors.comprobante }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Observaciones
                            </label>
                            <textarea
                                v-model="form.observaciones"
                                rows="2"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Observaciones adicionales..."
                            ></textarea>
                            <p v-if="form.errors.observaciones" class="mt-1 text-sm text-red-600">{{ form.errors.observaciones }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <Link
                                :href="route('gastos.index')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Registrar Gasto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
