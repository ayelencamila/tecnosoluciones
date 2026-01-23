<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const notifications = ref([]);
const showDropdown = ref(false);
const loading = ref(false);

// Contador de notificaciones no leídas
const unreadCount = computed(() => {
    return notifications.value.filter(n => !n.read_at).length;
});

// Cargar notificaciones
const loadNotifications = async () => {
    try {
        loading.value = true;
        const response = await window.axios.get('/api/notifications');
        notifications.value = response.data;
    } catch (error) {
        console.error('Error cargando notificaciones:', error);
    } finally {
        loading.value = false;
    }
};

// Marcar como leída
const markAsRead = async (notificationId) => {
    try {
        await window.axios.post(`/api/notifications/${notificationId}/read`);
        const notification = notifications.value.find(n => n.id === notificationId);
        if (notification) {
            notification.read_at = new Date().toISOString();
        }
    } catch (error) {
        console.error('Error marcando notificación:', error);
    }
};

// Ir a la notificación
const goToNotification = (notification) => {
    markAsRead(notification.id);
    if (notification.data.url) {
        router.visit(notification.data.url);
    }
    showDropdown.value = false;
};

// Marcar todas como leídas
const markAllAsRead = async () => {
    try {
        await window.axios.post('/api/notifications/mark-all-read');
        notifications.value.forEach(n => {
            n.read_at = new Date().toISOString();
        });
    } catch (error) {
        console.error('Error marcando todas como leídas:', error);
    }
};

// Toggle dropdown
const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value;
    if (showDropdown.value && notifications.value.length === 0) {
        loadNotifications();
    }
};

// Cargar al montar
onMounted(() => {
    loadNotifications();
    
    // Recargar cada 30 segundos
    setInterval(loadNotifications, 30000);
});
</script>

<template>
    <div class="relative">
        <!-- Bell Icon Button -->
        <button
            @click="toggleDropdown"
            class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors duration-150"
            :class="{ 'bg-gray-100': showDropdown }"
        >
            <!-- Bell Icon -->
            <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>

            <!-- Badge con contador -->
            <span
                v-if="unreadCount > 0"
                class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown de Notificaciones -->
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="showDropdown"
                class="absolute right-0 z-50 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden"
            >
                <!-- Header -->
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-sm font-semibold text-gray-900">Notificaciones</h3>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                    >
                        Marcar todas como leídas
                    </button>
                </div>

                <!-- Lista de Notificaciones -->
                <div class="max-h-96 overflow-y-auto">
                    <div v-if="loading" class="p-8 text-center text-gray-500">
                        <svg class="animate-spin h-8 w-8 mx-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div v-else-if="notifications.length === 0" class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <p class="text-sm">No hay notificaciones</p>
                    </div>

                    <div v-else>
                        <button
                            v-for="notification in notifications"
                            :key="notification.id"
                            @click="goToNotification(notification)"
                            class="w-full px-4 py-3 hover:bg-gray-50 transition-colors duration-150 text-left border-b border-gray-100 last:border-b-0"
                            :class="{ 'bg-indigo-50': !notification.read_at }"
                        >
                            <div class="flex items-start">
                                <!-- Icono -->
                                <div class="flex-shrink-0 text-2xl mr-3">
                                    {{ notification.data.icono }}
                                </div>

                                <!-- Contenido -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ notification.data.titulo }}
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ notification.data.mensaje }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-2">
                                        Cliente: {{ notification.data.cliente_nombre }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ notification.data.fecha }}
                                    </p>
                                </div>

                                <!-- Indicador no leído -->
                                <div v-if="!notification.read_at" class="flex-shrink-0 ml-2">
                                    <span class="inline-block w-2 h-2 bg-indigo-600 rounded-full"></span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div v-if="notifications.length > 0" class="px-4 py-3 bg-gray-50 border-t border-gray-200 text-center">
                    <a href="/auditorias" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        Ver todas las notificaciones
                    </a>
                </div>
            </div>
        </transition>
    </div>
</template>
