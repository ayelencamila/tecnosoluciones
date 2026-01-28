<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    usuario: Object,
    historial: Array,
});

const formatDate = (date) => {
    return new Date(date).toLocaleString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getAccionColor = (accion) => {
    const colores = {
        'CREAR_USUARIO': 'bg-green-100 text-green-800',
        'MODIFICAR_USUARIO': 'bg-blue-100 text-blue-800',
        'ACTIVAR_USUARIO': 'bg-green-100 text-green-800',
        'DESACTIVAR_USUARIO': 'bg-gray-100 text-gray-800',
        'BLOQUEAR_USUARIO': 'bg-red-100 text-red-800',
        'DESBLOQUEAR_USUARIO': 'bg-green-100 text-green-800',
        'BLOQUEO_AUTOMATICO': 'bg-orange-100 text-orange-800',
        'RESTABLECER_PASSWORD': 'bg-yellow-100 text-yellow-800',
        'LOGIN': 'bg-indigo-100 text-indigo-800',
        'LOGOUT': 'bg-purple-100 text-purple-800',
        'ACCESO_DENEGADO': 'bg-red-100 text-red-800',
    };
    return colores[accion] || 'bg-gray-100 text-gray-800';
};

const getAccionTexto = (accion) => {
    const textos = {
        'CREAR_USUARIO': 'Usuario creado',
        'MODIFICAR_USUARIO': 'Datos modificados',
        'ACTIVAR_USUARIO': 'Usuario activado',
        'DESACTIVAR_USUARIO': 'Usuario desactivado',
        'BLOQUEAR_USUARIO': 'Usuario bloqueado',
        'DESBLOQUEAR_USUARIO': 'Usuario desbloqueado',
        'BLOQUEO_AUTOMATICO': 'Bloqueo automático',
        'RESTABLECER_PASSWORD': 'Contraseña restablecida',
        'LOGIN': 'Inicio de sesión',
        'LOGOUT': 'Cierre de sesión',
        'ACCESO_DENEGADO': 'Acceso denegado',
    };
    return textos[accion] || accion;
};

const getEstadoClass = (usuario) => {
    if (!usuario.activo) return 'bg-gray-100 text-gray-600';
    const estaBloqueado = usuario.bloqueado_hasta && new Date(usuario.bloqueado_hasta) > new Date();
    if (estaBloqueado) return 'bg-red-100 text-red-700';
    return 'bg-green-100 text-green-700';
};

const getEstadoTexto = (usuario) => {
    if (!usuario.activo) return 'Inactivo';
    const estaBloqueado = usuario.bloqueado_hasta && new Date(usuario.bloqueado_hasta) > new Date();
    if (estaBloqueado) return 'Bloqueado';
    return 'Activo';
};
</script>

<template>
    <Head :title="`Usuario: ${usuario.name}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('admin.usuarios.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalle del Usuario
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Info del usuario -->
                <div class="bg-white shadow-md rounded-lg p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="h-20 w-20 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold">
                                {{ usuario.name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="ml-6">
                                <h3 class="text-2xl font-bold text-gray-900">{{ usuario.name }}</h3>
                                <p class="text-gray-600">{{ usuario.email }}</p>
                                <div class="flex items-center space-x-3 mt-2">
                                    <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800 capitalize">
                                        {{ usuario.rol?.nombre || 'Sin rol' }}
                                    </span>
                                    <span :class="['px-2 py-1 text-xs rounded-full', getEstadoClass(usuario)]">
                                        {{ getEstadoTexto(usuario) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <Link :href="route('admin.usuarios.edit', usuario.id)">
                            <SecondaryButton>Editar</SecondaryButton>
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t">
                        <div>
                            <p class="text-sm text-gray-500">Teléfono</p>
                            <p class="font-medium">{{ usuario.telefono || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Rol</p>
                            <p class="font-medium capitalize">{{ usuario.rol?.nombre || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Fecha de registro</p>
                            <p class="font-medium">{{ formatDate(usuario.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Última actualización</p>
                            <p class="font-medium">{{ formatDate(usuario.updated_at) }}</p>
                        </div>
                    </div>

                    <!-- Bloqueo info -->
                    <div v-if="usuario.bloqueado_hasta && new Date(usuario.bloqueado_hasta) > new Date()" 
                        class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm text-red-700">
                            <strong>Usuario bloqueado hasta:</strong> {{ formatDate(usuario.bloqueado_hasta) }}
                        </p>
                    </div>
                </div>

                <!-- Historial de operaciones -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Historial de Operaciones</h3>
                        <p class="text-sm text-gray-500">Registro de actividad relacionada con este usuario</p>
                    </div>

                    <div v-if="historial.length > 0" class="divide-y divide-gray-200">
                        <div v-for="evento in historial" :key="evento.auditoriaID" class="px-6 py-4 hover:bg-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-3">
                                    <span :class="['px-2 py-1 text-xs rounded-full', getAccionColor(evento.accion)]">
                                        {{ getAccionTexto(evento.accion) }}
                                    </span>
                                    <div>
                                        <p class="text-sm text-gray-900">{{ evento.motivo || evento.detalles }}</p>
                                        <p class="text-xs text-gray-500">
                                            Por: {{ evento.usuario?.name || 'Sistema' }}
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-400">{{ formatDate(evento.created_at) }}</span>
                            </div>

                            <!-- Detalles del cambio -->
                            <div v-if="evento.datos_anteriores || evento.datos_nuevos" class="mt-2 ml-24 text-xs">
                                <div v-if="evento.datos_anteriores" class="text-gray-500">
                                    <span class="font-medium">Antes:</span> 
                                    {{ JSON.stringify(evento.datos_anteriores) }}
                                </div>
                                <div v-if="evento.datos_nuevos" class="text-gray-600">
                                    <span class="font-medium">Después:</span> 
                                    {{ JSON.stringify(evento.datos_nuevos) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="px-6 py-8 text-center text-gray-500">
                        No hay registros de actividad para este usuario.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
