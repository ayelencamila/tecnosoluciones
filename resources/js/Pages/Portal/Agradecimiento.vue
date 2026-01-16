<script setup>
/**
 * Vista: Agradecimiento después de cotizar (CU-20)
 */
defineProps({
    proveedor: String,
    fechaRespuesta: String,
    solicitud: String,
})
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-100 flex items-center justify-center px-4">
        <div class="max-w-lg w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <!-- Icono de éxito -->
                <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    ¡Gracias por su cotización!
                </h1>
                
                <p class="text-gray-600 mb-6">
                    Estimado/a <strong>{{ proveedor }}</strong>, hemos recibido su cotización exitosamente.
                </p>

                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Solicitud</p>
                            <p class="font-semibold text-gray-900">{{ solicitud }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Fecha de respuesta</p>
                            <p class="font-semibold text-gray-900">{{ fechaRespuesta }}</p>
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-500">
                    Evaluaremos su propuesta y nos pondremos en contacto en caso de ser seleccionado.
                </p>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <p class="text-xs text-gray-400">TecnoSoluciones - Sistema de Compras</p>
                </div>
            </div>
        </div>
    </div>
</template>
