<script setup>
/**
 * Componente: Campanita de Alertas para Técnicos (CU-14)
 * 
 * Muestra alertas de reparaciones demoradas que requieren 
 * que el técnico ingrese un motivo de demora.
 */
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const alertas = ref([]);
const showDropdown = ref(false);
const loading = ref(false);

// Contador de alertas no leídas
const unreadCount = computed(() => {
    return alertas.value.filter(a => !a.leida).length;
});

// Cargar alertas del técnico
const loadAlertas = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/tecnico/alertas');
        alertas.value = response.data;
    } catch (error) {
        console.error('Error cargando alertas:', error);
    } finally {
        loading.value = false;
    }
};

// Ir a responder la alerta
const goToAlerta = (alerta) => {
    router.visit(`/alertas/${alerta.alertaReparacionID}`);
    showDropdown.value = false;
};

// Toggle dropdown
const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value;
    if (showDropdown.value) {
        loadAlertas();
    }
};

// Cerrar dropdown al hacer clic fuera
const closeDropdown = () => {
    showDropdown.value = false;
};

// Cargar al montar y cada 30 segundos
onMounted(() => {
    loadAlertas();
    setInterval(loadAlertas, 30000);
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
                class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full animate-pulse"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown de Alertas -->
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
                <div class="px-4 py-3 bg-orange-50 border-b border-orange-200">
                    <h3 class="text-sm font-semibold text-orange-900 flex items-center gap-2">
                        <span>⚠️</span>
                        Alertas de Reparaciones Demoradas
                    </h3>
                    <p class="text-xs text-orange-700 mt-1">Requieren ingresar motivo de demora</p>
                </div>

                <!-- Lista de Alertas -->
                <div class="max-h-96 overflow-y-auto">
                    <div v-if="loading" class="p-8 text-center text-gray-500">
                        <svg class="animate-spin h-8 w-8 mx-auto text-orange-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div v-else-if="alertas.length === 0" class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-green-600 font-medium">¡Sin alertas pendientes!</p>
                        <p class="text-xs text-gray-400 mt-1">Todas las reparaciones están al día</p>
                    </div>

                    <div v-else>
                        <button
                            v-for="alerta in alertas"
                            :key="alerta.alertaReparacionID"
                            @click="goToAlerta(alerta)"
                            class="w-full px-4 py-3 hover:bg-orange-50 transition-colors duration-150 text-left border-b border-gray-100 last:border-b-0"
                            :class="{ 'bg-orange-50': !alerta.leida }"
                        >
                            <div class="flex items-start">
                                <!-- Icono de alerta -->
                                <div class="flex-shrink-0 text-2xl mr-3">
                                    🔔
                                </div>

                                <!-- Contenido -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ alerta.reparacion?.codigo_reparacion }}
                                    </p>
                                    <p class="text-sm text-red-600 mt-1 font-medium">
                                        ⏰ {{ alerta.dias_excedidos }} día(s) de demora
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Cliente: {{ alerta.reparacion?.cliente?.apellido }} {{ alerta.reparacion?.cliente?.nombre }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Equipo: {{ alerta.reparacion?.modelo?.marca?.nombre }} {{ alerta.reparacion?.modelo?.nombre }}
                                    </p>
                                </div>

                                <!-- Indicador no leído -->
                                <div v-if="!alerta.leida" class="flex-shrink-0 ml-2">
                                    <span class="inline-block w-2 h-2 bg-orange-600 rounded-full animate-pulse"></span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 text-center">
                    <a href="/alertas" class="text-sm text-orange-600 hover:text-orange-800 font-medium">
                        Ver todas las alertas
                    </a>
                </div>
            </div>
        </transition>
    </div>
</template>
