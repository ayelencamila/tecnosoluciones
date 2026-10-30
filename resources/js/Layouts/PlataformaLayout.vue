<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const usuario = computed(() => page.props.auth.user);

// Empresa en la que el operador esta trabajando, si entro a alguna.
const empresaActiva = computed(() => page.props.auth.empresa);

const salirDeEmpresa = () => {
    router.post(route('super.empresas.salir'));
};

const cerrarSesion = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <!-- Barra de plataforma: deliberadamente oscura y distinta del panel
             de empresa, para que el operador nunca dude de en qué nivel está
             trabajando (Kendall: visibilidad del estado del sistema). -->
        <nav class="bg-slate-900 text-slate-100">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded bg-indigo-500 text-sm font-bold">
                            TS
                        </span>
                        <div class="leading-tight">
                            <Link :href="route('super.empresas.index')" class="block font-semibold hover:text-white">
                                Panel de plataforma
                            </Link>
                            <span class="text-xs text-slate-400">Administración de empresas clientes</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <span class="hidden text-sm text-slate-300 sm:inline">
                            {{ usuario?.name }}
                        </span>
                        <Link
                            :href="route('profile.edit')"
                            class="text-sm text-slate-300 hover:text-white"
                        >
                            Mi perfil
                        </Link>
                        <button
                            type="button"
                            class="rounded bg-slate-700 px-3 py-1.5 text-sm hover:bg-slate-600"
                            @click="cerrarSesion"
                        >
                            Cerrar sesión
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Aviso persistente cuando el operador tiene una empresa abierta -->
        <div v-if="empresaActiva" class="bg-amber-100 border-b border-amber-300">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6 lg:px-8">
                <p class="text-sm text-amber-900">
                    Estás trabajando dentro de
                    <strong>{{ empresaActiva.nombre }}</strong>.
                    Todo lo que hagas queda registrado en la auditoría de esa empresa.
                </p>
                <div class="flex gap-2">
                    <Link
                        :href="route('dashboard')"
                        class="rounded bg-amber-600 px-3 py-1 text-sm font-medium text-white hover:bg-amber-700"
                    >
                        Ir a su sistema
                    </Link>
                    <button
                        type="button"
                        class="rounded border border-amber-600 px-3 py-1 text-sm font-medium text-amber-900 hover:bg-amber-200"
                        @click="salirDeEmpresa"
                    >
                        Salir de la empresa
                    </button>
                </div>
            </div>
        </div>

        <header v-if="$slots.header" class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Mensajes flash -->
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                v-if="page.props.flash?.success"
                class="mt-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800"
            >
                {{ page.props.flash.success }}
            </div>
            <div
                v-if="page.props.flash?.error"
                class="mt-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800"
            >
                {{ page.props.flash.error }}
            </div>
        </div>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
