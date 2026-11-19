<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    Bot,
    Check,
    Copy,
    Folder,
    KeyRound,
    Plus,
    Trash2,
    X,
} from '@lucide/vue';
import { ref } from 'vue';
import ApiKeyController from '@/actions/App/Http/Controllers/Admin/ApiKeyController';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/admin/api-keys';

type ApiKey = {
    id: number;
    name: string;
    token: string;
    created_at: string | null;
    last_used_at: string | null;
    agent: { id: number; name: string } | null;
    workspace: { id: number; name: string } | null;
};

type Agent = {
    id: number;
    name: string;
};

type Workspace = {
    id: number;
    name: string;
};

defineProps<{
    apiKeys: ApiKey[];
    agents: Agent[];
    workspaces: Workspace[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'API kulcsok',
                href: index(),
            },
        ],
    },
});

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString('hu-HU') : 'Soha';
}

const copiedRowId = ref<number | null>(null);

async function copyRowToken(apiKey: ApiKey) {
    await navigator.clipboard.writeText(apiKey.token);
    copiedRowId.value = apiKey.id;
    window.setTimeout(() => {
        if (copiedRowId.value === apiKey.id) {
            copiedRowId.value = null;
        }
    }, 2000);
}
</script>

<template>
    <Head title="API kulcsok" />

    <h1 class="sr-only">API kulcsok</h1>

    <div class="nova-page space-y-6 px-6 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                eyebrow="Hozzáférés"
                title="API kulcsok"
                description="Kulcsok generálása a prompt API külső alkalmazásokból történő hívásához"
            />

            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        data-test="create-api-key-button"
                        :disabled="
                            agents.length === 0 || workspaces.length === 0
                        "
                        :title="
                            agents.length === 0
                                ? 'Előbb hozz létre egy ügynököt'
                                : workspaces.length === 0
                                  ? 'Előbb hozz létre egy munkaterületet'
                                  : undefined
                        "
                    >
                        <Plus /> Új kulcs</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ApiKeyController.store.form()"
                        reset-on-success
                        :options="{ preserveScroll: true }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Új API kulcs létrehozása</DialogTitle>
                            <DialogDescription>
                                Adj nevet a kulcsnak, hogy később felismerhesd.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="agent_id">Ügynök</Label>
                            <Select
                                name="agent_id"
                                :default-value="
                                    agents[0] ? String(agents[0].id) : undefined
                                "
                            >
                                <SelectTrigger id="agent_id">
                                    <SelectValue
                                        placeholder="Válassz ügynököt…"
                                    />
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
                            <InputError :message="errors.agent_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="workspace_id">Munkaterület</Label>
                            <Select
                                name="workspace_id"
                                :default-value="
                                    workspaces[0]
                                        ? String(workspaces[0].id)
                                        : undefined
                                "
                            >
                                <SelectTrigger id="workspace_id">
                                    <SelectValue
                                        placeholder="Válassz munkaterületet…"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="workspace in workspaces"
                                        :key="workspace.id"
                                        :value="String(workspace.id)"
                                    >
                                        {{ workspace.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.workspace_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="name">Név</Label>
                            <Input
                                id="name"
                                name="name"
                                placeholder="pl. CI szerver"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    <X />
                                    Mégse
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                :disabled="processing"
                                data-test="confirm-create-api-key-button"
                            >
                                <Plus />
                                Kulcs létrehozása
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="apiKeys.length === 0"
            class="rounded-lg border border-border bg-surface-100 p-8 text-center text-sm text-ink-muted"
        >
            Még nincs API kulcsod.
        </div>

        <div
            v-else
            class="overflow-hidden rounded-lg border border-border bg-surface-100 shadow-raised"
        >
            <div
                class="flex items-center justify-between border-b border-border bg-surface-200 px-5 py-3"
            >
                <span class="nova-label">Kiadott kulcsok</span>
                <span class="nova-label text-ink-muted"
                    >{{ apiKeys.length }} kulcs</span
                >
            </div>

            <div class="divide-y divide-border">
                <div
                    v-for="apiKey in apiKeys"
                    :key="apiKey.id"
                    class="flex items-start gap-4 p-5 transition-colors duration-[120ms] ease-out hover:bg-surface-200"
                >
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-sm border border-border bg-surface-200 text-plasma"
                    >
                        <KeyRound class="h-4 w-4" />
                    </div>

                    <div class="min-w-0 flex-1 space-y-3">
                        <div
                            class="flex flex-wrap items-center gap-x-2 gap-y-1"
                        >
                            <span class="font-medium text-ink">{{
                                apiKey.name
                            }}</span>
                            <span
                                v-if="apiKey.agent"
                                class="nova-code inline-flex items-center gap-1.5 rounded-sm border border-border bg-surface-200 px-2 py-0.5 text-ink-muted"
                            >
                                <Bot class="h-3 w-3" />
                                {{ apiKey.agent.name }}
                            </span>
                            <span
                                v-if="apiKey.workspace"
                                class="nova-code inline-flex items-center gap-1.5 rounded-sm border border-border bg-surface-200 px-2 py-0.5 text-ink-muted"
                            >
                                <Folder class="h-3 w-3" />
                                {{ apiKey.workspace.name }}
                            </span>
                        </div>

                        <div class="flex max-w-md items-center gap-2">
                            <Input
                                :model-value="apiKey.token"
                                readonly
                                class="text-ink-muted"
                            />
                            <Button
                                variant="secondary"
                                size="sm"
                                class="gap-1.5"
                                @click="copyRowToken(apiKey)"
                            >
                                <Check
                                    v-if="copiedRowId === apiKey.id"
                                    class="h-3.5 w-3.5"
                                />
                                <Copy v-else class="h-3.5 w-3.5" />
                                {{
                                    copiedRowId === apiKey.id
                                        ? 'Kimásolva'
                                        : 'Másolás'
                                }}
                            </Button>
                        </div>

                        <dl class="flex flex-wrap gap-x-8 gap-y-2">
                            <div>
                                <dt class="nova-label">Létrehozva</dt>
                                <dd class="nova-code text-ink-muted">
                                    {{ formatDate(apiKey.created_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="nova-label">Utoljára használva</dt>
                                <dd class="nova-code text-ink-muted">
                                    {{ formatDate(apiKey.last_used_at) }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <Dialog>
                        <DialogTrigger as-child>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-signal-alert hover:text-signal-alert"
                            >
                                <Trash2 /> Törlés</Button
                            >
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                v-bind="
                                    ApiKeyController.destroy.form(apiKey.id)
                                "
                                :options="{ preserveScroll: true }"
                                class="space-y-6"
                                v-slot="{ processing }"
                            >
                                <DialogHeader class="space-y-3">
                                    <DialogTitle
                                        >Törlöd a(z) "{{ apiKey.name }}"
                                        kulcsot?</DialogTitle
                                    >
                                    <DialogDescription>
                                        Az ezt a kulcsot használó alkalmazások
                                        azonnal elveszítik a hozzáférést. Ez nem
                                        vonható vissza.
                                    </DialogDescription>
                                </DialogHeader>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button variant="secondary">
                                            <X /> Mégse</Button
                                        >
                                    </DialogClose>

                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        :disabled="processing"
                                    >
                                        <Trash2 />
                                        Kulcs törlése
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </div>
    </div>
</template>
