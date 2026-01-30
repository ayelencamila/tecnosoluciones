<script setup>
/**
 * Vista: Solicitud Vencida - Portal de Proveedores (CU-20)
 * Se muestra cuando un proveedor intenta acceder a una solicitud que ya expiró.
 */
defineProps({
    solicitud: String,
    fechaVencimiento: String,
})
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-slate-100 flex items-center justify-center px-4">
        <div class="max-w-lg w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <!-- Icono de tiempo expirado -->
                <div class="mx-auto w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    Solicitud Vencida
                </h1>
                
                <p class="text-gray-600 mb-6">
                    El plazo para cotizar esta solicitud ha finalizado.
                </p>

                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Solicitud</p>
                            <p class="font-semibold text-gray-900">{{ solicitud }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Fecha de vencimiento</p>
                            <p class="font-semibold text-gray-900">{{ fechaVencimiento }}</p>
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-500">
                    Agradecemos su interés. Esperamos poder contar con su participación en futuras solicitudes de cotización.
                </p>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <p class="text-xs text-gray-400">TecnoSoluciones - Sistema de Compras</p>
                </div>
            </div>
        </div>
    </div>
</template>
