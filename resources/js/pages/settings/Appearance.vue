<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Megjelenés beállítások',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Megjelenés beállítások" />

    <h1 class="sr-only">Megjelenés beállítások</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Megjelenés beállítások"
            description="A fiókod megjelenési beállításainak módosítása"
        />
        <AppearanceTabs />
    </div>
</template>
