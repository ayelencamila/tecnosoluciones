<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

// Estado para el menú hamburguesa en móviles
const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <Link :href="route('dashboard')">
                                <span class="text-2xl font-bold text-indigo-600 tracking-tight">TecnoSoluciones</span>
                            </Link>
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </NavLink>
                            <NavLink :href="route('clientes.index')" :active="route().current('clientes.*')">
                                Clientes
                            </NavLink>
                            
                            <NavLink :href="route('productos.index')" :active="route().current('productos.*') && !route().current('productos.stock')">
                                Productos
                            </NavLink>

                            <NavLink :href="route('productos.stock')" :active="route().current('productos.stock')">
                                Stock
                            </NavLink>

                            <NavLink :href="route('ventas.index')" :active="route().current('ventas.*')">
                                Ventas
                            </NavLink>
                            <NavLink :href="route('pagos.index')" :active="route().current('pagos.*')">
                                Pagos
                            </NavLink>
                            <NavLink href="/descuentos" :active="$page.component.startsWith('Descuentos')">
                                Descuentos
                            </NavLink>
                             <NavLink href="/auditorias" :active="$page.component.startsWith('Auditorias')">
                                Auditoría
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <div class="ml-3 relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                            {{ $page.props.auth.user.name }}

                                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        Perfil
                                    </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        Cerrar Sesión
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <div class="-mr-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('clientes.index')" :active="route().current('clientes.*')">
                        Clientes
                    </ResponsiveNavLink>
                    
                    <ResponsiveNavLink :href="route('productos.index')" :active="route().current('productos.*') && !route().current('productos.stock')">
                        Productos
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('productos.stock')" :active="route().current('productos.stock')">
                        Stock
                    </ResponsiveNavLink>

                    <ResponsiveNavLink :href="route('ventas.index')" :active="route().current('ventas.*')">
                        Ventas
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('pagos.index')" :active="route().current('pagos.*')">
                        Pagos
                    </ResponsiveNavLink>
                    <ResponsiveNavLink href="/descuentos" :active="$page.component.startsWith('Descuentos')">
                        Descuentos
                    </ResponsiveNavLink>
                    <ResponsiveNavLink href="/auditorias" :active="$page.component.startsWith('Auditorias')">
                        Auditoría
                    </ResponsiveNavLink>
                </div>

                <div class="pt-4 pb-1 border-t border-gray-200">
                    <div class="px-4">
                        <div class="font-medium text-base text-gray-800">{{ $page.props.auth.user.name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            Perfil
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            Cerrar Sesión
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header class="bg-white shadow" v-if="$slots.header">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>

        <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="fixed top-4 right-4 z-50 flex flex-col gap-2 animate-fade-in-down">
            
            <div v-if="$page.props.flash?.success" class="bg-green-600 text-white px-6 py-4 rounded-lg shadow-xl flex items-center transform transition-all hover:scale-105">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>
                    <h4 class="font-bold text-sm uppercase tracking-wider">¡Éxito!</h4>
                    <p class="text-sm font-medium">{{ $page.props.flash?.success }}</p>
                </div>
            </div>
            
            <div v-if="$page.props.flash?.error" class="bg-red-600 text-white px-6 py-4 rounded-lg shadow-xl flex items-center transform transition-all hover:scale-105">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <h4 class="font-bold text-sm uppercase tracking-wider">Error</h4>
                    <p class="text-sm font-medium">{{ $page.props.flash?.error }}</p>
                </div>
            </div>
        </div>
    </div>
</template>