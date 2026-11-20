<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { ref, watch } from 'vue';
import AgentSelect from '@/components/AgentSelect.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/admin/logs';

type Option = {
    id: number;
    name: string;
};

type PromptLog = {
    id: number;
    prompt: string;
    response: string | null;
    success: boolean;
    error: string | null;
    session_id: string | null;
    created_at: string | null;
    user: { id: number; name: string } | null;
    api_key: { id: number; name: string } | null;
};

type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    logs: Paginator<PromptLog>;
    users: Option[];
    apiKeys: Option[];
    agents: Option[];
    workspaces: Option[];
    filters: {
        search: string | null;
        user_id: string | null;
        api_key_id: string | null;
        agent_id: string | null;
        workspace_id: string | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Logok',
                href: index(),
            },
        ],
    },
});

const ALL = 'all';

const search = ref(props.filters.search ?? '');
const userId = ref(props.filters.user_id ?? ALL);
const apiKeyId = ref(props.filters.api_key_id ?? ALL);
const agentId = ref(props.filters.agent_id ?? ALL);
const workspaceId = ref(props.filters.workspace_id ?? ALL);
const expanded = ref<number | null>(null);

function applyFilters() {
    router.get(
        index().url,
        {
            search: search.value || undefined,
            user_id: userId.value === ALL ? undefined : userId.value,
            api_key_id: apiKeyId.value === ALL ? undefined : apiKeyId.value,
            agent_id: agentId.value === ALL ? undefined : agentId.value,
            workspace_id:
                workspaceId.value === ALL ? undefined : workspaceId.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    window.clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(applyFilters, 350);
});

watch([userId, apiKeyId, agentId, workspaceId], () => {
    window.clearTimeout(searchTimeout);
    applyFilters();
});

function toggleExpanded(id: number) {
    expanded.value = expanded.value === id ? null : id;
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString('hu-HU') : '';
}
</script>

<template>
    <Head title="Logok" />

    <h1 class="sr-only">Logok</h1>

    <div class="nova-page space-y-6 px-6 py-8">
        <Heading
            variant="small"
            eyebrow="Telemetria"
            title="Logok"
            description="Minden felhasználó beküldött promptjai és a hozzájuk tartozó válaszok"
        />

        <div
            class="flex flex-col gap-3 rounded-lg border border-border bg-surface-200 p-4 sm:flex-row sm:items-center"
        >
            <Input
                v-model="search"
                type="search"
                placeholder="Keresés a promptokban és válaszokban…"
                class="sm:max-w-sm"
            />

            <Select v-model="userId">
                <SelectTrigger class="sm:w-48">
                    <SelectValue placeholder="Összes felhasználó" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">Összes felhasználó</SelectItem>
                    <SelectItem
                        v-for="user in users"
                        :key="user.id"
                        :value="String(user.id)"
                    >
                        {{ user.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="workspaceId">
                <SelectTrigger class="sm:w-48">
                    <SelectValue placeholder="Összes munkaterület" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">Összes munkaterület</SelectItem>
                    <SelectItem
                        v-for="workspace in workspaces"
                        :key="workspace.id"
                        :value="String(workspace.id)"
                    >
                        {{ workspace.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <AgentSelect
                v-model="agentId"
                :agents="agents"
                include-all
                :all-value="ALL"
                all-label="Összes ügynök"
                trigger-class="sm:w-48"
            />

            <Select v-model="apiKeyId">
                <SelectTrigger class="sm:w-48">
                    <SelectValue placeholder="Összes kulcs" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">Összes kulcs</SelectItem>
                    <SelectItem
                        v-for="apiKey in apiKeys"
                        :key="apiKey.id"
                        :value="String(apiKey.id)"
                    >
                        {{ apiKey.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div
            v-if="logs.data.length === 0"
            class="rounded-lg border border-border bg-surface-100 p-8 text-center text-sm text-ink-muted"
        >
            Nincs a szűrésnek megfelelő log.
        </div>

        <div
            v-else
            class="overflow-hidden rounded-lg border border-border bg-surface-100 shadow-raised"
        >
            <div
                class="flex items-center justify-between border-b border-border bg-surface-200 px-5 py-3"
            >
                <span class="nova-label">Naplósorok</span>
                <span class="nova-label text-ink-muted"
                    >{{ logs.total }} bejegyzés</span
                >
            </div>

            <div class="divide-y divide-border">
                <div
                    v-for="log in logs.data"
                    :key="log.id"
                    class="cursor-pointer space-y-2 p-5 transition-colors duration-[120ms] ease-out hover:bg-surface-200"
                    @click="toggleExpanded(log.id)"
                >
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <Badge :variant="log.success ? 'ok' : 'alert'">
                            {{ log.success ? 'Siker' : 'Hiba' }}
                        </Badge>
                        <span
                            v-if="log.user"
                            class="nova-code inline-flex h-7 items-center rounded-sm border border-plasma bg-plasma-deep px-2 text-plasma"
                            >{{ log.user.name }}</span
                        >
                        <span
                            v-if="log.api_key"
                            class="nova-code inline-flex h-7 items-center rounded-sm border border-border bg-surface-200 px-2 text-ink-muted"
                            >{{ log.api_key.name }}</span
                        >
                        <span v-else class="nova-code text-ink-faint"
                            >(törölt kulcs)</span
                        >
                        <span class="nova-code ml-auto text-ink-faint">{{
                            formatDate(log.created_at)
                        }}</span>
                    </div>

                    <p
                        class="font-medium text-ink"
                        :class="{ 'line-clamp-2': expanded !== log.id }"
                    >
                        {{ log.prompt }}
                    </p>

                    <p
                        class="nova-code whitespace-pre-wrap"
                        :class="[
                            log.error ? 'text-signal-alert' : 'text-ink-muted',
                            { 'line-clamp-3': expanded !== log.id },
                        ]"
                    >
                        {{ log.error ?? log.response ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="logs.total > 0"
            class="flex items-center justify-between gap-4"
        >
            <p class="nova-code text-ink-faint">
                {{ logs.from }}–{{ logs.to }} / {{ logs.total }}
            </p>

            <div class="flex items-center gap-2">
                <Button
                    as-child
                    variant="secondary"
                    size="sm"
                    :disabled="!logs.prev_page_url"
                    :class="{
                        'pointer-events-none opacity-50': !logs.prev_page_url,
                    }"
                >
                    <Link
                        :href="logs.prev_page_url ?? '#'"
                        preserve-scroll
                        preserve-state
                    >
                        <ChevronLeft /> Előző</Link
                    >
                </Button>

                <span class="nova-code text-ink-faint"
                    >{{ logs.current_page }} / {{ logs.last_page }}</span
                >

                <Button
                    as-child
                    variant="secondary"
                    size="sm"
                    :disabled="!logs.next_page_url"
                    :class="{
                        'pointer-events-none opacity-50': !logs.next_page_url,
                    }"
                >
                    <Link
                        :href="logs.next_page_url ?? '#'"
                        preserve-scroll
                        preserve-state
                    >
                        <ChevronRight /> Következő</Link
                    >
                </Button>
            </div>
        </div>
    </div>
</template>
