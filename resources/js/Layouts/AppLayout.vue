<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const showingSidebar = ref(false);
const page = usePage(); // Acceso a los datos del usuario (Roles)

// Usamos 'computed' para que el menú sea dinámico según el rol
const navItems = computed(() => {
    const items = [
        { 
            name: 'Dashboard', 
            route: 'dashboard', 
            icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' 
        },
        { 
            name: 'Clientes', 
            route: 'clientes.index', 
            icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' 
        },
        { 
            name: 'Productos', 
            route: 'productos.index', 
            icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' 
        },
        { 
            name: 'Stock', 
            route: 'productos.stock', 
            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' 
        },
        { 
            name: 'Ventas', 
            route: 'ventas.index', 
            icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z' 
        },
        { 
            name: 'Pagos', 
            route: 'pagos.index', 
            icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' 
        },
        { 
            name: 'Descuentos', 
            route: 'descuentos.index', 
            icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'
        },
        { 
            name: 'Reparaciones', 
            route: 'reparaciones.index', 
            icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' 
        },
        { 
            name: 'Auditoría', 
            route: 'auditorias.index', 
            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'
        },
        { 
            name: 'Configuración', 
            route: 'configuracion.index', 
            icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'
        }
    ];

    // --- LÓGICA PARA EL ADMINISTRADOR ---
    // Verificamos si el usuario tiene el rol 'admin'
    if (page.props.auth.user.role === 'admin') {
        items.push({
            name: 'Categorías (ABM)',
            route: 'admin.categorias.index', 
            icon: 'M4 6h16M4 10h16M4 14h16M4 18h16' // Icono de lista
        });
        
        items.push({
            name: 'Estados Reparación',
            route: 'admin.estados-reparacion.index',
            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
        });

        items.push({
            name: 'Provincias',
            route: 'admin.provincias.index',
            icon: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064' // Icono tipo mapa
        });

        items.push({
            name: 'Localidades',
            route: 'admin.localidades.index',
            icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z' // Icono de marcador de mapa
        });
    }

    return items;
});

// Verifica si la ruta actual coincide con el item del menú
const isActive = (routeName) => {
    return route().current(routeName) || route().current(routeName.replace('.index', '*'));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value);
};
</script>
<template>
    <div class="min-h-screen bg-gray-100 flex">
        
        <!-- SIDEBAR -->
        <aside 
            class="bg-white w-64 min-h-screen flex flex-col border-r border-gray-200 fixed md:relative z-30 transition-transform duration-300 ease-in-out"
            :class="showingSidebar ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        >
            <!-- Logo -->
            <div class="h-16 flex items-center justify-center border-b border-gray-200">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <div class="bg-indigo-600 p-1.5 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <span class="text-xl font-bold text-gray-800 tracking-tight">TecnoSoluciones</span>
                </Link>
            </div>

            <!-- Navegación -->
            <nav class="flex-1 py-6 space-y-1 px-3 overflow-y-auto">
                <Link 
                    v-for="item in navItems" 
                    :key="item.name"
                    :href="route(item.route)"
                    class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                    :class="isActive(item.route) 
                        ? 'bg-indigo-50 text-indigo-700' 
                        : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'"
                >
                    <svg class="w-5 h-5 mr-3 transition-colors" 
                         :class="isActive(item.route) ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500'"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <div class="p-4 border-t border-gray-200">
                <p class="text-xs text-center text-gray-400">v1.0.0 - TecnoSoluciones</p>
            </div>
        </aside>

        <!-- CONTENIDO -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- NAVBAR -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex justify-between items-center px-4 sm:px-6 lg:px-8 z-20 relative">
                
                <!-- Botón Móvil -->
                <button @click="showingSidebar = !showingSidebar" class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>

                <!-- Título -->
                <div class="flex-1 min-w-0">
                     <div v-if="$slots.header" class="text-lg font-bold text-gray-900 sm:truncate">
                        <slot name="header" />
                     </div>
                </div>

                <!-- Perfil y Notificaciones -->
                <div class="ml-4 flex items-center gap-4">
                    
                    <!-- Notificaciones -->
                    <button class="p-2 rounded-full text-gray-400 hover:text-gray-500 hover:bg-gray-100 relative">
                        <span class="absolute top-2 right-2 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </button>

                    <div class="h-6 w-px bg-gray-200"></div>

                    <!-- PERFIL (DISEÑO SOLICITADO) -->
                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="flex items-center max-w-xs bg-white rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 hover:bg-gray-50 p-1 pr-2">
                                    <!-- Avatar con Gradiente -->
                                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold border-2 border-white shadow-sm">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    
                                    <!-- Texto Nombre y Rol -->
                                    <div class="ml-2 text-left hidden md:block">
                                        <div class="text-sm font-semibold text-gray-800 leading-none">
                                            {{ $page.props.auth.user.name }}
                                        </div>
                                        <div class="text-xs text-gray-500 font-medium mt-0.5 capitalize">
                                            {{ $page.props.auth.user.role || 'Administrador' }}
                                        </div>
                                    </div>
                                    
                                    <!-- Flechita -->
                                    <svg class="ml-2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <span class="text-xs text-gray-400">Gestionar Cuenta</span>
                                </div>
                                <DropdownLink :href="route('profile.edit')">
                                    Mi Perfil
                                </DropdownLink>
                                <div class="border-t border-gray-100"></div>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Cerrar Sesión
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
                <!-- Alertas Flash -->
                <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="mb-6 animate-fade-in-down">
                    <div v-if="$page.props.flash?.success" class="rounded-md bg-green-50 p-4 border-l-4 border-green-400 shadow-sm flex">
                        <svg class="h-5 w-5 text-green-400 mr-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <p class="text-sm font-medium text-green-800">{{ $page.props.flash?.success }}</p>
                    </div>
                    <div v-if="$page.props.flash?.error" class="rounded-md bg-red-50 p-4 border-l-4 border-red-400 shadow-sm flex">
                        <svg class="h-5 w-5 text-red-400 mr-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <p class="text-sm font-medium text-red-800">{{ $page.props.flash?.error }}</p>
                    </div>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>