<script setup>
/**
 * Vista: Error en portal (CU-20)
 */
defineProps({
    titulo: String,
    mensaje: String,
})
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-red-50 to-rose-100 flex items-center justify-center px-4">
        <div class="max-w-lg w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <!-- Icono de error -->
                <div class="mx-auto w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    {{ titulo }}
                </h1>
                
                <p class="text-gray-600 mb-6">
                    {{ mensaje }}
                </p>

                <p class="text-sm text-gray-500">
                    Si cree que esto es un error, por favor contacte a TecnoSoluciones.
                </p>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <p class="text-xs text-gray-400">TecnoSoluciones - Sistema de Compras</p>
                </div>
            </div>
        </div>
    </div>
</template>
