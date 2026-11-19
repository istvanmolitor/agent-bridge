<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="nova-grid flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="home()" class="flex flex-col items-center">
                        <div
                            class="mb-1 flex h-11 w-11 items-center justify-center rounded-sm bg-plasma-deep text-plasma"
                        >
                            <AppLogoIcon class="size-6 fill-current" />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="nova-panel-title">{{ title }}</h1>
                        <p class="text-center text-sm text-ink-muted">
                            {{ description }}
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-lg border border-border bg-card p-6 shadow-raised"
                >
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
