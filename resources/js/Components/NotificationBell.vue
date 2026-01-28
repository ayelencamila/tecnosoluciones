<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const notifications = ref([]);
const showDropdown = ref(false);
const loading = ref(false);
const activeFilter = ref('all'); // 'all', 'unread', 'urgent'

// Tipos de notificación con colores (sin emojis)
const notificationTypes = {
    'venta': { color: 'green', label: 'Venta', iconType: 'currency' },
    'pago': { color: 'blue', label: 'Pago', iconType: 'card' },
    'reparacion': { color: 'orange', label: 'Reparación', iconType: 'wrench' },
    'stock': { color: 'amber', label: 'Stock', iconType: 'cube' },
    'cliente': { color: 'purple', label: 'Cliente', iconType: 'user' },
    'alerta': { color: 'red', label: 'Alerta', iconType: 'alert' },
    'sistema': { color: 'slate', label: 'Sistema', iconType: 'cog' },
    'default': { color: 'indigo', label: 'Notificación', iconType: 'bell' }
};

// Contador de notificaciones no leídas
const unreadCount = computed(() => {
    return notifications.value.filter(n => !n.read_at).length;
});

// Notificaciones urgentes (no leídas + antiguas de más de 1 hora)
const urgentCount = computed(() => {
    const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
    return notifications.value.filter(n => {
        if (n.read_at) return false;
        const createdAt = new Date(n.created_at);
        return createdAt < oneHourAgo || n.data?.urgente;
    }).length;
});

// Notificaciones filtradas
const filteredNotifications = computed(() => {
    let filtered = [...notifications.value];
    
    if (activeFilter.value === 'unread') {
        filtered = filtered.filter(n => !n.read_at);
    } else if (activeFilter.value === 'urgent') {
        const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
        filtered = filtered.filter(n => {
            if (n.read_at) return false;
            const createdAt = new Date(n.created_at);
            return createdAt < oneHourAgo || n.data?.urgente;
        });
    }
    
    // Ordenar: urgentes primero, luego por fecha
    return filtered.sort((a, b) => {
        const aUrgent = a.data?.urgente ? 1 : 0;
        const bUrgent = b.data?.urgente ? 1 : 0;
        if (aUrgent !== bUrgent) return bUrgent - aUrgent;
        return new Date(b.created_at) - new Date(a.created_at);
    });
});

// Notificaciones agrupadas por tipo
const groupedByType = computed(() => {
    const groups = {};
    notifications.value.filter(n => !n.read_at).forEach(n => {
        const type = n.data?.tipo || 'default';
        if (!groups[type]) groups[type] = 0;
        groups[type]++;
    });
    return groups;
});

// Obtener configuración del tipo de notificación
const getTypeConfig = (notification) => {
    const type = notification.data?.tipo || 'default';
    return notificationTypes[type] || notificationTypes.default;
};

// Formatear tiempo relativo
const timeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Ahora';
    if (seconds < 3600) return `${Math.floor(seconds / 60)} min`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)} h`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)} d`;
    return date.toLocaleDateString('es-AR', { day: 'numeric', month: 'short' });
};

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

// Cerrar al hacer clic fuera
const dropdownRef = ref(null);
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showDropdown.value = false;
    }
};

let refreshInterval = null;

// Cargar al montar
onMounted(() => {
    loadNotifications();
    refreshInterval = setInterval(loadNotifications, 30000);
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <!-- Bell Icon Button -->
        <button
            @click.stop="toggleDropdown"
            class="relative p-2 rounded-lg transition-all duration-200"
            :class="[
                showDropdown ? 'bg-gray-200 text-gray-900' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100',
            ]"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>

            <!-- Badge con contador -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-semibold leading-none text-white rounded-full"
                :class="urgentCount > 0 ? 'bg-red-600' : 'bg-gray-700'"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown de Notificaciones -->
        <transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="showDropdown"
                class="absolute right-0 z-50 mt-2 w-[400px] bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden"
            >
                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Notificaciones</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ unreadCount }} sin leer de {{ notifications.length }}
                            </p>
                        </div>
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllAsRead"
                            class="text-xs text-gray-600 hover:text-gray-900 font-medium px-2 py-1 hover:bg-gray-200 rounded transition-colors"
                        >
                            Marcar leídas
                        </button>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="px-4 py-2 bg-white border-b border-gray-100 flex gap-1">
                    <button
                        @click="activeFilter = 'all'"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors"
                        :class="activeFilter === 'all' 
                            ? 'bg-gray-900 text-white' 
                            : 'text-gray-600 hover:bg-gray-100'"
                    >
                        Todas
                    </button>
                    <button
                        @click="activeFilter = 'unread'"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors"
                        :class="activeFilter === 'unread' 
                            ? 'bg-gray-900 text-white' 
                            : 'text-gray-600 hover:bg-gray-100'"
                    >
                        Sin leer
                    </button>
                    <button
                        v-if="urgentCount > 0"
                        @click="activeFilter = 'urgent'"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors"
                        :class="activeFilter === 'urgent' 
                            ? 'bg-red-600 text-white' 
                            : 'text-red-600 hover:bg-red-50'"
                    >
                        Urgentes ({{ urgentCount }})
                    </button>
                </div>

                <!-- Lista de Notificaciones -->
                <div class="max-h-[380px] overflow-y-auto">
                    <div v-if="loading" class="p-8 text-center">
                        <svg class="animate-spin h-5 w-5 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-xs text-gray-500 mt-2">Cargando...</p>
                    </div>

                    <div v-else-if="filteredNotifications.length === 0" class="p-8 text-center">
                        <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-gray-600 mt-2">Sin notificaciones</p>
                        <p class="text-xs text-gray-400 mt-1">Estás al día</p>
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <button
                            v-for="notification in filteredNotifications"
                            :key="notification.id"
                            @click="goToNotification(notification)"
                            class="w-full px-4 py-3 hover:bg-gray-50 transition-colors text-left group"
                            :class="{ 'bg-blue-50/50': !notification.read_at }"
                        >
                            <div class="flex items-start gap-3">
                                <!-- Icono SVG según tipo -->
                                <div 
                                    class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                                    :class="{
                                        'bg-green-100 text-green-600': getTypeConfig(notification).color === 'green',
                                        'bg-blue-100 text-blue-600': getTypeConfig(notification).color === 'blue',
                                        'bg-orange-100 text-orange-600': getTypeConfig(notification).color === 'orange',
                                        'bg-amber-100 text-amber-600': getTypeConfig(notification).color === 'amber',
                                        'bg-purple-100 text-purple-600': getTypeConfig(notification).color === 'purple',
                                        'bg-red-100 text-red-600': getTypeConfig(notification).color === 'red',
                                        'bg-slate-100 text-slate-600': getTypeConfig(notification).color === 'slate',
                                        'bg-indigo-100 text-indigo-600': getTypeConfig(notification).color === 'indigo',
                                    }"
                                >
                                    <!-- Icono Venta/Dinero -->
                                    <svg v-if="getTypeConfig(notification).iconType === 'currency'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <!-- Icono Pago/Tarjeta -->
                                    <svg v-else-if="getTypeConfig(notification).iconType === 'card'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    <!-- Icono Reparación -->
                                    <svg v-else-if="getTypeConfig(notification).iconType === 'wrench'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <!-- Icono Stock/Cubo -->
                                    <svg v-else-if="getTypeConfig(notification).iconType === 'cube'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <!-- Icono Cliente/Usuario -->
                                    <svg v-else-if="getTypeConfig(notification).iconType === 'user'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <!-- Icono Alerta -->
                                    <svg v-else-if="getTypeConfig(notification).iconType === 'alert'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <!-- Icono Sistema/Config -->
                                    <svg v-else-if="getTypeConfig(notification).iconType === 'cog'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    </svg>
                                    <!-- Icono Default/Campana -->
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                </div>

                                <!-- Contenido -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ notification.data.titulo }}
                                        </p>
                                        <span 
                                            v-if="notification.data?.urgente"
                                            class="px-1.5 py-0.5 text-[10px] font-semibold bg-red-100 text-red-700 rounded uppercase"
                                        >
                                            Urgente
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-0.5 line-clamp-2">
                                        {{ notification.data.mensaje }}
                                    </p>
                                    
                                    <!-- Metadata -->
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span class="text-[11px] text-gray-400">
                                            {{ timeAgo(notification.created_at) }}
                                        </span>
                                        <span class="text-gray-300">·</span>
                                        <span class="text-[11px] text-gray-500">
                                            {{ getTypeConfig(notification).label }}
                                        </span>
                                        <template v-if="notification.data.cliente_nombre">
                                            <span class="text-gray-300">·</span>
                                            <span class="text-[11px] text-gray-400 truncate">
                                                {{ notification.data.cliente_nombre }}
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Indicador de estado -->
                                <div class="flex-shrink-0 flex items-center gap-2">
                                    <span 
                                        v-if="!notification.read_at" 
                                        class="w-2 h-2 bg-blue-600 rounded-full"
                                    ></span>
                                    <svg 
                                        class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors" 
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <button 
                            @click="loadNotifications"
                            class="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1 py-1"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Actualizar
                        </button>
                        <a 
                            href="/auditorias" 
                            class="text-xs text-gray-600 hover:text-gray-900 font-medium flex items-center gap-1"
                        >
                            Ver historial
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
