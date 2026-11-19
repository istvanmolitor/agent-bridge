<script setup lang="ts">
import {
    ChevronDown,
    ChevronRight,
    File,
    Folder,
    FolderOpen,
} from '@lucide/vue';
import { ref } from 'vue';
import { tree } from '@/routes/admin/workspaces';

type Entry = {
    name: string;
    path: string;
    type: 'directory' | 'file';
};

const props = defineProps<{
    workspaceId: number;
    entry: Entry;
    depth: number;
    selectedPath: string | null;
}>();

const emit = defineEmits<{
    open: [path: string, name: string];
}>();

const expanded = ref(false);
const loaded = ref(false);
const loading = ref(false);
const error = ref<string | null>(null);
const children = ref<Entry[]>([]);

async function loadChildren() {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(
            tree.url(props.workspaceId, { query: { path: props.entry.path } }),
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

        children.value = (await response.json()).entries as Entry[];
        loaded.value = true;
    } catch {
        error.value = 'Nem sikerült betölteni.';
    } finally {
        loading.value = false;
    }
}

function toggle() {
    expanded.value = !expanded.value;

    if (expanded.value && !loaded.value && !loading.value) {
        loadChildren();
    }
}
</script>

<template>
    <li>
        <button
            v-if="entry.type === 'directory'"
            type="button"
            class="nova-code flex w-full items-center gap-1.5 rounded-sm px-1.5 py-1 text-left text-ink hover:bg-surface-200"
            :style="{ paddingLeft: `${depth * 12 + 6}px` }"
            @click="toggle"
        >
            <ChevronDown
                v-if="expanded"
                class="h-3.5 w-3.5 shrink-0 text-ink-faint"
            />
            <ChevronRight v-else class="h-3.5 w-3.5 shrink-0 text-ink-faint" />
            <FolderOpen v-if="expanded" class="h-4 w-4 shrink-0 text-plasma" />
            <Folder v-else class="h-4 w-4 shrink-0 text-ink-faint" />
            <span class="truncate">{{ entry.name }}</span>
        </button>

        <button
            v-else
            type="button"
            class="nova-code flex w-full items-center gap-1.5 rounded-sm px-1.5 py-1 text-left text-ink hover:bg-surface-200"
            :class="{
                'bg-plasma-deep text-plasma shadow-[inset_2px_0_0_var(--plasma)]':
                    selectedPath === entry.path,
            }"
            :style="{ paddingLeft: `${depth * 12 + 24}px` }"
            @click="emit('open', entry.path, entry.name)"
        >
            <File class="h-4 w-4 shrink-0 text-ink-faint" />
            <span class="truncate">{{ entry.name }}</span>
        </button>

        <div v-if="expanded" class="text-ink-faint">
            <p
                v-if="loading"
                class="px-1.5 py-1 text-xs"
                :style="{ paddingLeft: `${depth * 12 + 24}px` }"
            >
                Betöltés…
            </p>
            <p
                v-else-if="error"
                class="nova-code px-1.5 py-1 text-signal-alert"
                :style="{ paddingLeft: `${depth * 12 + 24}px` }"
            >
                {{ error }}
            </p>
            <p
                v-else-if="loaded && children.length === 0"
                class="px-1.5 py-1 text-xs"
                :style="{ paddingLeft: `${depth * 12 + 24}px` }"
            >
                Üres mappa.
            </p>
            <ul v-else>
                <FileTreeNode
                    v-for="child in children"
                    :key="child.path"
                    :workspace-id="workspaceId"
                    :entry="child"
                    :depth="depth + 1"
                    :selected-path="selectedPath"
                    @open="(path, name) => emit('open', path, name)"
                />
            </ul>
        </div>
    </li>
</template>
