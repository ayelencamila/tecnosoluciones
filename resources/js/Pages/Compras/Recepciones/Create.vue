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
        <!-- HEADER -->
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Gestión de Compras
                    </h2>
                    <p class="text-sm font-medium text-indigo-800 dark:text-indigo-300 tracking-wide mt-1">
                        Recepciones › Registrar Recepción de Mercadería
                    </p>
                </div>
                <Link
                    :href="route('ordenes.show', ordenCompra.id)"
                    class="px-5 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                    Volver
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Título y descripción -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">
                        REGISTRAR RECEPCIÓN DE MERCADERÍA
                    </h1>

                    <!-- Tarjeta informativa -->
                    <div class="bg-gradient-to-r from-indigo-50 to-emerald-50 dark:from-indigo-900/20 dark:to-emerald-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    Registre las cantidades recibidas para cada producto de la Orden de Compra.
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    Puede realizar recepciones parciales o totales. El stock se actualizará automáticamente.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de la OC -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                        </svg>
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
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                                <svg class="w-5 h-5 mr-2 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                Ítems a Recepcionar
                            </h3>
                            <div class="flex space-x-3">
                                <button
                                    @click="recibirTodoCompleto"
                                    type="button"
                                    class="px-4 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-md hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors"
                                >
                                    Recibir Todo
                                </button>
                                <button
                                    @click="limpiarCantidades"
                                    type="button"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    Limpiar
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de ítems -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-indigo-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                            Producto
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                            Solicitado
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                            Ya Recibido
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                            Pendiente
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                            Recibir Ahora
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                            Observación
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                            Acción
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(item, index) in form.items" :key="item.detalle_orden_id" class="hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-colors">
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
                                        class="px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        Cancelar
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="!formValido || form.processing"
                                        :class="[
                                            'px-6 py-2.5 text-sm font-bold text-white rounded-md shadow-sm uppercase tracking-wide transition-colors',
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
