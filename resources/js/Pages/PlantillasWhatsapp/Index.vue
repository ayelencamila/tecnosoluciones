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
                Plantillas WhatsApp
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
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
                            <Link :href="route('configuracion.index')" class="text-gray-500 hover:text-gray-700 transition-colors">
                                Configuración
                            </Link>
                        </li>
                        <li class="text-gray-300">/</li>
                        <li class="text-gray-900 font-medium">Plantillas WhatsApp</li>
                    </ol>
                </nav>

                <!-- Header con icono WhatsApp -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Gestión de Plantillas de Mensajes</h3>
                            <p class="text-sm text-gray-500">Configure los mensajes automáticos para diferentes eventos del sistema</p>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-emerald-800">
                                Cada plantilla puede usar <strong>variables dinámicas</strong> que se reemplazan automáticamente al enviar. 
                                Configure también los <strong>horarios de envío</strong> permitidos para cada tipo de mensaje.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Grid de Plantillas -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div v-for="plantilla in plantillas" :key="plantilla.plantilla_id" 
                         class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-200">
                        
                        <!-- Header -->
                        <div class="px-5 py-4 border-b border-gray-100">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-semibold text-gray-900">{{ plantilla.nombre }}</h3>
                                        <span v-if="plantilla.activo" 
                                              class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">
                                            Activa
                                        </span>
                                        <span v-else 
                                              class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                                            Inactiva
                                        </span>
                                    </div>
                                    <code class="text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-600 font-mono">
                                        {{ plantilla.tipo_evento }}
                                    </code>
                                </div>
                                <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Contenido Preview -->
                        <div class="px-5 py-4">
                            <div class="mb-4">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Vista Previa</p>
                                <div class="bg-gray-50 rounded-lg p-3 max-h-40 overflow-y-auto border border-gray-100">
                                    <pre class="text-sm text-gray-700 whitespace-pre-wrap font-sans leading-relaxed">{{ plantilla.contenido_preview || 'Sin contenido' }}</pre>
                                </div>
                            </div>

                            <!-- Info Row -->
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center text-gray-500">
                                        <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="font-medium">{{ plantilla.horario_inicio }} - {{ plantilla.horario_fin }}</span>
                                    </div>
                                    <div class="flex items-center text-gray-500">
                                        <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        <span>{{ plantilla.cantidad_variables }} variables</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-5 py-3 bg-gray-50 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <div class="text-xs text-gray-500">
                                    <span class="text-gray-400">Modificado:</span> {{ plantilla.updated_at }}
                                </div>
                                <div class="flex items-center gap-2">
                                    <Link :href="`/plantillas-whatsapp/${plantilla.plantilla_id}/historial`"
                                          class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Historial
                                    </Link>
                                    <Link :href="`/plantillas-whatsapp/${plantilla.plantilla_id}/edit`"
                                          class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Editar
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!plantillas || plantillas.length === 0" 
                     class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No hay plantillas configuradas</h3>
                    <p class="text-sm text-gray-500">Las plantillas de WhatsApp se crean automáticamente al ejecutar los seeders.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
