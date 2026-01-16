<script setup>
/**
 * Vista: Agradecimiento por rechazo (CU-20)
 */
defineProps({
    proveedor: String,
})
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-slate-100 flex items-center justify-center px-4">
        <div class="max-w-lg w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <!-- Icono -->
                <div class="mx-auto w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    Gracias por su respuesta
                </h1>
                
                <p class="text-gray-600 mb-6">
                    Estimado/a <strong>{{ proveedor }}</strong>, lamentamos que no pueda participar en esta oportunidad.
                </p>

                <p class="text-sm text-gray-500">
                    Hemos registrado su respuesta. Esperamos poder contar con su participación en futuras solicitudes.
                </p>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <p class="text-xs text-gray-400">TecnoSoluciones - Sistema de Compras</p>
                </div>
            </div>
        </div>
    </div>
</template>
