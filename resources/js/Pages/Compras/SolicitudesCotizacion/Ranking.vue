<script setup>
/**
 * Vista: Ranking de Respuestas de Cotización (CU-20/CU-21)
 * 
 * Muestra todas las respuestas ordenadas de mejor a peor oferta.
 * Al elegir una, el sistema genera automáticamente la Orden de Compra.
 * 
 * Lineamientos:
 * - Kendall: Comparación tabular para toma de decisiones
 * - Profesor: "El sistema me debe generar todas las respuestas con la mejor oferta a la peor"
 */
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    solicitud: Object,
    ranking: Array,
    ordenGenerada: {
        type: Object,
        default: null
    }
})

const mostrarModalElegir = ref(false)
const mostrarModalExito = ref(false)
const cotizacionSeleccionada = ref(null)
const formElegir = useForm({
    motivo: 'Seleccionada como mejor oferta del ranking'
})

// Abrir modal de confirmación
function abrirModalElegir(oferta) {
    cotizacionSeleccionada.value = oferta
    mostrarModalElegir.value = true
}

// Elegir y generar orden automáticamente
function elegirYGenerarOrden() {
    formElegir.post(route('solicitudes-cotizacion.elegir-generar-orden', [props.solicitud.id, cotizacionSeleccionada.value.cotizacion_id]), {
        onSuccess: () => {
            mostrarModalElegir.value = false
            mostrarModalExito.value = true
        }
    })
}

// Ir a ver la orden generada
function verOrden() {
    router.visit(route('ordenes-compra.show', props.ordenGenerada?.id))
}

// Formatear moneda
function formatCurrency(value) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS'
    }).format(value || 0)
}

// Formatear fecha
function formatDate(date) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Clase según posición
function getPosicionClass(index) {
    if (index === 0) return 'bg-green-500'
    if (index === 1) return 'bg-gray-400'
    if (index === 2) return 'bg-amber-600'
    return 'bg-gray-300'
}

// Borde según posición
function getBordeClass(index) {
    if (index === 0) return 'border-green-400 bg-green-50'
    return 'border-gray-200 bg-white'
}
</script>

<template>
    <Head :title="`Ranking - ${solicitud.codigo_solicitud}`" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Compras
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Breadcrumb y título -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-indigo-600 uppercase tracking-wider mb-1">
                                COMPRAS > SOLICITUDES > RANKING DE RESPUESTAS
                            </p>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Ranking de Ofertas Recibidas
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Solicitud <span class="font-mono font-semibold text-indigo-600">{{ solicitud.codigo_solicitud }}</span>
                            </p>
                        </div>
                        <Link 
                            :href="route('solicitudes-cotizacion.show', solicitud.id)"
                            class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            Volver a Solicitud
                        </Link>
                    </div>
                </div>

                <!-- Info de la solicitud -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Productos solicitados:</span>
                            <span class="ml-2 font-semibold">{{ solicitud.detalles?.length || 0 }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Proveedores invitados:</span>
                            <span class="ml-2 font-semibold">{{ solicitud.cotizaciones_proveedores?.length || 0 }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Respuestas recibidas:</span>
                            <span class="ml-2 font-semibold text-green-600">{{ ranking.length }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Vencimiento:</span>
                            <span class="ml-2 font-semibold">{{ formatDate(solicitud.fecha_vencimiento) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Mensaje si no hay respuestas -->
                <div v-if="ranking.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Sin respuestas aún</h3>
                    <p class="mt-2 text-gray-500">Los proveedores aún no han respondido a esta solicitud.</p>
                </div>

                <!-- Tabla de Ranking -->
                <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-sm font-bold text-gray-700 uppercase flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                            Ranking ordenado por menor precio total
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Seleccione la mejor oferta para generar automáticamente la Orden de Compra
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                        Pos.
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Proveedor
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Productos
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Ofertado
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha Respuesta
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-48">
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr 
                                    v-for="(oferta, index) in ranking" 
                                    :key="oferta.cotizacion_id"
                                    :class="getBordeClass(index)"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <!-- Posición -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            :class="getPosicionClass(index)"
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                    </td>

                                    <!-- Proveedor -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div>
                                                <p class="font-semibold text-gray-900">{{ oferta.proveedor?.razon_social }}</p>
                                                <p class="text-sm text-gray-500">{{ oferta.proveedor?.email }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Productos cotizados -->
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm">
                                            <span class="font-semibold text-gray-900">{{ oferta.productos_cotizados }}</span>
                                            <span class="text-gray-500">/{{ oferta.productos_requeridos }}</span>
                                        </span>
                                        <span v-if="oferta.cotizo_completo" class="ml-1 text-green-600 text-xs font-medium">
                                            Completo
                                        </span>
                                    </td>

                                    <!-- Total -->
                                    <td class="px-6 py-4 text-right">
                                        <p class="text-xl font-bold" :class="index === 0 ? 'text-green-600' : 'text-gray-900'">
                                            {{ formatCurrency(oferta.total) }}
                                        </p>
                                    </td>

                                    <!-- Fecha respuesta -->
                                    <td class="px-6 py-4 text-center text-sm text-gray-500">
                                        {{ formatDate(oferta.fecha_respuesta) }}
                                    </td>

                                    <!-- Acción -->
                                    <td class="px-6 py-4 text-center">
                                        <button
                                            @click="abrirModalElegir(oferta)"
                                            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                            :class="index === 0 
                                                ? 'bg-green-600 text-white hover:bg-green-700' 
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300'"
                                        >
                                            {{ index === 0 ? 'Elegir Mejor Oferta' : 'Elegir esta Oferta' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Detalle expandido de cada oferta -->
                    <div class="border-t border-gray-200">
                        <div class="px-6 py-4 bg-gray-50">
                            <h4 class="text-sm font-semibold text-gray-700 uppercase">Detalle de Productos por Proveedor</h4>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div 
                                v-for="(oferta, index) in ranking" 
                                :key="'detalle-' + oferta.cotizacion_id"
                                class="px-6 py-4"
                            >
                                <div class="flex items-center gap-2 mb-3">
                                    <span 
                                        :class="getPosicionClass(index)"
                                        class="w-6 h-6 rounded-full flex items-center justify-center text-white font-bold text-xs"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                    <span class="font-semibold text-gray-900">{{ oferta.proveedor?.razon_social }}</span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-gray-500 text-xs uppercase">
                                                <th class="text-left py-2 pr-4">Producto</th>
                                                <th class="text-right py-2 px-4">Precio Unit.</th>
                                                <th class="text-center py-2 px-4">Cantidad</th>
                                                <th class="text-center py-2 px-4">Plazo</th>
                                                <th class="text-right py-2 pl-4">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="resp in oferta.respuestas" :key="resp.id" class="border-t border-gray-100">
                                                <td class="py-2 pr-4">
                                                    <span class="font-medium text-gray-900">{{ resp.producto?.nombre }}</span>
                                                    <span class="text-gray-500 text-xs ml-2">{{ resp.producto?.codigo }}</span>
                                                </td>
                                                <td class="py-2 px-4 text-right text-gray-900">{{ formatCurrency(resp.precio_unitario) }}</td>
                                                <td class="py-2 px-4 text-center text-gray-700">{{ resp.cantidad_disponible }}</td>
                                                <td class="py-2 px-4 text-center text-gray-700">{{ resp.plazo_entrega_dias }} días</td>
                                                <td class="py-2 pl-4 text-right font-semibold text-gray-900">
                                                    {{ formatCurrency(resp.precio_unitario * resp.cantidad_disponible) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="border-t-2 border-gray-200">
                                                <td colspan="4" class="py-2 text-right font-semibold text-gray-700">Total:</td>
                                                <td class="py-2 pl-4 text-right text-lg font-bold" :class="index === 0 ? 'text-green-600' : 'text-gray-900'">
                                                    {{ formatCurrency(oferta.total) }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmación -->
        <div v-if="mostrarModalElegir" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="mostrarModalElegir = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Confirmar Selección
                    </h3>
                    
                    <div class="my-4 p-4 bg-green-50 rounded-lg border border-green-200">
                        <p class="font-semibold text-green-800">{{ cotizacionSeleccionada?.proveedor?.razon_social }}</p>
                        <p class="text-2xl font-bold text-green-700 mt-1">{{ formatCurrency(cotizacionSeleccionada?.total) }}</p>
                        <p class="text-sm text-green-600 mt-1">
                            {{ cotizacionSeleccionada?.productos_cotizados }}/{{ cotizacionSeleccionada?.productos_requeridos }} productos cotizados
                        </p>
                    </div>

                    <div class="bg-indigo-50 rounded-lg p-4 mb-4">
                        <p class="text-sm text-indigo-800">
                            <strong>Al confirmar, el sistema:</strong>
                        </p>
                        <ul class="text-sm text-indigo-700 mt-2 space-y-1">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Marcará esta cotización como elegida
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Generará automáticamente la Orden de Compra
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Enviará la orden al proveedor por WhatsApp/Email
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Cerrará esta solicitud de cotización
                            </li>
                        </ul>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Motivo de selección (opcional)
                        </label>
                        <textarea
                            v-model="formElegir.motivo"
                            rows="2"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Ej: Mejor precio y disponibilidad inmediata"
                        ></textarea>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button
                            type="button"
                            @click="mostrarModalElegir = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="elegirYGenerarOrden"
                            :disabled="formElegir.processing"
                            class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 font-medium"
                        >
                            <span v-if="formElegir.processing">Procesando...</span>
                            <span v-else>Confirmar y Generar Orden</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Éxito -->
        <div v-if="mostrarModalExito && ordenGenerada" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden">
                    <!-- Header verde -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-5">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center ring-4 ring-white/30 mr-4">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Orden de Compra Generada</h3>
                                <p class="text-green-100 text-sm">Proceso completado exitosamente</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="px-6 py-5">
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Número OC:</span>
                                    <p class="font-mono font-bold text-indigo-600 text-lg">{{ ordenGenerada.numero_oc }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-500">Total:</span>
                                    <p class="font-bold text-green-600 text-lg">{{ formatCurrency(ordenGenerada.total) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-500">Proveedor:</span>
                                    <p class="font-medium text-gray-900">{{ ordenGenerada.proveedor }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-500">Estado:</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ ordenGenerada.estado }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 mb-4">
                            La orden ha sido enviada al proveedor y está lista para seguimiento.
                        </p>

                        <div class="flex gap-3">
                            <Link
                                :href="route('solicitudes-cotizacion.index')"
                                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-center"
                            >
                                Volver a Solicitudes
                            </Link>
                            <button
                                @click="verOrden"
                                class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium"
                            >
                                Ver Orden de Compra
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
