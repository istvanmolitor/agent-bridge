<script setup lang="ts">
import { RefreshCw } from '@lucide/vue';
import { onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import FileTreeNode from '@/components/FileTreeNode.vue';
import { tree } from '@/routes/admin/workspaces';

type Entry = {
    name: string;
    path: string;
    type: 'directory' | 'file';
};

const props = withDefaults(
    defineProps<{
        workspaceId: number;
        selectedPath?: string | null;
    }>(),
    {
        selectedPath: null,
    },
);

const emit = defineEmits<{
    open: [path: string, name: string];
}>();

const entries = ref<Entry[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

async function reload() {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(
            tree.url(props.workspaceId, { query: { path: '' } }),
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

        entries.value = (await response.json()).entries as Entry[];
    } catch {
        error.value = 'Nem sikerült beolvasni a mappát.';
        entries.value = [];
    } finally {
        loading.value = false;
    }
}

onMounted(reload);
watch(() => props.workspaceId, reload);

defineExpose({ reload });
</script>

<template>
    <div class="flex h-full flex-col">
        <div
            class="flex items-center justify-between gap-2 border-b border-border bg-surface-200 px-3 py-2"
        >
            <span class="nova-label">Fileok</span>
            <Button
                variant="ghost"
                size="icon"
                class="h-6 w-6"
                type="button"
                title="Frissítés"
                :disabled="loading"
                @click="reload"
            >
                <RefreshCw
                    class="h-3.5 w-3.5"
                    :class="{ 'animate-spin': loading }"
                />
            </Button>
        </div>

        <div class="min-h-0 flex-1 overflow-auto p-1">
            <p
                v-if="loading && entries.length === 0"
                class="nova-code px-2 py-2 text-ink-faint"
            >
                Betöltés…
            </p>
            <p v-else-if="error" class="nova-code px-2 py-2 text-signal-alert">
                {{ error }}
            </p>
            <p
                v-else-if="entries.length === 0"
                class="nova-code px-2 py-2 text-ink-faint"
            >
                A mappa üres.
            </p>
            <ul v-else>
                <FileTreeNode
                    v-for="entry in entries"
                    :key="entry.path"
                    :workspace-id="workspaceId"
                    :entry="entry"
                    :depth="0"
                    :selected-path="selectedPath"
                    @open="(path, name) => emit('open', path, name)"
                />
            </ul>
        </div>
    </div>
</template>
