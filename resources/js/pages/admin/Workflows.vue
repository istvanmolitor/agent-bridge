<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Check, Pencil, Plus, Trash2, Workflow as WorkflowIcon, X } from '@lucide/vue';
import { ref } from 'vue';
import WorkflowController from '@/actions/App/Http/Controllers/Admin/WorkflowController';
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
import { index, show } from '@/routes/admin/workflows';

type Workflow = {
    id: number;
    name: string;
    description: string | null;
    nodes_count: number;
    connections_count: number;
    created_at: string | null;
};

defineProps<{
    workflows: Workflow[];
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

const createOpen = ref(false);
const editing = ref<Workflow | null>(null);
</script>

<template>
    <Head title="Folyamatok" />

    <h1 class="sr-only">Folyamatok</h1>

    <div class="nova-page space-y-6 px-6 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                eyebrow="Workflow"
                title="Folyamatok"
                description="Hozz létre folyamatokat, majd nyisd meg a szerkesztőt, ahol node-okból és kapcsolatokból építheted fel a gráfot."
            />

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button data-test="create-workflow-button">
                        <Plus /> Új folyamat</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="WorkflowController.store.form()"
                        reset-on-success
                        :options="{ preserveScroll: true }"
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                        @success="createOpen = false"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Új folyamat létrehozása</DialogTitle>
                            <DialogDescription>
                                Add meg a folyamat nevét és egy rövid leírását.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="create-name">Név</Label>
                            <Input
                                id="create-name"
                                name="name"
                                placeholder="pl. Cikk feldolgozás"
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

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" type="button">
                                    <X /> Mégse</Button
                                >
                            </DialogClose>

                            <Button type="submit" :disabled="processing">
                                <Plus />
                                Folyamat létrehozása
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="workflows.length === 0"
            class="rounded-lg border border-border bg-surface-100 p-8 text-center text-sm text-ink-muted"
        >
            Még nincs folyamatod.
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
                    >{{ workflows.length }} folyamat</span
                >
            </div>

            <div class="divide-y divide-border">
                <div
                    v-for="workflow in workflows"
                    :key="workflow.id"
                    class="flex items-start justify-between gap-4 p-5 transition-colors duration-[120ms] ease-out hover:bg-surface-200"
                >
                    <Link
                        :href="show(workflow.id)"
                        class="min-w-0 flex-1 space-y-1.5"
                    >
                        <p class="font-medium text-ink">{{ workflow.name }}</p>
                        <p
                            v-if="workflow.description"
                            class="text-sm text-ink-muted"
                        >
                            {{ workflow.description }}
                        </p>
                        <p class="nova-code text-ink-faint">
                            {{ workflow.nodes_count }} node ·
                            {{ workflow.connections_count }} kapcsolat
                        </p>
                    </Link>

                    <div class="flex shrink-0 gap-2">
                        <Button as-child variant="secondary" size="sm">
                            <Link :href="show(workflow.id)">
                                <WorkflowIcon /> Szerkesztő</Link
                            >
                        </Button>

                        <Dialog
                            :open="editing?.id === workflow.id"
                            @update:open="
                                (open) => (editing = open ? workflow : null)
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
                                        WorkflowController.update.form(
                                            workflow.id,
                                        )
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ errors, processing }"
                                    @success="editing = null"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Folyamat szerkesztése</DialogTitle
                                        >
                                        <DialogDescription>
                                            Módosítsd a folyamat nevét vagy
                                            leírását.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-2">
                                        <Label :for="`edit-name-${workflow.id}`"
                                            >Név</Label
                                        >
                                        <Input
                                            :id="`edit-name-${workflow.id}`"
                                            name="name"
                                            :default-value="workflow.name"
                                            required
                                        />
                                        <InputError :message="errors.name" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label
                                            :for="`edit-description-${workflow.id}`"
                                            >Leírás</Label
                                        >
                                        <Input
                                            :id="`edit-description-${workflow.id}`"
                                            name="description"
                                            :default-value="
                                                workflow.description ?? ''
                                            "
                                        />
                                        <InputError
                                            :message="errors.description"
                                        />
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
                                        WorkflowController.destroy.form(
                                            workflow.id,
                                        )
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ processing }"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Törlöd a(z) "{{ workflow.name }}"
                                            folyamatot?</DialogTitle
                                        >
                                        <DialogDescription>
                                            A folyamat node-jai és azok
                                            kapcsolatai is törlődnek. Ez nem
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
                                            Folyamat törlése
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
