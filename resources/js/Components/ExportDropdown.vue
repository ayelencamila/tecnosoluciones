<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    exportUrl: {
        type: String,
        required: true,
    },
    params: {
        type: Object,
        default: () => ({}),
    },
});

const open = ref(false);
const dropdownRef = ref(null);

const descargar = (formato) => {
    const allParams = {};
    Object.keys(props.params).forEach(key => {
        const val = props.params[key];
        if (val !== '' && val !== null && val !== undefined) {
            allParams[key] = val;
        }
    });
    allParams.formato = formato;
    const queryString = new URLSearchParams(allParams).toString();
    window.location.href = props.exportUrl + '?' + queryString;
    open.value = false;
};

const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <button
            @click="open = !open"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all shadow-sm w-full justify-center"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Exportar
            <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="open"
                class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none overflow-hidden"
            >
                <button
                    @click="descargar('xlsx')"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
                >
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z"/>
                        <path d="M8 13l2.5 3.5L8 20h1.5l1.75-2.5L13 20h1.5l-2.5-3.5L14.5 13H13l-1.75 2.5L9.5 13H8z"/>
                    </svg>
                    <div class="text-left">
                        <div class="font-medium">Excel (.xlsx)</div>
                        <div class="text-xs text-gray-400">Hoja de cálculo</div>
                    </div>
                </button>

                <button
                    @click="descargar('pdf')"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors border-t border-gray-100"
                >
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z"/>
                        <path d="M9.5 13v5H8v-5h1.5zm3.5 0c.83 0 1.5.67 1.5 1.5S13.83 16 13 16h-1v2h-1.5v-5H13zm0 1.5h-1v1h1c.28 0 .5-.22.5-.5s-.22-.5-.5-.5z"/>
                    </svg>
                    <div class="text-left">
                        <div class="font-medium">PDF</div>
                        <div class="text-xs text-gray-400">Documento portable</div>
                    </div>
                </button>

                <button
                    @click="descargar('csv')"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors border-t border-gray-100"
                >
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z"/>
                        <path d="M8 13v5h1.5v-2h1c.83 0 1.5-.67 1.5-1.5S11.33 13 10.5 13H8zm1.5 1.5h1c.28 0 .5.22.5.5s-.22.5-.5.5h-1v-1z"/>
                    </svg>
                    <div class="text-left">
                        <div class="font-medium">CSV</div>
                        <div class="text-xs text-gray-400">Valores separados</div>
                    </div>
                </button>
            </div>
        </Transition>
    </div>
</template>
