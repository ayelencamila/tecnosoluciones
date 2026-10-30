<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>

        <!-- Los textos legales tienen que estar accesibles ANTES de crear la
             cuenta, porque el registro exige aceptarlos. -->
        <footer class="mt-6 pb-6 text-center text-xs text-gray-500">
            <Link
                :href="route('legal.terminos')"
                class="hover:text-gray-700 hover:underline"
            >
                Términos y Condiciones
            </Link>
            <span class="mx-2 text-gray-300">|</span>
            <Link
                :href="route('legal.privacidad')"
                class="hover:text-gray-700 hover:underline"
            >
                Política de Privacidad
            </Link>
        </footer>
    </div>
</template>
