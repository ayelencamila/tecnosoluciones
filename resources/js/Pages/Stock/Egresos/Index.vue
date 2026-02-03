<script setup>
/**
 * Vista: Listado de Egresos de Stock
 * 
 * Muestra el historial de salidas de stock por motivos distintos a venta:
 * robo, pérdida, defectuosos, uso interno, etc.
 */
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    egresos: Object,
    tiposEgreso: Array,
    filters: Object,
})

function formatDate(date) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    })
}

function getTipoClass(tipo) {
    const nombre = tipo?.nombre?.toLowerCase() || ''
    if (nombre.includes('robo')) return 'bg-red-100 text-red-700'
    if (nombre.includes('defectuoso')) return 'bg-orange-100 text-orange-700'
    if (nombre.includes('pérdida') || nombre.includes('merma')) return 'bg-amber-100 text-amber-700'
    if (nombre.includes('muestra') || nombre.includes('donación')) return 'bg-purple-100 text-purple-700'
    if (nombre.includes('uso interno')) return 'bg-blue-100 text-blue-700'
    return 'bg-gray-100 text-gray-700'
}
</script>

<template>
    <AppLayout title="Egresos de Stock">
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="font-bold text-xl text-gray-900">Egresos de Stock</h2>
                    <p class="text-sm text-gray-500 mt-1">Registro de salidas por pérdida, robo, defectuosos y otros</p>
                </div>
                <Link 
                    :href="route('egresos-stock.create')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 text-white font-semibold text-sm rounded-lg hover:bg-red-700 transition-colors shadow-sm"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                    Registrar Egreso
                </Link>
            </div>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Tabla de Egresos -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center gap-3">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Historial de Egresos</h3>
                            <p class="text-xs text-gray-500">{{ egresos.total || 0 }} registros</p>
                        </div>
                    </div>

                    <div v-if="egresos.data?.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="egreso in egresos.data" :key="egreso.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ formatDate(egreso.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 flex-shrink-0 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ egreso.producto?.nombre }}</p>
                                                <p class="text-xs text-gray-500 font-mono">{{ egreso.producto?.codigo }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium"
                                            :class="getTipoClass(egreso.tipo_movimiento)"
                                        >
                                            {{ egreso.tipo_movimiento?.nombre }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-sm font-bold text-red-600 bg-red-50">
                                            -{{ egreso.cantidad }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" :title="egreso.motivo">
                                        {{ egreso.motivo || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ egreso.usuario?.name || 'Sistema' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="p-12 text-center">
                        <div class="mx-auto h-16 w-16 text-gray-300 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Sin egresos registrados</h3>
                        <p class="text-sm text-gray-500 mb-4">No hay salidas de stock por pérdida, robo u otros motivos.</p>
                        <Link 
                            :href="route('egresos-stock.create')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Registrar primer egreso
                        </Link>
                    </div>

                    <!-- Paginación -->
                    <div v-if="egresos.links?.length > 3" class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                        <nav class="flex items-center justify-between">
                            <p class="text-sm text-gray-500">
                                Mostrando {{ egresos.from }} a {{ egresos.to }} de {{ egresos.total }} resultados
                            </p>
                            <div class="flex gap-1">
                                <template v-for="link in egresos.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        class="px-3 py-1 text-sm rounded-md"
                                        :class="link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border'"
                                        v-html="link.label"
                                    />
                                    <span
                                        v-else
                                        class="px-3 py-1 text-sm text-gray-400"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
