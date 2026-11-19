<script setup lang="ts">
import { watch, ref } from 'vue';
import { file as fileRoute } from '@/routes/admin/workspaces';

const props = defineProps<{
    workspaceId: number;
    path: string | null;
    name: string | null;
}>();

type FileContent = {
    path: string;
    content: string;
    truncated: boolean;
    binary: boolean;
};

const content = ref<FileContent | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

async function load(path: string) {
    loading.value = true;
    error.value = null;
    content.value = null;

    try {
        const response = await fetch(
            fileRoute.url(props.workspaceId, { query: { path } }),
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        content.value = (await response.json()) as FileContent;
    } catch {
        error.value = 'Nem sikerült megnyitni a filet.';
    } finally {
        loading.value = false;
    }
}

watch(
    () => [props.workspaceId, props.path] as const,
    ([, path]) => {
        if (path) {
            load(path);
        } else {
            content.value = null;
            error.value = null;
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="border-b border-border bg-surface-200 px-3 py-2">
            <span class="nova-label truncate">
                {{ name ?? 'Nincs megnyitott file' }}
            </span>
        </div>

        <div class="min-h-0 flex-1 overflow-auto">
            <p v-if="!path" class="p-4 text-sm text-ink-muted">
                Válassz egy filet a fából a megtekintéshez.
            </p>
            <p v-else-if="loading" class="p-4 text-sm text-ink-muted">
                Betöltés…
            </p>
            <p v-else-if="error" class="p-4 text-sm text-signal-alert">
                {{ error }}
            </p>
            <p v-else-if="content?.binary" class="p-4 text-sm text-ink-muted">
                Bináris file — nem jeleníthető meg.
            </p>
            <template v-else-if="content">
                <pre
                    class="nova-code p-4 break-words whitespace-pre-wrap text-ink"
                    >{{ content.content }}</pre>
                <p
                    v-if="content.truncated"
                    class="nova-code border-t border-border px-4 py-2 text-ink-faint"
                >
                    A file túl nagy — csak az eleje látszik.
                </p>
            </template>
        </div>
    </div>
</template>
