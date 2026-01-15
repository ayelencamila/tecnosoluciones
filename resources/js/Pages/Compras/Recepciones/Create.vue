<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    ordenCompra: Object,
});

const page = usePage();

// Formulario
const form = useForm({
    orden_compra_id: props.ordenCompra.id,
    observaciones: '',
    items: props.ordenCompra.detalles.map(detalle => ({
        detalle_orden_id: detalle.id,
        cantidad_recibida: 0,
        observacion_item: '',
        // Datos adicionales para visualización (no se envían)
        producto: detalle.producto,
        cantidad_solicitada: detalle.cantidad_solicitada,
        cantidad_pendiente: detalle.cantidad_pendiente,
        cantidad_ya_recibida: detalle.cantidad_recibida,
    })),
});

// Calcular totales
const totalItemsARecepcionar = computed(() => {
    return form.items.reduce((acc, item) => acc + (parseInt(item.cantidad_recibida) || 0), 0);
});

const itemsConCantidad = computed(() => {
    return form.items.filter(item => (parseInt(item.cantidad_recibida) || 0) > 0).length;
});

// Validar que al menos un ítem tenga cantidad
const hayItemsParaRecepcionar = computed(() => {
    return form.items.some(item => (parseInt(item.cantidad_recibida) || 0) > 0);
});

// Validar cantidades máximas
const itemsConErrorCantidad = computed(() => {
    return form.items.filter(item => {
        const cantidad = parseInt(item.cantidad_recibida) || 0;
        return cantidad > item.cantidad_pendiente;
    });
});

const formValido = computed(() => {
    return hayItemsParaRecepcionar.value && 
           itemsConErrorCantidad.value.length === 0 &&
           form.observaciones.trim().length >= 10;
});

// Recibir todo (cantidad pendiente)
const recibirTodo = (index) => {
    form.items[index].cantidad_recibida = form.items[index].cantidad_pendiente;
};

// Recibir todo de todos los items
const recibirTodoCompleto = () => {
    form.items.forEach((item, index) => {
        item.cantidad_recibida = item.cantidad_pendiente;
    });
};

// Limpiar cantidades
const limpiarCantidades = () => {
    form.items.forEach(item => {
        item.cantidad_recibida = 0;
        item.observacion_item = '';
    });
};

// Enviar formulario
const enviarFormulario = () => {
    // Filtrar solo los items que se van a enviar
    const itemsParaEnviar = form.items
        .filter(item => (parseInt(item.cantidad_recibida) || 0) > 0)
        .map(item => ({
            detalle_orden_id: item.detalle_orden_id,
            cantidad_recibida: parseInt(item.cantidad_recibida),
            observacion_item: item.observacion_item || null,
        }));

    form.transform(data => ({
        orden_compra_id: data.orden_compra_id,
        observaciones: data.observaciones,
        items: itemsParaEnviar,
    })).post(route('recepciones.store'));
};

// Formatear moneda
const formatearMoneda = (valor) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
    }).format(valor || 0);
};
</script>

<template>
    <Head title="Recepcionar Mercadería" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Recepcionar Mercadería
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Registrar recepción de productos de la orden {{ ordenCompra.numero_orden }}
                    </p>
                </div>
                <Link
                    :href="route('ordenes.show', ordenCompra.id)"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver a la OC
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Información de la OC -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Datos de la Orden de Compra
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Número de Orden</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ ordenCompra.numero_orden }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Proveedor</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ ordenCompra.proveedor?.nombre }}</p>
                            <p class="text-xs text-gray-500">CUIT: {{ ordenCompra.proveedor?.cuit }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Fecha de Orden</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ new Date(ordenCompra.fecha_orden).toLocaleDateString('es-AR') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Formulario de recepción -->
                <form @submit.prevent="enviarFormulario">
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                        <!-- Header con acciones rápidas -->
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Ítems a Recepcionar
                            </h3>
                            <div class="flex space-x-3">
                                <button
                                    @click="recibirTodoCompleto"
                                    type="button"
                                    class="px-3 py-1.5 text-sm font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-md hover:bg-green-100 dark:hover:bg-green-900/50"
                                >
                                    Recibir Todo
                                </button>
                                <button
                                    @click="limpiarCantidades"
                                    type="button"
                                    class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600"
                                >
                                    Limpiar
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de ítems -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Producto
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Solicitado
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Ya Recibido
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Pendiente
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Recibir Ahora
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Observación
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Acción
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(item, index) in form.items" :key="item.detalle_orden_id">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ item.producto?.nombre }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Código: {{ item.producto?.codigo }} | {{ item.producto?.unidad_medida }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ item.cantidad_solicitada }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ item.cantidad_ya_recibida }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2 py-1 text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded">
                                                {{ item.cantidad_pendiente }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <input
                                                v-model.number="item.cantidad_recibida"
                                                type="number"
                                                min="0"
                                                :max="item.cantidad_pendiente"
                                                :class="[
                                                    'w-24 px-3 py-2 text-center border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm',
                                                    item.cantidad_recibida > item.cantidad_pendiente
                                                        ? 'border-red-500 dark:border-red-500'
                                                        : 'border-gray-300 dark:border-gray-600'
                                                ]"
                                            />
                                            <p v-if="item.cantidad_recibida > item.cantidad_pendiente" class="mt-1 text-xs text-red-600">
                                                Máximo: {{ item.cantidad_pendiente }}
                                            </p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input
                                                v-model="item.observacion_item"
                                                type="text"
                                                placeholder="Opcional"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm"
                                            />
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <button
                                                @click="recibirTodo(index)"
                                                type="button"
                                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                            >
                                                Recibir todo
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Observaciones generales -->
                        <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Observaciones de la Recepción <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="form.observaciones"
                                rows="3"
                                placeholder="Ingrese observaciones sobre la recepción (mínimo 10 caracteres)..."
                                :class="[
                                    'w-full px-3 py-2 border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white text-sm',
                                    form.errors.observaciones 
                                        ? 'border-red-500' 
                                        : 'border-gray-300 dark:border-gray-600'
                                ]"
                            ></textarea>
                            <p v-if="form.errors.observaciones" class="mt-1 text-sm text-red-600">
                                {{ form.errors.observaciones }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ form.observaciones.length }}/10 caracteres mínimo
                            </p>
                        </div>

                        <!-- Resumen y botones -->
                        <div class="p-6 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ itemsConCantidad }}</span> ítem(s) seleccionado(s) · 
                                    <span class="font-medium text-gray-900 dark:text-white">{{ totalItemsARecepcionar }}</span> unidades a recepcionar
                                </div>
                                <div class="flex space-x-3">
                                    <Link
                                        :href="route('ordenes.show', ordenCompra.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Cancelar
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="!formValido || form.processing"
                                        :class="[
                                            'px-6 py-2 text-sm font-medium text-white rounded-md shadow-sm',
                                            formValido && !form.processing
                                                ? 'bg-indigo-600 hover:bg-indigo-700'
                                                : 'bg-gray-400 cursor-not-allowed'
                                        ]"
                                    >
                                        <span v-if="form.processing">Procesando...</span>
                                        <span v-else>Confirmar Recepción</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Mensajes de validación -->
                            <div v-if="!hayItemsParaRecepcionar" class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-md">
                                <p class="text-sm text-yellow-800 dark:text-yellow-400">
                                    Debe ingresar al menos una cantidad a recepcionar.
                                </p>
                            </div>
                            <div v-if="itemsConErrorCantidad.length > 0" class="mt-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-md">
                                <p class="text-sm text-red-800 dark:text-red-400">
                                    Hay {{ itemsConErrorCantidad.length }} ítem(s) con cantidad mayor a la pendiente.
                                </p>
                            </div>

                            <!-- Errores del servidor -->
                            <div v-if="Object.keys(form.errors).length > 0" class="mt-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-md">
                                <p class="font-medium text-sm text-red-800 dark:text-red-400">Por favor corrija los siguientes errores:</p>
                                <ul class="mt-2 list-disc list-inside text-sm text-red-700 dark:text-red-300">
                                    <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
