<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    kpis: Object,
    tablas: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};
</script>

<template>
    <Head title="Tablero de Control" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tablero de Control
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-green-500 p-6 transition hover:shadow-lg">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Ventas de Hoy</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(kpis.ventasHoy) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-indigo-500 p-6 transition hover:shadow-lg">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Por Cobrar</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(kpis.deudaTotal) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-red-500 p-6 transition hover:shadow-lg">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Stock Crítico</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.stockCritico }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-l-4 border-blue-500 p-6 transition hover:shadow-lg">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Prod. Activos</p>
                                <p class="text-2xl font-bold text-gray-900">{{ kpis.totalProductos }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-8 flex flex-col sm:flex-row gap-4">
                    <Link :href="route('ventas.create')" class="flex-1">
                        <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-lg shadow-md transition flex justify-center items-center text-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Nueva Venta
                        </button>
                    </Link>
                    <Link :href="route('clientes.create')" class="flex-1">
                        <button class="w-full bg-white hover:bg-gray-50 text-gray-700 font-bold py-4 px-6 rounded-lg shadow-md border border-gray-300 transition flex justify-center items-center text-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            Registrar Cliente
                        </button>
                    </Link>
                     <Link :href="route('pagos.create')" class="flex-1">
                        <button class="w-full bg-white hover:bg-gray-50 text-gray-700 font-bold py-4 px-6 rounded-lg shadow-md border border-gray-300 transition flex justify-center items-center text-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Registrar Pago
                        </button>
                    </Link>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Últimas Ventas</h3>
                            <Link :href="route('ventas.index')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">Ver todas</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="venta in tablas.ultimasVentas" :key="venta.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            {{ venta.cliente }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            {{ venta.fecha }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold text-right">
                                            {{ formatCurrency(venta.total) }}
                                        </td>
                                    </tr>
                                    <tr v-if="tablas.ultimasVentas.length === 0">
                                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No hay ventas hoy.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-red-700 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Reposición Urgente
                            </h3>
                            <Link :href="route('productos.stock')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">Gestionar Stock</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                        <th class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Disponible</th>
                                        <th class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Mínimo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(stock, idx) in tablas.productosCriticos" :key="idx" class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm text-gray-900">
                                            {{ stock.producto }}
                                            <div class="text-xs text-gray-400">{{ stock.deposito }}</div>
                                        </td>
                                        <td class="px-6 py-3 text-center text-sm font-bold text-red-600">
                                            {{ stock.cantidad }}
                                        </td>
                                        <td class="px-6 py-3 text-center text-sm text-gray-500">
                                            {{ stock.minimo }}
                                        </td>
                                    </tr>
                                    <tr v-if="tablas.productosCriticos.length === 0">
                                        <td colspan="3" class="px-6 py-6 text-center text-sm text-green-600 font-medium bg-green-50">
                                            ¡Todo en orden! No hay stock crítico.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>