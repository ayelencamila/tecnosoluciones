<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    plantillas: Array,
});
</script>

<template>
    <AppLayout title="Plantillas WhatsApp">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Plantillas WhatsApp (CU-30)
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Descripción -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Configure las plantillas de mensajes WhatsApp para diferentes eventos del sistema. 
                                Cada plantilla puede usar variables dinámicas y tiene horarios configurables de envío.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Listado de plantillas en formato de tarjetas -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div v-for="plantilla in plantillas" :key="plantilla.plantilla_id" 
                         class="bg-white shadow-lg rounded-lg overflow-hidden hover:shadow-xl transition-shadow duration-300 border border-gray-200">
                        
                        <!-- Header de la tarjeta -->
                        <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-green-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                        {{ plantilla.nombre }}
                                    </h3>
                                    <div class="flex items-center space-x-2">
                                        <code class="text-xs bg-white px-2 py-1 rounded border border-gray-300 text-gray-700">
                                            {{ plantilla.tipo_evento }}
                                        </code>
                                        <span v-if="plantilla.activo" 
                                              class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            🟢 Activa
                                        </span>
                                        <span v-else 
                                              class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            🔴 Inactiva
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contenido de la plantilla -->
                        <div class="px-6 py-4">
                            <div class="mb-4">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                    Vista Previa del Mensaje:
                                </h4>
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 max-h-64 overflow-y-auto">
                                    <pre class="text-sm text-gray-700 whitespace-pre-wrap font-sans leading-relaxed">{{ plantilla.contenido_preview || 'Sin contenido' }}</pre>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <p class="text-xs text-gray-500">Horario</p>
                                        <p class="font-medium">{{ plantilla.horario_inicio }} - {{ plantilla.horario_fin }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                    </svg>
                                    <div>
                                        <p class="text-xs text-gray-500">Variables</p>
                                        <p class="font-medium">{{ plantilla.cantidad_variables }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer: Usuario y fecha -->
                            <div class="flex items-center justify-between text-xs text-gray-500 pt-3 border-t border-gray-200">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ plantilla.usuario_modificacion }}
                                </div>
                                <div>{{ plantilla.updated_at }}</div>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                            <Link :href="`/plantillas-whatsapp/${plantilla.plantilla_id}/historial`"
                                  class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Historial
                            </Link>
                            <Link :href="`/plantillas-whatsapp/${plantilla.plantilla_id}/edit`"
                                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Editar Plantilla
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
