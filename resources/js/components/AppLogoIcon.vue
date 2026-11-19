<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M11.1 2.5a.75.75 0 0 1 1.42 0l1.78 4.78 4.78 1.78a.75.75 0 0 1 0 1.41l-4.78 1.78-1.78 4.78a.75.75 0 0 1-1.42 0l-1.78-4.78-4.78-1.78a.75.75 0 0 1 0-1.41l4.78-1.78 1.78-4.78Z"
        />
        <path
            fill="currentColor"
            d="M18.2 14.2a.6.6 0 0 1 1.13 0l.77 2.07 2.07.77a.6.6 0 0 1 0 1.13l-2.07.77-.77 2.07a.6.6 0 0 1-1.13 0l-.77-2.07-2.07-.77a.6.6 0 0 1 0-1.13l2.07-.77.77-2.07Z"
        />
    </svg>
</template>
