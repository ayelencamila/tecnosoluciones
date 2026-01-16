<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    plantilla: Object,
    historial: Array,
});
</script>

<template>
    <AppLayout :title="`Historial: ${plantilla.nombre}`">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Historial de Cambios
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Breadcrumb -->
                <nav class="flex items-center mb-6">
                    <ol class="flex items-center space-x-2 text-sm">
                        <li>
                            <Link :href="route('dashboard')" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                </svg>
                            </Link>
                        </li>
                        <li class="text-gray-300">/</li>
                        <li>
                            <Link href="/plantillas-whatsapp" class="text-gray-500 hover:text-gray-700 transition-colors">
                                Plantillas WhatsApp
                            </Link>
                        </li>
                        <li class="text-gray-300">/</li>
                        <li>
                            <Link :href="`/plantillas-whatsapp/${plantilla.plantilla_id}/editar`" class="text-gray-500 hover:text-gray-700 transition-colors">
                                {{ plantilla.nombre }}
                            </Link>
                        </li>
                        <li class="text-gray-300">/</li>
                        <li class="text-gray-900 font-medium">Historial</li>
                    </ol>
                </nav>

                <!-- Header Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-indigo-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Historial de Cambios</h3>
                            <p class="text-sm text-gray-500 mt-0.5">
                                Registro de modificaciones para <strong>{{ plantilla.nombre }}</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div v-if="historial.length > 0" class="space-y-4">
                    <div
                        v-for="(item, index) in historial"
                        :key="index"
                        class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        
                        <div class="p-5">
                            <div class="flex items-start gap-4">
                                <!-- Icono según acción -->
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Contenido -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                {{ item.accion }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ item.fecha }}
                                        </div>
                                    </div>

                                    <p class="mt-2 text-sm text-gray-600">
                                        <span class="font-medium text-gray-900">{{ item.usuario }}</span>
                                        realizó cambios en la plantilla.
                                    </p>

                                    <div v-if="item.motivo" class="mt-3 p-3 bg-gray-50 rounded-lg">
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Motivo</p>
                                        <p class="text-sm text-gray-700">{{ item.motivo }}</p>
                                    </div>

                                    <div v-if="item.detalles" class="mt-2">
                                        <p class="text-xs text-gray-500">{{ item.detalles }}</p>
                                    </div>

                                    <!-- Cambios detallados (opcional, mostrar con toggle) -->
                                    <details v-if="item.datos_anteriores || item.datos_nuevos" class="mt-3">
                                        <summary class="cursor-pointer text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                            Ver cambios detallados
                                        </summary>
                                        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div v-if="item.datos_anteriores" class="p-3 bg-rose-50 rounded-lg border border-rose-100">
                                                <p class="text-xs font-medium text-rose-600 uppercase tracking-wide mb-2">Antes</p>
                                                <pre class="text-xs text-gray-700 overflow-x-auto whitespace-pre-wrap">{{ JSON.stringify(item.datos_anteriores, null, 2) }}</pre>
                                            </div>
                                            <div v-if="item.datos_nuevos" class="p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                                                <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide mb-2">Después</p>
                                                <pre class="text-xs text-gray-700 overflow-x-auto whitespace-pre-wrap">{{ JSON.stringify(item.datos_nuevos, null, 2) }}</pre>
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Sin historial registrado</h3>
                    <p class="text-gray-500 max-w-sm mx-auto">
                        Esta plantilla no tiene modificaciones registradas en el sistema de auditoría.
                    </p>
                </div>

                <!-- Botón volver -->
                <div class="mt-6">
                    <Link 
                        href="/plantillas-whatsapp"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al listado
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
