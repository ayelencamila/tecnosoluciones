<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';

const showingSidebar = ref(false);
// Estado para el menú desplegable de Maestros
const showingMaestros = ref(false);

const page = usePage(); 

// --- 1. MENÚ PRINCIPAL (OPERATIVO) ---
const mainNavItems = [
    { 
        name: 'Dashboard', 
        route: 'dashboard', 
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' 
    },
    { 
        name: 'Ventas', 
        route: 'ventas.index', 
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' 
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
        name: 'Reparaciones', 
        route: 'reparaciones.index', 
        icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' 
    },
    { 
        name: 'Clientes', 
        route: 'clientes.index', 
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' 
    },
    { 
        name: 'Pagos', 
        route: 'pagos.index', 
        icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' 
    },
    { 
        name: 'Descuentos', 
        route: 'descuentos.index', 
        icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'
    },
    { 
        name: 'Auditoría', 
        route: 'auditorias.index', 
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'
    },
];

// --- 2. MENÚ MAESTROS (SOLO ADMIN) ---
const maestrosItems = computed(() => {
    if (page.props.auth.user.role !== 'admin') return [];

    return [
        { name: 'Categorías Prod.', route: 'admin.categorias.index' },
        { name: 'Estados Prod.', route: 'admin.estados-producto.index' },
        { name: 'Marcas', route: 'admin.marcas.index' },
        { name: 'Modelos', route: 'admin.modelos.index' },
        { name: 'Unidades Medida', route: 'admin.unidades-medida.index' },
        { name: 'Proveedores', route: 'admin.proveedores.index' },
        { name: 'Depósitos', route: 'admin.depositos.index' },
        { name: 'Medios de Pago', route: 'admin.medios-pago.index' },
        { name: 'Estados Reparación', route: 'admin.estados-reparacion.index' },
        { name: 'Motivos de Demora', route: 'admin.motivos-demora.index' },
        { name: 'Provincias', route: 'admin.provincias.index' },
        { name: 'Localidades', route: 'admin.localidades.index' },
        { name: 'Tipos de Cliente', route: 'admin.tipos-cliente.index' },
        { name: 'Estados de Cliente', route: 'admin.estados-cliente.index' },
    ];
});

// Verifica si alguna ruta del submenú está activa para dejarlo abierto
const isMaestrosActive = computed(() => {
    return maestrosItems.value.some(item => route().current(item.route));
});

// Si hay una ruta activa adentro, abrimos el menú por defecto
if (isMaestrosActive.value) {
    showingMaestros.value = true;
}

const isActive = (routeName) => {
    return route().current(routeName) || route().current(routeName.replace('.index', '*'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex">
        
        <aside 
            class="bg-white w-64 min-h-screen flex flex-col border-r border-gray-200 fixed md:relative z-30 transition-transform duration-300 ease-in-out"
            :class="showingSidebar ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        >
            <div class="h-16 flex items-center justify-center border-b border-gray-200">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <div class="bg-indigo-600 p-1.5 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <span class="text-xl font-bold text-gray-800 tracking-tight">TecnoSoluciones</span>
                </Link>
            </div>

            <nav class="flex-1 py-6 space-y-1 px-3 overflow-y-auto custom-scrollbar">
                
                <Link 
                    v-for="item in mainNavItems" 
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

                <div v-if="$page.props.auth.user.role === 'admin'" class="pt-4 pb-2">
                    <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Administración
                    </p>
                </div>

                <div v-if="$page.props.auth.user.role === 'admin'">
                    <button 
                        @click="showingMaestros = !showingMaestros"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 focus:outline-none transition-colors"
                        :class="{ 'bg-gray-50': showingMaestros || isMaestrosActive }"
                    >
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Gestión de Maestros
                        </div>
                        <svg 
                            class="w-4 h-4 text-gray-400 transform transition-transform duration-200" 
                            :class="{ 'rotate-180': showingMaestros }"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-show="showingMaestros" class="mt-1 space-y-1 pl-11">
                        <Link 
                            v-for="subitem in maestrosItems" 
                            :key="subitem.name"
                            :href="route(subitem.route)"
                            class="block px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="isActive(subitem.route)
                                ? 'text-indigo-700 bg-indigo-50'
                                : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'"
                        >
                            {{ subitem.name }}
                        </Link>
                    </div>
                </div>

                <div class="pt-4 mt-4 border-t border-gray-200">
                    <Link 
                        :href="route('configuracion.index')"
                        class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                        :class="isActive('configuracion.index') 
                            ? 'bg-indigo-50 text-indigo-700' 
                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        <svg class="w-5 h-5 mr-3 transition-colors" 
                             :class="isActive('configuracion.index') ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Configuración Global
                    </Link>
                </div>

            </nav>

            <div class="p-4 border-t border-gray-200">
                <p class="text-xs text-center text-gray-400">v1.0.0 - TecnoSoluciones</p>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex justify-between items-center px-4 sm:px-6 lg:px-8 z-20 relative">
                <button @click="showingSidebar = !showingSidebar" class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>

                <div class="flex-1 min-w-0">
                     <div v-if="$slots.header" class="text-lg font-bold text-gray-900 sm:truncate">
                        <slot name="header" />
                     </div>
                </div>

                <div class="ml-4 flex items-center gap-4">
                    <!-- Campanita de Notificaciones -->
                    <NotificationBell v-if="$page.props.auth.user.role === 'admin'" />
                    
                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="flex items-center max-w-xs bg-white rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 hover:bg-gray-50 p-1 pr-2">
                                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold border-2 border-white shadow-sm">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="ml-2 text-left hidden md:block">
                                        <div class="text-sm font-semibold text-gray-800 leading-none">
                                            {{ $page.props.auth.user.name }}
                                        </div>
                                        <div class="text-xs text-gray-500 font-medium mt-0.5 capitalize">
                                            {{ $page.props.auth.user.role || 'Administrador' }}
                                        </div>
                                    </div>
                                    <svg class="ml-2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <span class="text-xs text-gray-400">Gestionar Cuenta</span>
                                </div>
                                <DropdownLink :href="route('profile.edit')">Mi Perfil</DropdownLink>
                                <div class="border-t border-gray-100"></div>
                                <DropdownLink :href="route('logout')" method="post" as="button">Cerrar Sesión</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
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