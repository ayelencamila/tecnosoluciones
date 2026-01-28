<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';

const props = defineProps({
    modelValue: [String, Number],
    placeholder: { type: String, default: 'Buscar...' },
    apiEndpoint: { type: String, required: true },
    displayField: { type: String, default: 'nombre' },
    minChars: { type: Number, default: 2 },
    clearable: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue']);

const searchQuery = ref('');
const results = ref([]);
const isOpen = ref(false);
const isLoading = ref(false);
const selectedItem = ref(null);
const containerRef = ref(null);

// Buscar cuando cambia el texto
const search = debounce(async (query) => {
    if (query.length < props.minChars) {
        results.value = [];
        return;
    }
    
    isLoading.value = true;
    try {
        const response = await axios.get(props.apiEndpoint, {
            params: { q: query, limit: 10 }
        });
        results.value = response.data;
    } catch (error) {
        console.error('Error en búsqueda:', error);
        results.value = [];
    } finally {
        isLoading.value = false;
    }
}, 300);

watch(searchQuery, (newVal) => {
    if (!selectedItem.value || newVal !== getDisplayText(selectedItem.value)) {
        search(newVal);
        isOpen.value = newVal.length >= props.minChars;
    }
});

const getDisplayText = (item) => {
    if (!item) return '';
    return item[props.displayField] || item.razon_social || item.nombre || '';
};

const selectItem = (item) => {
    selectedItem.value = item;
    searchQuery.value = getDisplayText(item);
    emit('update:modelValue', item.id);
    isOpen.value = false;
    results.value = [];
};

const clearSelection = () => {
    selectedItem.value = null;
    searchQuery.value = '';
    emit('update:modelValue', '');
    results.value = [];
};

const handleFocus = () => {
    if (searchQuery.value.length >= props.minChars) {
        isOpen.value = true;
    }
};

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Si viene un valor inicial, cargarlo
watch(() => props.modelValue, async (newVal) => {
    if (newVal && !selectedItem.value) {
        try {
            const response = await axios.get(props.apiEndpoint, {
                params: { id: newVal }
            });
            if (response.data && response.data.length > 0) {
                selectedItem.value = response.data[0];
                searchQuery.value = getDisplayText(response.data[0]);
            }
        } catch (e) {
            // Ignorar error si no se puede cargar
        }
    } else if (!newVal) {
        selectedItem.value = null;
        searchQuery.value = '';
    }
}, { immediate: true });
</script>

<template>
    <div ref="containerRef" class="relative">
        <div class="relative">
            <input
                type="text"
                v-model="searchQuery"
                @focus="handleFocus"
                :placeholder="placeholder"
                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pr-8"
            />
            
            <!-- Icono de búsqueda o loading -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                <svg v-if="isLoading" class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <button v-else-if="selectedItem && clearable" @click.prevent="clearSelection" type="button" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <svg v-else class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Dropdown de resultados -->
        <div v-if="isOpen && results.length > 0" 
             class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
            <ul class="py-1">
                <li v-for="item in results" 
                    :key="item.id"
                    @click="selectItem(item)"
                    class="px-4 py-2 hover:bg-indigo-50 cursor-pointer text-sm text-gray-700">
                    {{ getDisplayText(item) }}
                </li>
            </ul>
        </div>

        <!-- Sin resultados -->
        <div v-if="isOpen && searchQuery.length >= minChars && results.length === 0 && !isLoading"
             class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
            <p class="px-4 py-2 text-sm text-gray-500">No se encontraron resultados</p>
        </div>
    </div>
</template>
