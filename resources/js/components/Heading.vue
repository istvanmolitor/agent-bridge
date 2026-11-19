<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    /** Nagybetűs mikrofelirat a cím fölött. */
    eyebrow?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header :class="variant === 'small' ? 'space-y-1' : 'mb-8 space-y-1'">
        <p v-if="eyebrow" class="nova-label">{{ eyebrow }}</p>

        <h2 :class="variant === 'small' ? 'nova-panel-title' : 'nova-title'">
            {{ title }}
        </h2>

        <p v-if="description" class="max-w-prose text-sm text-ink-muted">
            {{ description }}
        </p>
    </header>
</template>
