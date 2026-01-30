<script setup>
import { onMounted, ref, computed } from 'vue';

const props = defineProps({
    id: String,
    name: String,
});

const model = defineModel({
    type: [String, Number],
    required: true,
});

const input = ref(null);

// Generar ID único si no se proporciona
const generatedId = ref(`input-${Math.random().toString(36).substring(2, 9)}`);
const inputId = computed(() => props.id || generatedId.value);
const inputName = computed(() => props.name || inputId.value);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        :id="inputId"
        :name="inputName"
        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
        v-bind="$attrs"
        v-model="model"
        ref="input"
    />
</template>
