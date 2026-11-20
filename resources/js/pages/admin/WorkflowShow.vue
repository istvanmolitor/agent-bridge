<script setup lang="ts">
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';

import { Head, router, useForm } from '@inertiajs/vue3';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import {
    type Connection,
    Handle,
    Position,
    useVueFlow,
    VueFlow,
} from '@vue-flow/core';
import { Check, Pencil, Plus, Trash2, Unlink, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/admin/workflows';
import {
    destroy as destroyConnection,
    store as storeConnection,
} from '@/routes/admin/workflows/connections';
import {
    destroy as destroyNode,
    position as positionNode,
    store as storeNode,
    update as updateNode,
} from '@/routes/admin/workflows/nodes';

type NodeData = {
    id: number;
    name: string;
    description: string | null;
    position_x: number;
    position_y: number;
};

type ConnectionData = {
    id: number;
    source_node_id: number;
    target_node_id: number;
};

const props = defineProps<{
    workflow: { id: number; name: string; description: string | null };
    nodes: NodeData[];
    connections: ConnectionData[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Folyamatok',
                href: index(),
            },
        ],
    },
});

// --- Vue Flow state, kept in sync with the Inertia props -------------------
// Deliberately loose local types: Vue Flow's own Node/Edge generics blow up
// vue-tsc (TS2589) when stored in a ref, and the canvas accepts plain objects.

type FlowNode = {
    id: string;
    type: string;
    position: { x: number; y: number };
    data: { name: string; description: string | null };
};

type FlowEdge = {
    id: string;
    source: string;
    target: string;
    data?: { connectionId: number };
};

const vfNodes = ref<FlowNode[]>([]);
const vfEdges = ref<FlowEdge[]>([]);

function toVfNode(node: NodeData): FlowNode {
    return {
        id: String(node.id),
        type: 'workflow',
        position: { x: node.position_x, y: node.position_y },
        data: { name: node.name, description: node.description },
    };
}

function toVfEdge(connection: ConnectionData): FlowEdge {
    return {
        id: `e${connection.id}`,
        source: String(connection.source_node_id),
        target: String(connection.target_node_id),
        data: { connectionId: connection.id },
    };
}

// Reconcile instead of replace, so live node positions survive prop refreshes.
function syncNodes(): void {
    const existing = new Map<string, FlowNode>();
    vfNodes.value.forEach((node) => existing.set(node.id, node));

    const next: FlowNode[] = [];

    for (const node of props.nodes) {
        const current = existing.get(String(node.id));

        if (current) {
            current.data = { name: node.name, description: node.description };
            next.push(current);
        } else {
            next.push(toVfNode(node));
        }
    }

    vfNodes.value = next;
}

function syncEdges(): void {
    vfEdges.value = props.connections.map(toVfEdge);
}

syncNodes();
syncEdges();

watch(() => props.nodes, syncNodes, { deep: true });
watch(() => props.connections, syncEdges, { deep: true });

// --- Canvas interactions ---------------------------------------------------

const { onConnect, onNodeDragStop, getSelectedEdges } = useVueFlow();

onConnect((connection: Connection) => {
    const source = connection.source;
    const target = connection.target;

    if (!source || !target || source === target) {
        return;
    }

    const exists = vfEdges.value.some(
        (edge) => edge.source === source && edge.target === target,
    );

    if (!exists) {
        vfEdges.value = [
            ...vfEdges.value,
            { id: `tmp-${source}-${target}`, source, target },
        ];
    }

    router.post(
        storeConnection.url(props.workflow.id),
        { source_node_id: Number(source), target_node_id: Number(target) },
        { preserveScroll: true, preserveState: true, onError: syncEdges },
    );
});

onNodeDragStop(({ node }) => {
    router.patch(
        positionNode.url([props.workflow.id, Number(node.id)]),
        { position_x: node.position.x, position_y: node.position.y },
        { preserveScroll: true, preserveState: true, only: ['workflow'] },
    );
});

const selectedEdgeCount = computed(() => getSelectedEdges.value.length);

function deleteSelectedEdges(): void {
    for (const edge of getSelectedEdges.value) {
        const connectionId = edge.data?.connectionId as number | undefined;

        if (connectionId) {
            router.delete(
                destroyConnection.url([props.workflow.id, connectionId]),
                { preserveScroll: true, preserveState: true },
            );
        } else {
            vfEdges.value = vfEdges.value.filter((e) => e.id !== edge.id);
        }
    }
}

// --- Node CRUD -------------------------------------------------------------

function nextPosition(): { position_x: number; position_y: number } {
    const count = vfNodes.value.length;

    return {
        position_x: 80 + (count % 4) * 220,
        position_y: 80 + Math.floor(count / 4) * 160,
    };
}

const createOpen = ref(false);
const createForm = useForm({ name: '', description: '', position_x: 0, position_y: 0 });

function submitCreate(): void {
    createForm
        .transform((data) => ({ ...data, ...nextPosition() }))
        .post(storeNode.url(props.workflow.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                createForm.reset();
                createOpen.value = false;
            },
        });
}

const editing = ref<NodeData | null>(null);
const editForm = useForm({ name: '', description: '' });

watch(editing, (node) => {
    if (node) {
        editForm.clearErrors();
        editForm.name = node.name;
        editForm.description = node.description ?? '';
    }
});

function submitEdit(): void {
    if (!editing.value) {
        return;
    }

    editForm.put(updateNode.url([props.workflow.id, editing.value.id]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            editing.value = null;
        },
    });
}

const deleting = ref<NodeData | null>(null);

function confirmDelete(): void {
    if (!deleting.value) {
        return;
    }

    router.delete(destroyNode.url([props.workflow.id, deleting.value.id]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            deleting.value = null;
        },
    });
}
</script>

<template>
    <Head :title="workflow.name" />

    <div class="flex h-full flex-col gap-6 px-6 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                eyebrow="Workflow"
                :title="workflow.name"
                :description="
                    workflow.description ??
                    'Húzz a node-ok széléről egy másik node-ra kapcsolat létrehozásához.'
                "
            />

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button data-test="create-node-button">
                        <Plus /> Új node</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit.prevent="submitCreate">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Új node létrehozása</DialogTitle>
                            <DialogDescription>
                                Add meg a node nevét és egy leírását.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="node-name">Név</Label>
                            <Input
                                id="node-name"
                                v-model="createForm.name"
                                placeholder="pl. Letöltés"
                                required
                            />
                            <InputError :message="createForm.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="node-description">Leírás</Label>
                            <Textarea
                                id="node-description"
                                v-model="createForm.description"
                                rows="4"
                                placeholder="Mit csinál ez a lépés? (opcionális)"
                            />
                            <InputError
                                :message="createForm.errors.description"
                            />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" type="button">
                                    <X /> Mégse</Button
                                >
                            </DialogClose>

                            <Button type="submit" :disabled="createForm.processing">
                                <Plus /> Node létrehozása
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            class="grid min-h-0 flex-1 overflow-hidden rounded-lg border border-border bg-surface-100 shadow-raised lg:grid-cols-[280px_1fr]"
        >
            <!-- Node list -->
            <div
                class="flex min-h-0 flex-col border-b border-border lg:border-r lg:border-b-0"
            >
                <div
                    class="flex items-center justify-between border-b border-border bg-surface-200 px-4 py-3"
                >
                    <span class="nova-label">Node-ok</span>
                    <span class="nova-label text-ink-muted"
                        >{{ nodes.length }} db</span
                    >
                </div>

                <div
                    v-if="nodes.length === 0"
                    class="p-4 text-sm text-ink-muted"
                >
                    Még nincs node. Hozz létre egyet az „Új node” gombbal.
                </div>

                <div v-else class="min-h-0 flex-1 divide-y divide-border overflow-y-auto">
                    <div
                        v-for="node in nodes"
                        :key="node.id"
                        class="flex items-start justify-between gap-2 p-4"
                    >
                        <div class="min-w-0 space-y-1">
                            <p class="truncate font-medium text-ink">
                                {{ node.name }}
                            </p>
                            <p
                                v-if="node.description"
                                class="line-clamp-2 text-sm text-ink-muted"
                            >
                                {{ node.description }}
                            </p>
                        </div>

                        <div class="flex shrink-0 gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Szerkesztés"
                                @click="editing = node"
                            >
                                <Pencil />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Törlés"
                                class="text-signal-alert hover:text-signal-alert"
                                @click="deleting = node"
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Canvas -->
            <div class="relative min-h-0">
                <div
                    v-if="selectedEdgeCount > 0"
                    class="absolute top-3 right-3 z-10"
                >
                    <Button variant="destructive" size="sm" @click="deleteSelectedEdges">
                        <Unlink /> Kapcsolat törlése
                    </Button>
                </div>

                <VueFlow
                    v-model:nodes="vfNodes"
                    v-model:edges="vfEdges"
                    :delete-key-code="null"
                    :min-zoom="0.2"
                    fit-view-on-init
                    class="h-full w-full"
                >
                    <Background :gap="16" />
                    <Controls />

                    <template #node-workflow="{ data }">
                        <div class="workflow-node">
                            <Handle type="target" :position="Position.Left" />
                            <p class="workflow-node__title">{{ data.name }}</p>
                            <p
                                v-if="data.description"
                                class="workflow-node__desc"
                            >
                                {{ data.description }}
                            </p>
                            <Handle type="source" :position="Position.Right" />
                        </div>
                    </template>
                </VueFlow>
            </div>
        </div>
    </div>

    <!-- Edit node dialog -->
    <Dialog
        :open="editing !== null"
        @update:open="(open) => (editing = open ? editing : null)"
    >
        <DialogContent>
            <form class="space-y-6" @submit.prevent="submitEdit">
                <DialogHeader class="space-y-3">
                    <DialogTitle>Node szerkesztése</DialogTitle>
                    <DialogDescription>
                        Módosítsd a node nevét vagy leírását.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="edit-node-name">Név</Label>
                    <Input id="edit-node-name" v-model="editForm.name" required />
                    <InputError :message="editForm.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-node-description">Leírás</Label>
                    <Textarea
                        id="edit-node-description"
                        v-model="editForm.description"
                        rows="4"
                    />
                    <InputError :message="editForm.errors.description" />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" type="button">
                            <X /> Mégse</Button
                        >
                    </DialogClose>

                    <Button type="submit" :disabled="editForm.processing">
                        <Check /> Mentés
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Delete node dialog -->
    <Dialog
        :open="deleting !== null"
        @update:open="(open) => (deleting = open ? deleting : null)"
    >
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle
                    >Törlöd a(z) "{{ deleting?.name }}" node-ot?</DialogTitle
                >
                <DialogDescription>
                    A node-hoz tartozó kapcsolatok is törlődnek. Ez nem vonható
                    vissza.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary" type="button">
                        <X /> Mégse</Button
                    >
                </DialogClose>

                <Button variant="destructive" @click="confirmDelete">
                    <Trash2 /> Node törlése
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.workflow-node {
    position: relative;
    min-width: 150px;
    max-width: 220px;
    border-radius: 0.5rem;
    border: 1px solid var(--color-border);
    background-color: var(--color-surface-200);
    padding: 0.625rem 0.875rem;
    box-shadow: var(--shadow-raised, 0 1px 2px rgb(0 0 0 / 0.2));
}

.workflow-node__title {
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--color-ink);
}

.workflow-node__desc {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    line-height: 1rem;
    color: var(--color-ink-muted);
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
