<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Number,
        default: 0
    },
    readonly: {
        type: Boolean,
        default: false
    },
    maxStars: {
        type: Number,
        default: 5
    }
});

const emit = defineEmits(['update:modelValue']);

const hoverValue = ref(null);

const currentRating = computed(() => {
    return hoverValue.value !== null ? hoverValue.value : props.modelValue;
});

const setRating = (value) => {
    if (!props.readonly) {
        emit('update:modelValue', value);
    }
};

const onMouseEnter = (value) => {
    if (!props.readonly) {
        hoverValue.value = value;
    }
};

const onMouseLeave = () => {
    hoverValue.value = null;
};

const getStarClass = (starIndex) => {
    const rating = currentRating.value;
    
    if (rating >= starIndex) {
        return 'text-yellow-400 fill-yellow-400'; // Estrella llena
    } else if (rating >= starIndex - 0.5) {
        return 'text-yellow-400 fill-yellow-400 half-star'; // Media estrella
    } else {
        return 'text-gray-300 fill-gray-300'; // Estrella vacía
    }
};
</script>

<template>
    <div class="flex items-center gap-1">
        <button
            v-for="star in maxStars"
            :key="star"
            type="button"
            @click="setRating(star)"
            @mouseenter="onMouseEnter(star)"
            @mouseleave="onMouseLeave"
            :disabled="readonly"
            :class="[
                'transition-all duration-150',
                readonly ? 'cursor-default' : 'cursor-pointer hover:scale-110'
            ]"
        >
            <svg 
                class="w-6 h-6" 
                :class="getStarClass(star)"
                xmlns="http://www.w3.org/2000/svg" 
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1"
            >
                <path 
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" 
                />
            </svg>
        </button>
        
        <span v-if="modelValue > 0" class="ml-2 text-sm font-medium text-gray-700">
            {{ modelValue.toFixed(1) }}
        </span>
    </div>
</template>

<style scoped>
.half-star {
    background: linear-gradient(90deg, #FBBF24 50%, #D1D5DB 50%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>
