<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-sm bg-plasma-deep text-plasma"
    >
        <AppLogoIcon class="size-5 fill-current" />
    </div>
    <div class="ml-1 grid flex-1 text-left">
        <span
            class="truncate font-display text-[15px] leading-tight font-semibold tracking-[0.06em] text-ink uppercase"
            >{{ name }}</span
        >
    </div>
</template>
