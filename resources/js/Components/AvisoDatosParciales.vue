<script setup>
/**
 * Aviso de salida recortada por permisos.
 *
 * Kendall & Kendall: una salida incompleta tiene que declararse como tal.
 * Si el operador ve "j•••@gmail.com" sin explicación va a concluir que la
 * ficha está mal cargada y va a intentar "arreglarla", o va a pedirle el
 * dato al cliente por segunda vez. El aviso convierte un dato que parece
 * roto en una restricción comprensible.
 */
defineProps({
    /** Qué se está mirando, para redactar el aviso en concreto. */
    entidad: {
        type: String,
        default: 'los registros',
    },
});
</script>

<template>
    <div class="mb-4 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-3">
        <svg
            class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88"
            />
        </svg>
        <p class="text-sm text-amber-800">
            Estás viendo el contacto de {{ entidad }} de forma parcial: tu rol no
            tiene el permiso para ver los datos completos. Los últimos dígitos
            alcanzan para identificar el registro. Si necesitás el dato entero,
            pedíselo a quien administre los roles del sistema.
        </p>
    </div>
</template>
