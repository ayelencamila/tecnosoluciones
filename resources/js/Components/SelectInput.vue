<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        required: true, // Array de { value: '...', label: '...' }
    },
    id: String,
    name: String,
});

const emit = defineEmits(['update:modelValue']);

const proxyValue = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit('update:modelValue', val);
    },
});
</script>

<template>
    <select
        :id="id"
        :name="name"
        v-model="proxyValue"
        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
    >
        <option v-for="option in options" :key="option.value" :value="option.value">
            {{ option.label }}
        </option>
    </select>
</template>