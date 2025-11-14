<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: String,
});

const classes = computed(() => {
    return 'block font-medium text-sm text-gray-700';
});
</script>

<template>
    <label :class="classes">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
