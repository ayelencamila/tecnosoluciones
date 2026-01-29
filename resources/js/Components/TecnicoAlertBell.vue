<script setup>
/**
 * Componente: Campanita de Alertas para Técnicos (CU-14)
 * 
 * Muestra alertas de reparaciones demoradas que requieren 
 * que el técnico ingrese un motivo de demora.
 * 
 * Formato unificado con NotificationBell del administrador.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const alertas = ref([]);
const showDropdown = ref(false);
const loading = ref(false);
const activeFilter = ref('all'); // 'all', 'unread'

// Verificar si el usuario está autenticado
const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

// Contador de alertas no leídas
const unreadCount = computed(() => {
    return alertas.value.filter(a => !a.leida).length;
});

// Alertas urgentes (más de 3 días excedidos)
const urgentCount = computed(() => {
    return alertas.value.filter(a => !a.leida && a.dias_excedidos > 3).length;
});

// Alertas filtradas
const filteredAlertas = computed(() => {
    let filtered = [...alertas.value];
    
    if (activeFilter.value === 'unread') {
        filtered = filtered.filter(a => !a.leida);
    } else if (activeFilter.value === 'urgent') {
        filtered = filtered.filter(a => !a.leida && a.dias_excedidos > 3);
    }
    
    // Ordenar: urgentes primero, luego por días excedidos
    return filtered.sort((a, b) => {
        const aUrgent = a.dias_excedidos > 3 ? 1 : 0;
        const bUrgent = b.dias_excedidos > 3 ? 1 : 0;
        if (aUrgent !== bUrgent) return bUrgent - aUrgent;
        return b.dias_excedidos - a.dias_excedidos;
    });
});

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

// Cargar alertas del técnico
const loadAlertas = async () => {
    if (!isAuthenticated.value) return;
    
    try {
        loading.value = true;
        const response = await window.axios.get('/api/tecnico/alertas');
        alertas.value = response.data;
    } catch (error) {
        console.error('Error cargando alertas:', error);
        alertas.value = [];
    } finally {
        loading.value = false;
    }
};

// Marcar alerta individual como leída
const marcarLeida = async (alerta, event) => {
    event?.stopPropagation();
    try {
        await window.axios.patch(`/api/tecnico/alertas/${alerta.alertaReparacionID}/marcar-leida`);
        alerta.leida = true;
    } catch (error) {
        console.error('Error marcando alerta como leída:', error);
    }
};

// Marcar todas como leídas
const marcarTodasLeidas = async () => {
    try {
        await window.axios.post('/api/tecnico/alertas/marcar-todas-leidas');
        alertas.value.forEach(a => { a.leida = true; });
    } catch (error) {
        console.error('Error marcando todas como leídas:', error);
    }
};

// Ir a responder la alerta (marca como leída y navega)
const goToAlerta = async (alerta) => {
    // Marcar como leída antes de navegar
    if (!alerta.leida) {
        try {
            await window.axios.patch(`/api/tecnico/alertas/${alerta.alertaReparacionID}/marcar-leida`);
        } catch (error) {
            console.error('Error marcando alerta:', error);
        }
    }
    showDropdown.value = false;
    router.visit(`/alertas/${alerta.alertaReparacionID}`);
};

// Toggle dropdown
const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value;
    if (showDropdown.value) {
        loadAlertas();
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

// Cargar al montar y cada 30 segundos
onMounted(() => {
    loadAlertas();
    refreshInterval = setInterval(loadAlertas, 30000);
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
                :class="urgentCount > 0 ? 'bg-red-600' : 'bg-orange-600'"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown de Alertas -->
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
                <div class="px-4 py-3 border-b border-gray-200 bg-orange-50">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Alertas SLA
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ unreadCount }} pendientes de {{ alertas.length }}
                            </p>
                        </div>
                        <button
                            v-if="unreadCount > 0"
                            @click="marcarTodasLeidas"
                            class="text-xs text-gray-600 hover:text-gray-900 font-medium px-2 py-1 hover:bg-orange-100 rounded transition-colors"
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
                        Pendientes
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

                <!-- Lista de Alertas -->
                <div class="max-h-[380px] overflow-y-auto">
                    <div v-if="loading" class="p-8 text-center">
                        <svg class="animate-spin h-5 w-5 mx-auto text-orange-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-xs text-gray-500 mt-2">Cargando...</p>
                    </div>

                    <div v-else-if="filteredAlertas.length === 0" class="p-8 text-center">
                        <svg class="w-10 h-10 mx-auto text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-green-600 mt-2 font-medium">¡Sin alertas pendientes!</p>
                        <p class="text-xs text-gray-400 mt-1">Todas las reparaciones están al día</p>
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <button
                            v-for="alerta in filteredAlertas"
                            :key="alerta.alertaReparacionID"
                            @click="goToAlerta(alerta)"
                            class="w-full px-4 py-3 hover:bg-gray-50 transition-colors text-left group"
                            :class="{ 'bg-orange-50/50': !alerta.leida }"
                        >
                            <div class="flex items-start gap-3">
                                <!-- Icono según urgencia -->
                                <div 
                                    class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                                    :class="alerta.dias_excedidos > 3 ? 'bg-red-100 text-red-600' : 'bg-orange-100 text-orange-600'"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>

                                <!-- Contenido -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ alerta.reparacion?.codigo_reparacion }}
                                        </p>
                                        <span 
                                            v-if="alerta.dias_excedidos > 3"
                                            class="px-1.5 py-0.5 text-[10px] font-semibold bg-red-100 text-red-700 rounded uppercase"
                                        >
                                            Urgente
                                        </span>
                                    </div>
                                    <p class="text-xs text-red-600 font-medium mt-0.5">
                                        +{{ alerta.dias_excedidos }} día{{ alerta.dias_excedidos !== 1 ? 's' : '' }} de demora
                                    </p>
                                    <p class="text-xs text-gray-600 mt-0.5 line-clamp-1">
                                        {{ alerta.reparacion?.cliente?.apellido }} {{ alerta.reparacion?.cliente?.nombre }}
                                    </p>
                                    
                                    <!-- Metadata -->
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span class="text-[11px] text-gray-400">
                                            {{ timeAgo(alerta.created_at) }}
                                        </span>
                                        <span class="text-gray-300">·</span>
                                        <span class="text-[11px] text-gray-500 truncate">
                                            {{ alerta.reparacion?.modelo?.marca?.nombre }} {{ alerta.reparacion?.modelo?.nombre }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Indicador de estado y acciones -->
                                <div class="flex-shrink-0 flex items-center gap-2">
                                    <!-- Botón marcar como leída -->
                                    <button
                                        v-if="!alerta.leida"
                                        @click="marcarLeida(alerta, $event)"
                                        class="p-1 rounded hover:bg-gray-200 opacity-0 group-hover:opacity-100 transition-all"
                                        title="Marcar como leída"
                                    >
                                        <svg class="w-4 h-4 text-gray-400 hover:text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                    <span 
                                        v-if="!alerta.leida" 
                                        class="w-2 h-2 bg-orange-600 rounded-full"
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
                            @click="loadAlertas"
                            class="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1 py-1"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Actualizar
                        </button>
                        <a 
                            href="/alertas" 
                            class="text-xs text-orange-600 hover:text-orange-800 font-medium flex items-center gap-1"
                        >
                            Ver todas las alertas
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
