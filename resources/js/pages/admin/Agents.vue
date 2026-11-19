<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Check, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import AgentController from '@/actions/App/Http/Controllers/Admin/AgentController';
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
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/admin/agents';

type Agent = {
    id: number;
    name: string;
    description: string | null;
    system_prompt: string;
    prompt_mode: 'append' | 'override';
    model: string | null;
    api_keys_count: number;
    created_at: string | null;
};

defineProps<{
    agents: Agent[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Ügynökök',
                href: index(),
            },
        ],
    },
});

const promptModeLabels: Record<Agent['prompt_mode'], string> = {
    append: 'Hozzáfűzés az alap viselkedéshez',
    override: 'Teljes felülírás',
};

const createOpen = ref(false);
const editing = ref<Agent | null>(null);
</script>

<template>
    <Head title="Ügynökök" />

    <h1 class="sr-only">Ügynökök</h1>

    <div class="nova-page space-y-6 px-6 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                eyebrow="Persona"
                title="Ügynökök"
                description="Írd le az ügynök személyiségét. Egy ügynökhöz több API kulcsot is rendelhetsz, mindegyik külön munkamenetet kap."
            />

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button data-test="create-agent-button">
                        <Plus /> Új ügynök</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="AgentController.store.form()"
                        reset-on-success
                        :options="{ preserveScroll: true }"
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                        @success="createOpen = false"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Új ügynök létrehozása</DialogTitle>
                            <DialogDescription>
                                Add meg az ügynök nevét és személyiségét.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="create-name">Név</Label>
                            <Input
                                id="create-name"
                                name="name"
                                placeholder="pl. Költő"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-description">Leírás</Label>
                            <Input
                                id="create-description"
                                name="description"
                                placeholder="Rövid összefoglaló (opcionális)"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-system_prompt"
                                >Személyiség (system prompt)</Label
                            >
                            <Textarea
                                id="create-system_prompt"
                                name="system_prompt"
                                rows="6"
                                placeholder="Írd le, hogyan viselkedjen az ügynök…"
                                required
                            />
                            <InputError :message="errors.system_prompt" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-prompt_mode">Persona mód</Label>
                            <Select name="prompt_mode" default-value="append">
                                <SelectTrigger id="create-prompt_mode">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="append">{{
                                        promptModeLabels.append
                                    }}</SelectItem>
                                    <SelectItem value="override">{{
                                        promptModeLabels.override
                                    }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.prompt_mode" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-model">Model (opcionális)</Label>
                            <Input
                                id="create-model"
                                name="model"
                                placeholder="pl. claude-opus-4-8"
                            />
                            <InputError :message="errors.model" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" type="button">
                                    <X /> Mégse</Button
                                >
                            </DialogClose>

                            <Button type="submit" :disabled="processing">
                                <Plus />
                                Ügynök létrehozása
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="agents.length === 0"
            class="rounded-lg border border-border bg-surface-100 p-8 text-center text-sm text-ink-muted"
        >
            Még nincs ügynököd.
        </div>

        <div
            v-else
            class="overflow-hidden rounded-lg border border-border bg-surface-100 shadow-raised"
        >
            <div
                class="flex items-center justify-between border-b border-border bg-surface-200 px-5 py-3"
            >
                <span class="nova-label">Nyilvántartás</span>
                <span class="nova-label text-ink-muted"
                    >{{ agents.length }} ügynök</span
                >
            </div>

            <div class="divide-y divide-border">
                <div
                    v-for="agent in agents"
                    :key="agent.id"
                    class="flex items-start justify-between gap-4 p-5 transition-colors duration-[120ms] ease-out hover:bg-surface-200"
                >
                    <div class="min-w-0 space-y-1.5">
                        <p class="font-medium text-ink">{{ agent.name }}</p>
                        <p
                            v-if="agent.description"
                            class="text-sm text-ink-muted"
                        >
                            {{ agent.description }}
                        </p>
                        <p class="nova-code text-ink-faint">
                            {{ promptModeLabels[agent.prompt_mode] }}
                            <template v-if="agent.model">
                                · {{ agent.model }}</template
                            >
                            · {{ agent.api_keys_count }} API kulcs
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <Dialog
                            :open="editing?.id === agent.id"
                            @update:open="
                                (open) => (editing = open ? agent : null)
                            "
                        >
                            <DialogTrigger as-child>
                                <Button variant="secondary" size="sm">
                                    <Pencil /> Szerkesztés</Button
                                >
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    v-bind="
                                        AgentController.update.form(agent.id)
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ errors, processing }"
                                    @success="editing = null"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Ügynök szerkesztése</DialogTitle
                                        >
                                        <DialogDescription>
                                            A módosítás nem érinti a meglévő,
                                            már futó munkameneteket.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-2">
                                        <Label :for="`edit-name-${agent.id}`"
                                            >Név</Label
                                        >
                                        <Input
                                            :id="`edit-name-${agent.id}`"
                                            name="name"
                                            :default-value="agent.name"
                                            required
                                        />
                                        <InputError :message="errors.name" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label
                                            :for="`edit-description-${agent.id}`"
                                            >Leírás</Label
                                        >
                                        <Input
                                            :id="`edit-description-${agent.id}`"
                                            name="description"
                                            :default-value="
                                                agent.description ?? ''
                                            "
                                        />
                                        <InputError
                                            :message="errors.description"
                                        />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label
                                            :for="`edit-system_prompt-${agent.id}`"
                                            >Személyiség (system prompt)</Label
                                        >
                                        <Textarea
                                            :id="`edit-system_prompt-${agent.id}`"
                                            name="system_prompt"
                                            rows="6"
                                            :default-value="agent.system_prompt"
                                            required
                                        />
                                        <InputError
                                            :message="errors.system_prompt"
                                        />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label
                                            :for="`edit-prompt_mode-${agent.id}`"
                                            >Persona mód</Label
                                        >
                                        <Select
                                            name="prompt_mode"
                                            :default-value="agent.prompt_mode"
                                        >
                                            <SelectTrigger
                                                :id="`edit-prompt_mode-${agent.id}`"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="append">{{
                                                    promptModeLabels.append
                                                }}</SelectItem>
                                                <SelectItem value="override">{{
                                                    promptModeLabels.override
                                                }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            :message="errors.prompt_mode"
                                        />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`edit-model-${agent.id}`"
                                            >Model (opcionális)</Label
                                        >
                                        <Input
                                            :id="`edit-model-${agent.id}`"
                                            name="model"
                                            :default-value="agent.model ?? ''"
                                            placeholder="pl. claude-opus-4-8"
                                        />
                                        <InputError :message="errors.model" />
                                    </div>

                                    <DialogFooter class="gap-2">
                                        <DialogClose as-child>
                                            <Button
                                                variant="secondary"
                                                type="button"
                                            >
                                                <X /> Mégse</Button
                                            >
                                        </DialogClose>

                                        <Button
                                            type="submit"
                                            :disabled="processing"
                                        >
                                            <Check /> Mentés</Button
                                        >
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>

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
                                        AgentController.destroy.form(agent.id)
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ processing }"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Törlöd a(z) "{{ agent.name }}"
                                            ügynököt?</DialogTitle
                                        >
                                        <DialogDescription>
                                            Az ügynökhöz tartozó API kulcsok és
                                            munkamenetek is törlődnek. Ez nem
                                            vonható vissza.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <DialogFooter class="gap-2">
                                        <DialogClose as-child>
                                            <Button
                                                variant="secondary"
                                                type="button"
                                            >
                                                <X /> Mégse</Button
                                            >
                                        </DialogClose>

                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            :disabled="processing"
                                        >
                                            <Trash2 />
                                            Ügynök törlése
                                        </Button>
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
