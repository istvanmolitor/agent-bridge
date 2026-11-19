<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import FileTree from '@/components/FileTree.vue';
import FileViewer from '@/components/FileViewer.vue';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/workspaces';

type Workspace = {
    id: number;
    name: string;
    path: string;
};

const props = defineProps<{
    workspace: Workspace;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Munkaterületek',
                href: index(),
            },
        ],
    },
});

const selectedPath = ref<string | null>(null);
const selectedName = ref<string | null>(null);

function openFile(path: string, name: string) {
    selectedPath.value = path;
    selectedName.value = name;
}
</script>

<template>
    <Head :title="workspace.name" />

    <div class="flex h-full flex-col gap-6 px-6 py-8">
        <Heading
            variant="small"
            eyebrow="Munkaterület"
            :title="workspace.name"
        />
        <p class="nova-code -mt-4 truncate text-ink-faint">
            {{ workspace.path }}
        </p>

        <div
            class="grid min-h-0 flex-1 overflow-hidden rounded-lg border border-border bg-surface-100 shadow-raised md:grid-cols-[320px_1fr]"
        >
            <div
                class="min-h-0 border-b border-border md:border-r md:border-b-0"
            >
                <FileTree
                    :workspace-id="workspace.id"
                    :selected-path="selectedPath"
                    @open="openFile"
                />
            </div>

            <div class="min-h-0">
                <FileViewer
                    :workspace-id="workspace.id"
                    :path="selectedPath"
                    :name="selectedName"
                />
            </div>
        </div>
    </div>
</template>
