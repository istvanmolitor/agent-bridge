<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronRight,
    Folder,
    LoaderCircle,
    Plus,
    RefreshCw,
    SendHorizontal,
    X,
} from '@lucide/vue';
import { computed, nextTick, ref, watch, type Ref } from 'vue';
import FileTree from '@/components/FileTree.vue';
import FileViewer from '@/components/FileViewer.vue';
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
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { index, run } from '@/routes/admin/chat';
import { store as storeSession } from '@/routes/admin/chat/sessions';

type Agent = { id: number; name: string };
type Workspace = { id: number; name: string };

type Session = {
    id: number;
    agent: Agent | null;
    workspace: Workspace | null;
    has_session: boolean;
    last_used_at: string | null;
    created_at: string | null;
};

type PromptLog = {
    id: number;
    prompt: string;
    response: string | null;
    success: boolean;
    error: string | null;
    created_at: string | null;
};

const props = defineProps<{
    sessions: Session[];
    agents: Agent[];
    workspaces: Workspace[];
    logs: PromptLog[];
    selectedSessionId: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Chat',
                href: index(),
            },
        ],
    },
});

const canCreate = computed(
    () => props.agents.length > 0 && props.workspaces.length > 0,
);

const http = useHttp<{ prompt: string; agent_session_id: string | null }>({
    prompt: '',
    agent_session_id:
        props.selectedSessionId ??
        (props.sessions[0] ? String(props.sessions[0].id) : null),
});

const selectedSession = computed(
    () =>
        props.sessions.find(
            (session) => String(session.id) === http.agent_session_id,
        ) ?? null,
);

const workspace = computed(() => selectedSession.value?.workspace ?? null);

function selectSession(session: Session) {
    http.agent_session_id = String(session.id);
}

// Keep the active session in sync when the server selects one (create / restart).
watch(
    () => props.selectedSessionId,
    (id) => {
        if (id && id !== http.agent_session_id) {
            http.agent_session_id = id;
        }
    },
);

// --- Create session modal ---
const createOpen = ref(false);
const newAgentId = ref<string | undefined>();
const newWorkspaceId = ref<string | undefined>();

function openCreate() {
    newAgentId.value = props.agents[0] ? String(props.agents[0].id) : undefined;
    newWorkspaceId.value = props.workspaces[0]
        ? String(props.workspaces[0].id)
        : undefined;
    createOpen.value = true;
}

function createSession() {
    if (!newAgentId.value || !newWorkspaceId.value) {
        return;
    }

    router.post(
        storeSession.url(),
        { agent_id: newAgentId.value, workspace_id: newWorkspaceId.value },
        { onSuccess: () => (createOpen.value = false) },
    );
}

// Start a fresh session reusing the active session's agent + workspace.
function restartSession() {
    const current = selectedSession.value;
    if (!current?.agent || !current?.workspace) {
        return;
    }

    router.post(storeSession.url(), {
        agent_id: current.agent.id,
        workspace_id: current.workspace.id,
    });
}

// --- Workspace modal ---
const workspaceOpen = ref(false);
const fileTree = ref<InstanceType<typeof FileTree> | null>(null);
const selectedFilePath = ref<string | null>(null);
const selectedFileName = ref<string | null>(null);

function openFile(path: string, name: string) {
    selectedFilePath.value = path;
    selectedFileName.value = name;
}

// When the active session (and thus workspace) changes, drop the open file.
watch(workspace, () => {
    selectedFilePath.value = null;
    selectedFileName.value = null;
});

const chatEnd = ref<HTMLElement | null>(null);

// Which prompts / answers are expanded. Default: only the most recent message
// is open; older ones stay collapsed and show just the first few lines.
const expandedPrompts = ref<Set<number>>(new Set());
const expandedResponses = ref<Set<number>>(new Set());

watch(
    () => props.logs,
    (logs) => {
        const last = logs[logs.length - 1];
        const lastonly = new Set(last ? [last.id] : []);
        expandedPrompts.value = new Set(lastonly);
        expandedResponses.value = new Set(lastonly);
        scrollToBottom();
    },
    { immediate: true },
);

// Reload the conversation when the active session changes.
watch(
    () => http.agent_session_id,
    (id) => {
        router.reload({
            only: ['logs', 'selectedSessionId'],
            data: { agent_session_id: id ?? undefined },
        });
    },
);

function toggleIn(set: Ref<Set<number>>, id: number) {
    const next = new Set(set.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    set.value = next;
}

function isPromptExpanded(id: number): boolean {
    return expandedPrompts.value.has(id);
}

function togglePrompt(id: number) {
    toggleIn(expandedPrompts, id);
}

function isResponseExpanded(id: number): boolean {
    return expandedResponses.value.has(id);
}

function toggleResponse(id: number) {
    toggleIn(expandedResponses, id);
}

function scrollToBottom() {
    nextTick(() => chatEnd.value?.scrollIntoView({ block: 'end' }));
}

function submit() {
    if (
        http.processing ||
        http.prompt.trim() === '' ||
        !http.agent_session_id
    ) {
        return;
    }

    http.post(run.url(), {
        onSuccess: () => {
            http.prompt = '';
            router.reload({
                only: ['logs', 'sessions', 'selectedSessionId'],
                data: { agent_session_id: http.agent_session_id ?? undefined },
            });
            // The agent may have changed files in the workspace.
            fileTree.value?.reload();
        },
    });
}

function sessionTitle(session: Session): string {
    return session.agent?.name ?? 'Ügynök';
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString('hu-HU') : '';
}
</script>

<template>
    <Head title="Chat" />

    <h1 class="sr-only">Chat</h1>

    <div class="flex h-[calc(100svh-4rem)] min-h-0">
        <!-- Sessions sidebar -->
        <aside class="flex w-72 shrink-0 flex-col border-r border-border">
            <div class="border-b border-border p-3">
                <Button
                    class="w-full"
                    :disabled="!canCreate"
                    :title="
                        canCreate
                            ? undefined
                            : 'Előbb hozz létre egy ügynököt és egy munkaterületet'
                    "
                    data-test="chat-new-session"
                    @click="openCreate"
                >
                    <Plus class="h-4 w-4" />
                    Új munkamenet
                </Button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                <p
                    v-if="sessions.length === 0"
                    class="p-4 text-sm text-ink-muted"
                >
                    Még nincs munkamenet.
                </p>

                <button
                    v-for="session in sessions"
                    :key="session.id"
                    type="button"
                    class="flex w-full flex-col items-start gap-1 border-b border-border px-3 py-2.5 text-left text-sm transition-colors duration-[120ms] ease-out hover:bg-surface-200"
                    :class="{
                        'bg-plasma-deep text-plasma shadow-[inset_2px_0_0_var(--plasma)]':
                            String(session.id) === http.agent_session_id,
                    }"
                    @click="selectSession(session)"
                >
                    <span class="font-medium">{{ sessionTitle(session) }}</span>
                    <span
                        class="nova-code flex items-center gap-1.5 text-ink-muted"
                    >
                        <Folder class="h-3 w-3 shrink-0" />
                        {{ session.workspace?.name ?? '—' }}
                    </span>
                    <span class="nova-code text-ink-faint">
                        {{
                            formatDate(
                                session.last_used_at ?? session.created_at,
                            )
                        }}
                    </span>
                </button>
            </div>
        </aside>

        <!-- Chat area -->
        <section class="flex min-h-0 min-w-0 flex-1 flex-col gap-4 px-6 py-6">
            <div class="flex items-start justify-between gap-3">
                <Heading
                    variant="small"
                    eyebrow="Munkamenet"
                    title="Chat"
                    description="Beszélgess a kiválasztott munkamenet ügynökével."
                />

                <div v-if="selectedSession" class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        type="button"
                        title="Új munkamenet ugyanezzel az ügynökkel"
                        data-test="chat-restart"
                        @click="restartSession"
                    >
                        <RefreshCw class="h-4 w-4" />
                        Újraindítás
                    </Button>

                    <Button
                        variant="outline"
                        size="sm"
                        type="button"
                        :disabled="!workspace"
                        data-test="chat-workspace"
                        @click="workspaceOpen = true"
                    >
                        <Folder class="h-4 w-4" />
                        Munkaterület
                    </Button>
                </div>
            </div>

            <div
                v-if="sessions.length === 0"
                class="flex flex-1 items-center justify-center text-sm text-ink-muted"
            >
                Hozz létre egy munkamenetet a bal oldali gombbal.
            </div>

            <template v-else>
                <!-- Chat transcript -->
                <div
                    class="nova-grid min-h-0 flex-1 space-y-4 overflow-y-auto rounded-lg border border-border bg-surface-void p-4"
                    data-test="chat-transcript"
                >
                    <p
                        v-if="logs.length === 0"
                        class="py-8 text-center text-sm text-ink-muted"
                    >
                        Még nincs üzenet ebben a munkamenetben. Kérdezz valamit
                        lent.
                    </p>

                    <template v-for="log in logs" :key="log.id">
                        <!-- User prompt (collapsible) -->
                        <div class="flex justify-end">
                            <div
                                class="max-w-[85%] overflow-hidden rounded-md border border-plasma bg-plasma-deep text-ink"
                            >
                                <button
                                    type="button"
                                    class="nova-label flex w-full items-center gap-2 px-4 py-2 text-left text-plasma hover:bg-surface-300/40"
                                    @click="togglePrompt(log.id)"
                                >
                                    <ChevronDown
                                        v-if="isPromptExpanded(log.id)"
                                        class="h-3.5 w-3.5 shrink-0"
                                    />
                                    <ChevronRight
                                        v-else
                                        class="h-3.5 w-3.5 shrink-0"
                                    />
                                    <span>Prompt</span>
                                    <span class="ml-auto">{{
                                        formatDate(log.created_at)
                                    }}</span>
                                </button>

                                <p
                                    class="nova-code px-4 pb-3 whitespace-pre-wrap"
                                    :class="{
                                        'line-clamp-4': !isPromptExpanded(
                                            log.id,
                                        ),
                                    }"
                                >
                                    {{ log.prompt }}
                                </p>
                            </div>
                        </div>

                        <!-- Agent response (collapsible) -->
                        <div class="flex justify-start">
                            <div
                                class="max-w-[85%] overflow-hidden rounded-md border border-border bg-surface-100"
                            >
                                <button
                                    type="button"
                                    class="nova-label flex w-full items-center gap-2 px-4 py-2 text-left hover:bg-surface-200"
                                    @click="toggleResponse(log.id)"
                                >
                                    <ChevronDown
                                        v-if="isResponseExpanded(log.id)"
                                        class="h-3.5 w-3.5 shrink-0"
                                    />
                                    <ChevronRight
                                        v-else
                                        class="h-3.5 w-3.5 shrink-0"
                                    />
                                    <span
                                        :class="
                                            log.success
                                                ? 'text-signal-ok'
                                                : 'text-signal-alert'
                                        "
                                    >
                                        {{ log.success ? 'Válasz' : 'Hiba' }}
                                    </span>
                                    <span class="ml-auto">{{
                                        formatDate(log.created_at)
                                    }}</span>
                                </button>

                                <p
                                    class="nova-code px-4 pb-3 whitespace-pre-wrap"
                                    :class="[
                                        log.error
                                            ? 'text-signal-alert'
                                            : 'text-ink',
                                        {
                                            'line-clamp-4': !isResponseExpanded(
                                                log.id,
                                            ),
                                        },
                                    ]"
                                >
                                    {{ log.error ?? log.response ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </template>

                    <div ref="chatEnd" />
                </div>

                <!-- Composer -->
                <div class="shrink-0 space-y-2">
                    <div class="flex items-end gap-2">
                        <Textarea
                            v-model="http.prompt"
                            rows="3"
                            class="flex-1"
                            placeholder="Kérdezz valamit…"
                            data-test="chat-prompt"
                            @keydown.enter.exact.prevent="submit"
                        />

                        <Button
                            size="icon"
                            :disabled="
                                http.processing ||
                                http.prompt.trim() === '' ||
                                !http.agent_session_id
                            "
                            :title="
                                http.processing
                                    ? 'Futtatás…'
                                    : 'Prompt futtatása'
                            "
                            aria-label="Prompt futtatása"
                            data-test="chat-submit"
                            @click="submit"
                        >
                            <LoaderCircle
                                v-if="http.processing"
                                class="h-4 w-4 animate-spin"
                            />
                            <SendHorizontal v-else class="h-4 w-4" />
                        </Button>
                    </div>
                    <InputError :message="http.errors.prompt" />
                </div>
            </template>
        </section>
    </div>

    <!-- Create session modal -->
    <Dialog v-model:open="createOpen">
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>Új munkamenet</DialogTitle>
                <DialogDescription>
                    Válassz egy ügynököt és egy munkaterületet a beszélgetéshez.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-2">
                <Label>Ügynök</Label>
                <Select v-model="newAgentId">
                    <SelectTrigger data-test="chat-create-agent">
                        <SelectValue placeholder="Válassz ügynököt…" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="agent in agents"
                            :key="agent.id"
                            :value="String(agent.id)"
                        >
                            {{ agent.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="grid gap-2">
                <Label>Munkaterület</Label>
                <Select v-model="newWorkspaceId">
                    <SelectTrigger data-test="chat-create-workspace">
                        <SelectValue placeholder="Válassz munkaterületet…" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="ws in workspaces"
                            :key="ws.id"
                            :value="String(ws.id)"
                        >
                            {{ ws.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary"> <X /> Mégse</Button>
                </DialogClose>
                <Button
                    type="button"
                    :disabled="!newAgentId || !newWorkspaceId"
                    data-test="chat-create-session"
                    @click="createSession"
                >
                    <Plus />
                    Létrehozás
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Workspace modal -->
    <Dialog v-model:open="workspaceOpen">
        <DialogContent
            class="flex h-[95vh] w-[95vw] max-w-[95vw] flex-col gap-0 overflow-hidden p-0 sm:max-w-[95vw]"
        >
            <DialogHeader class="border-b border-border px-4 py-3">
                <DialogTitle>
                    Munkaterület
                    <span v-if="workspace" class="nova-code text-ink-faint">
                        — {{ workspace.name }}</span
                    >
                </DialogTitle>
            </DialogHeader>

            <div
                v-if="workspace"
                class="grid min-h-0 flex-1 grid-cols-[1fr_2fr] divide-x divide-border"
            >
                <div class="min-h-0 overflow-hidden">
                    <FileTree
                        ref="fileTree"
                        :key="workspace.id"
                        :workspace-id="workspace.id"
                        :selected-path="selectedFilePath"
                        @open="openFile"
                    />
                </div>
                <div class="min-h-0 overflow-hidden">
                    <FileViewer
                        :workspace-id="workspace.id"
                        :path="selectedFilePath"
                        :name="selectedFileName"
                    />
                </div>
            </div>

            <p v-else class="p-4 text-sm text-ink-muted">
                Ehhez a munkamenethez nincs munkaterület rendelve.
            </p>
        </DialogContent>
    </Dialog>
</template>
