<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    titulo: { type: String, required: true },
    // Fecha de última revisión del texto. Que sea visible es parte del
    // requisito legal: el usuario tiene que poder saber qué versión aceptó.
    actualizado: { type: String, default: null },
});

const page = usePage();

// El enlace de "volver" depende de si hay sesión abierta o no.
const estaAutenticado = computed(() => !!page.props.auth?.user);
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
                <Link
                    :href="estaAutenticado ? route('dashboard') : route('login')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    ← Volver
                </Link>
                <nav class="flex gap-4 text-sm">
                    <Link
                        :href="route('legal.terminos')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        Términos y Condiciones
                    </Link>
                    <Link
                        :href="route('legal.privacidad')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        Política de Privacidad
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Ancho de lectura acotado y jerarquía tipográfica clara
             (Kendall & Kendall: legibilidad en salidas de texto extenso). -->
        <main class="mx-auto max-w-3xl px-6 py-10">
            <article class="rounded-lg bg-white p-8 shadow-sm sm:p-10">
                <h1 class="text-2xl font-semibold text-gray-900">{{ titulo }}</h1>
                <p v-if="actualizado" class="mt-2 text-sm text-gray-500">
                    Última actualización: {{ actualizado }}
                </p>

                <div class="legal mt-8 text-gray-700">
                    <slot />
                </div>
            </article>
        </main>
    </div>
</template>

<style scoped>
/* Estilos de lectura para el texto legal. Se definen acá una sola vez para
   que las dos páginas (y las que se agreguen) queden consistentes. */
.legal :deep(h2) {
    margin-top: 2rem;
    margin-bottom: 0.75rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
}

.legal :deep(h3) {
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #1f2937;
}

.legal :deep(p) {
    margin-bottom: 0.875rem;
    line-height: 1.7;
}

.legal :deep(ul) {
    margin-bottom: 0.875rem;
    list-style-type: disc;
    padding-left: 1.5rem;
    line-height: 1.7;
}

.legal :deep(li) {
    margin-bottom: 0.375rem;
}

/* Marca visualmente los bloques que todavía hay que redactar, para que sea
   imposible publicar el sistema creyendo que el texto legal está completo. */
.legal :deep(.pendiente) {
    margin-bottom: 1rem;
    border-left: 3px solid #f59e0b;
    background-color: #fffbeb;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #92400e;
}
</style>
