<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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
    apiKeys: Option[];
    agents: Option[];
    workspaces: Option[];
    filters: {
        search: string | null;
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
const apiKeyId = ref(props.filters.api_key_id ?? ALL);
const agentId = ref(props.filters.agent_id ?? ALL);
const workspaceId = ref(props.filters.workspace_id ?? ALL);
const expanded = ref<number | null>(null);

function applyFilters() {
    router.get(
        index().url,
        {
            search: search.value || undefined,
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

watch([apiKeyId, agentId, workspaceId], () => {
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

    <div class="space-y-6 px-4 py-6">
        <Heading
            variant="small"
            title="Logok"
            description="A beküldött promptok és a hozzájuk tartozó válaszok, API kulcsonként"
        />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Input
                v-model="search"
                type="search"
                placeholder="Keresés a promptokban és válaszokban…"
                class="sm:max-w-sm"
            />

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

            <Select v-model="agentId">
                <SelectTrigger class="sm:w-48">
                    <SelectValue placeholder="Összes ügynök" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">Összes ügynök</SelectItem>
                    <SelectItem
                        v-for="agent in agents"
                        :key="agent.id"
                        :value="String(agent.id)"
                    >
                        {{ agent.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

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
            class="rounded-lg border p-8 text-center text-sm text-muted-foreground"
        >
            Nincs a szűrésnek megfelelő log.
        </div>

        <div v-else class="divide-y rounded-lg border">
            <div
                v-for="log in logs.data"
                :key="log.id"
                class="cursor-pointer space-y-2 p-4 hover:bg-muted/40"
                @click="toggleExpanded(log.id)"
            >
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <Badge :variant="log.success ? 'secondary' : 'destructive'">
                        {{ log.success ? 'Siker' : 'Hiba' }}
                    </Badge>
                    <span
                        v-if="log.api_key"
                        class="rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                        >{{ log.api_key.name }}</span
                    >
                    <span v-else class="text-xs text-muted-foreground"
                        >(törölt kulcs)</span
                    >
                    <span class="ml-auto text-xs text-muted-foreground">{{
                        formatDate(log.created_at)
                    }}</span>
                </div>

                <p
                    class="font-medium"
                    :class="{ 'line-clamp-2': expanded !== log.id }"
                >
                    {{ log.prompt }}
                </p>

                <p
                    class="whitespace-pre-wrap text-sm text-muted-foreground"
                    :class="{ 'line-clamp-3': expanded !== log.id }"
                >
                    {{ log.error ?? log.response ?? '—' }}
                </p>
            </div>
        </div>

        <div
            v-if="logs.total > 0"
            class="flex items-center justify-between gap-4"
        >
            <p class="text-sm text-muted-foreground">
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
                        >Előző</Link
                    >
                </Button>

                <span class="text-sm text-muted-foreground"
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
                        >Következő</Link
                    >
                </Button>
            </div>
        </div>
    </div>
</template>
