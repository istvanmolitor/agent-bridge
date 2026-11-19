<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'dark', Icon: Moon, label: 'Sötét' },
    { value: 'light', Icon: Sun, label: 'Világos' },
    { value: 'system', Icon: Monitor, label: 'Rendszer' },
] as const;
</script>

<template>
    <div
        class="inline-flex gap-1 rounded-md border border-border bg-surface-200 p-1"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            :class="[
                'flex items-center rounded-sm px-3.5 py-1.5 transition-colors duration-[120ms] ease-out',
                appearance === value
                    ? 'bg-plasma-deep text-plasma'
                    : 'text-ink-muted hover:bg-surface-300 hover:text-ink',
            ]"
        >
            <component :is="Icon" class="-ml-1 h-4 w-4" />
            <span class="nova-label ml-1.5 text-current">{{ label }}</span>
        </button>
    </div>
</template>
