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

const numericValue = computed(() => parseFloat(props.modelValue) || 0);

const currentRating = computed(() => {
    return hoverValue.value !== null ? hoverValue.value : numericValue.value;
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

const isFilled = (starIndex) => {
    const rating = currentRating.value;
    return rating >= starIndex;
};

const isHalf = (starIndex) => {
    const rating = currentRating.value;
    return !isFilled(starIndex) && rating >= starIndex - 0.5;
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
            <!-- Estrella llena (amarilla) -->
            <svg 
                v-if="isFilled(star) || isHalf(star)"
                class="w-7 h-7 text-amber-400" 
                xmlns="http://www.w3.org/2000/svg" 
                viewBox="0 0 24 24"
                fill="currentColor"
            >
                <path 
                    fill-rule="evenodd"
                    d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" 
                    clip-rule="evenodd"
                />
            </svg>
            <!-- Estrella vacía (contorno gris oscuro) -->
            <svg 
                v-else
                class="w-7 h-7 text-gray-400" 
                xmlns="http://www.w3.org/2000/svg" 
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
            >
                <path 
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" 
                />
            </svg>
        </button>
        
        <span v-if="numericValue > 0" class="ml-2 text-sm font-medium text-gray-700">
            {{ numericValue.toFixed(1) }}
        </span>
    </div>
</template>


