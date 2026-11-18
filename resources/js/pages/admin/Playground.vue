<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { edit, run } from '@/routes/admin/playground';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Playground',
                href: edit(),
            },
        ],
    },
});

const http = useHttp({ prompt: '' });
const response = ref<unknown>(null);

function submit() {
    response.value = null;

    http.post(run.url(), {
        onSuccess: (result) => {
            response.value = result;
        },
    });
}
</script>

<template>
    <Head title="Playground" />

    <h1 class="sr-only">Playground</h1>

    <div class="space-y-6 px-4 py-6">
        <Heading
            variant="small"
            title="Prompt playground"
            description="Küldj egy promptot a claude CLI-nek, és nézd meg a nyers JSON választ"
        />

        <div class="space-y-2">
            <Textarea
                v-model="http.prompt"
                rows="6"
                placeholder="Kérdezz valamit…"
                data-test="playground-prompt"
            />
            <InputError :message="http.errors.prompt" />
        </div>

        <Button
            :disabled="http.processing || http.prompt.trim() === ''"
            data-test="playground-submit"
            @click="submit"
        >
            {{ http.processing ? 'Futtatás…' : 'Prompt futtatása' }}
        </Button>

        <pre
            v-if="response"
            class="overflow-auto rounded-lg border bg-muted p-4 text-sm"
            data-test="playground-response"
        >{{ JSON.stringify(response, null, 2) }}</pre>
    </div>
</template>
