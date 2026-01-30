<script setup>
/**
 * Vista: Monitoreo de Stock (CU-20)
 * 
 * Dashboard de productos bajo stock mínimo con acciones para
 * generar solicitudes de cotización.
 */
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    productosBajoStock: Array,
    productosAltaRotacion: Array,
    porProveedor: Array,
    resumen: Object,
})

function getStockClass(actual, minimo) {
    const porcentaje = (actual / minimo) * 100
    if (actual === 0) return 'text-red-700 bg-red-100'
    if (porcentaje <= 50) return 'text-amber-700 bg-amber-100'
    return 'text-yellow-700 bg-yellow-100'
}
</script>

<template>
    <AppLayout title="Monitoreo de Stock">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Monitoreo de Stock
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Productos bajo punto de reorden
            </p>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Barra de acciones -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <Link
                        :href="route('solicitudes-cotizacion.index')"
                        class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Volver a Solicitudes
                    </Link>
                    <Link
                        :href="route('solicitudes-cotizacion.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nueva Solicitud
                    </Link>
                </div>

                <!-- Resumen en cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bajo Stock</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ resumen?.bajo_stock || 0 }}</p>
                            </div>
                            <div class="p-2.5 bg-red-100 rounded-lg">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Sin Stock</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ resumen?.sin_stock || 0 }}</p>
                            </div>
                            <div class="p-2.5 bg-amber-100 rounded-lg">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Sin Mínimo</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ resumen?.sin_minimo_configurado || 0 }}</p>
                            </div>
                            <div class="p-2.5 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">% Bajo Stock</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ resumen?.porcentaje_bajo_stock || 0 }}%</p>
                            </div>
                            <div class="p-2.5 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Productos por proveedor -->
                <div v-if="porProveedor?.length > 0" class="space-y-6">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                        Productos agrupados por proveedor habitual
                    </h3>
                    
                    <div v-for="grupo in porProveedor" :key="grupo.proveedor_id || 'sin'" class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="px-5 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">
                                    {{ grupo.proveedor_nombre || grupo.proveedor_habitual || 'Sin proveedor asignado' }}
                                </h4>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ grupo.cantidad_productos || grupo.productos?.length }} producto(s) bajo stock
                                </p>
                            </div>
                            <Link
                                v-if="grupo.proveedor_id"
                                :href="route('solicitudes-cotizacion.create')"
                                class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                Crear solicitud
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </Link>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Producto
                                        </th>
                                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Depósito
                                        </th>
                                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Stock Actual
                                        </th>
                                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Mínimo
                                        </th>
                                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Faltante
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="producto in grupo.productos" :key="producto.producto_id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-5 py-4">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ producto.producto?.nombre || producto.producto_nombre }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                {{ producto.producto?.codigo || producto.producto_codigo }}
                                            </p>
                                        </td>
                                        <td class="px-5 py-4 text-center text-sm text-gray-600">
                                            {{ producto.deposito?.nombre || producto.deposito || 'Principal' }}
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <span
                                                :class="getStockClass(producto.cantidad_actual || producto.stock_actual, producto.stock_minimo)"
                                                class="inline-flex px-2.5 py-0.5 text-xs font-semibold rounded-full"
                                            >
                                                {{ producto.cantidad_actual ?? producto.stock_actual }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-center text-sm text-gray-600">
                                            {{ producto.stock_minimo }}
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <span class="text-sm font-semibold text-red-600">
                                                -{{ producto.faltante ?? producto.diferencia }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Estado vacío: Stock en orden -->
                <div v-else class="bg-white border border-gray-200 rounded-lg p-12 text-center">
                    <div class="mx-auto w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900">
                        ¡Stock en orden!
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        No hay productos bajo el punto de reorden en este momento.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
