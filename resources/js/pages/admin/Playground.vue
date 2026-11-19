<script setup lang="ts">
import { Head, router, useHttp } from "@inertiajs/vue3";
import { ChevronDown, ChevronRight } from "@lucide/vue";
import { computed, nextTick, ref, watch, type Ref } from "vue";
import Heading from "@/components/Heading.vue";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { edit, reset, run } from "@/routes/admin/playground";

type ApiKey = {
    id: number;
    name: string;
    claude_session_id: string | null;
    agent: { id: number; name: string } | null;
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
    apiKeys: ApiKey[];
    logs: PromptLog[];
    selectedApiKeyId: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: "Playground",
                href: edit(),
            },
        ],
    },
});

const http = useHttp<{ prompt: string; api_key_id: string | null }>({
    prompt: "",
    api_key_id:
        props.selectedApiKeyId ??
        (props.apiKeys[0] ? String(props.apiKeys[0].id) : null),
});

const selectedKey = computed(
    () =>
        props.apiKeys.find((key) => String(key.id) === http.api_key_id) ?? null,
);

const hasSession = computed(() => selectedKey.value?.claude_session_id != null);

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

// Reload the conversation when the selected API key changes.
watch(
    () => http.api_key_id,
    (id) => {
        router.reload({
            only: ["logs", "selectedApiKeyId"],
            data: { api_key_id: id ?? undefined },
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
    nextTick(() => chatEnd.value?.scrollIntoView({ block: "end" }));
}

function submit() {
    if (http.processing || http.prompt.trim() === "" || !http.api_key_id) {
        return;
    }

    http.post(run.url(), {
        onSuccess: () => {
            http.prompt = "";
            router.reload({
                only: ["logs", "apiKeys", "selectedApiKeyId"],
                data: { api_key_id: http.api_key_id ?? undefined },
            });
        },
    });
}

function resetSession() {
    if (!http.api_key_id) {
        return;
    }

    router.post(
        reset.url(),
        { api_key_id: http.api_key_id },
        { preserveScroll: true },
    );
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString("hu-HU") : "";
}
</script>

<template>
    <Head title="Playground" />

    <h1 class="sr-only">Playground</h1>

    <div class="flex h-full flex-col gap-4 px-4 py-6">
        <Heading
            variant="small"
            title="Prompt playground"
            description="Válassz egy API kulcsot — ez határozza meg az ügynököt és a vele folytatott munkamenetet — majd beszélgess vele."
        />

        <div v-if="apiKeys.length === 0" class="text-sm text-muted-foreground">
            Előbb hozz létre egy ügynököt és egy API kulcsot.
        </div>

        <template v-else>
            <div class="flex flex-wrap items-end gap-3">
                <div class="grid gap-2">
                    <label class="text-sm font-medium">API kulcs</label>
                    <Select v-model="http.api_key_id">
                        <SelectTrigger
                            class="w-full md:w-96"
                            data-test="playground-api-key"
                        >
                            <SelectValue placeholder="Válassz API kulcsot…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="key in apiKeys"
                                :key="key.id"
                                :value="String(key.id)"
                            >
                                {{ key.name }}
                                <template v-if="key.agent">
                                    — {{ key.agent.name }}</template
                                >
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div
                    class="flex items-center gap-3 text-sm text-muted-foreground"
                >
                    <span v-if="hasSession"
                        >Folytatódó munkamenet ehhez a kulcshoz.</span
                    >
                    <span v-else
                        >Új munkamenet indul a következő prompttal.</span
                    >
                    <Button
                        v-if="hasSession"
                        variant="outline"
                        size="sm"
                        type="button"
                        @click="resetSession"
                    >
                        Munkamenet újraindítása
                    </Button>
                </div>
            </div>
            <InputError :message="http.errors.api_key_id" />

            <!-- Chat transcript -->
            <div
                class="min-h-0 flex-1 space-y-4 overflow-y-auto rounded-lg border bg-muted/20 p-4"
                data-test="playground-chat"
            >
                <p
                    v-if="logs.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    Még nincs üzenet ehhez a kulcshoz. Kérdezz valamit lent.
                </p>

                <template v-for="log in logs" :key="log.id">
                    <!-- User prompt (collapsible) -->
                    <div class="flex justify-end">
                        <div
                            class="max-w-[85%] overflow-hidden rounded-2xl rounded-br-sm bg-primary text-primary-foreground"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-xs text-primary-foreground/80 hover:bg-black/10"
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
                                class="px-4 pb-3 text-sm whitespace-pre-wrap"
                                :class="{
                                    'line-clamp-4': !isPromptExpanded(log.id),
                                }"
                            >
                                {{ log.prompt }}
                            </p>
                        </div>
                    </div>

                    <!-- Agent response (collapsible) -->
                    <div class="flex justify-start">
                        <div
                            class="max-w-[85%] overflow-hidden rounded-2xl rounded-bl-sm border bg-background"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-xs text-muted-foreground hover:bg-muted/50"
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
                                            ? 'text-muted-foreground'
                                            : 'font-medium text-destructive'
                                    "
                                >
                                    {{ log.success ? "Válasz" : "Hiba" }}
                                </span>
                                <span class="ml-auto">{{
                                    formatDate(log.created_at)
                                }}</span>
                            </button>

                            <p
                                class="px-4 pb-3 text-sm whitespace-pre-wrap"
                                :class="{
                                    'line-clamp-4': !isResponseExpanded(log.id),
                                }"
                            >
                                {{ log.error ?? log.response ?? "—" }}
                            </p>
                        </div>
                    </div>
                </template>

                <div ref="chatEnd" />
            </div>

            <!-- Composer -->
            <div class="space-y-2">
                <Textarea
                    v-model="http.prompt"
                    rows="4"
                    placeholder="Kérdezz valamit…"
                    data-test="playground-prompt"
                    @keydown.enter.exact.prevent="submit"
                />
                <InputError :message="http.errors.prompt" />

                <Button
                    :disabled="
                        http.processing ||
                        http.prompt.trim() === '' ||
                        !http.api_key_id
                    "
                    data-test="playground-submit"
                    @click="submit"
                >
                    {{ http.processing ? "Futtatás…" : "Prompt futtatása" }}
                </Button>
            </div>
        </template>
    </div>
</template>
