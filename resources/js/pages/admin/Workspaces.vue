<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Check, FolderOpen, FolderPlus, Pencil, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import WorkspaceController from '@/actions/App/Http/Controllers/Admin/WorkspaceController';
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
import { index, show } from '@/routes/admin/workspaces';

type Workspace = {
    id: number;
    name: string;
    path: string;
    relative_path: string;
    api_keys_count: number;
    created_at: string | null;
};

defineProps<{
    base: string;
    workspaces: Workspace[];
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

const createOpen = ref(false);
const editing = ref<Workspace | null>(null);
</script>

<template>
    <Head title="Munkaterületek" />

    <h1 class="sr-only">Munkaterületek</h1>

    <div class="nova-page space-y-6 px-6 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                eyebrow="Tároló"
                title="Munkaterületek"
                description="Egy munkaterület egy név és egy fizikai mappa. Mentéskor a mappa létrejön, ha még nem létezik, és a claude parancs ebben a mappában fut."
            />

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button data-test="create-workspace-button">
                        <FolderPlus /> Új munkaterület</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="WorkspaceController.store.form()"
                        reset-on-success
                        :options="{ preserveScroll: true }"
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                        @success="createOpen = false"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle
                                >Új munkaterület létrehozása</DialogTitle
                            >
                            <DialogDescription>
                                Add meg a nevet és a mappa nevét. A mappa a
                                megadott bázismappa alatt jön létre, ha még nem
                                létezik.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="create-name">Név</Label>
                            <Input
                                id="create-name"
                                name="name"
                                placeholder="pl. Dokumentáció"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-folder">Mappa</Label>
                            <div
                                class="flex h-9 items-center overflow-hidden rounded-sm border border-input bg-surface-100 focus-within:border-plasma"
                            >
                                <span
                                    class="nova-code shrink-0 self-stretch border-r border-border bg-surface-200 px-3 leading-9 text-ink-faint"
                                    >{{ base }}/</span
                                >
                                <input
                                    id="create-folder"
                                    name="folder"
                                    placeholder="pl. project-1"
                                    class="nova-code w-full bg-transparent px-3 text-ink outline-none placeholder:text-ink-faint"
                                    required
                                />
                            </div>
                            <InputError :message="errors.folder" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" type="button">
                                    <X /> Mégse</Button
                                >
                            </DialogClose>

                            <Button type="submit" :disabled="processing">
                                <FolderPlus />
                                Munkaterület létrehozása
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="workspaces.length === 0"
            class="rounded-lg border border-border bg-surface-100 p-8 text-center text-sm text-ink-muted"
        >
            Még nincs munkaterületed.
        </div>

        <div
            v-else
            class="overflow-hidden rounded-lg border border-border bg-surface-100 shadow-raised"
        >
            <div
                class="flex items-center justify-between border-b border-border bg-surface-200 px-5 py-3"
            >
                <span class="nova-label">Mappák</span>
                <span class="nova-label text-ink-muted"
                    >{{ workspaces.length }} munkaterület</span
                >
            </div>

            <div class="divide-y divide-border">
                <div
                    v-for="workspace in workspaces"
                    :key="workspace.id"
                    class="flex items-start justify-between gap-4 p-5 transition-colors duration-[120ms] ease-out hover:bg-surface-200"
                >
                    <div class="min-w-0 space-y-1.5">
                        <Link
                            :href="show(workspace.id)"
                            class="flex items-center gap-2 font-medium text-ink transition-colors duration-[120ms] ease-out hover:text-plasma"
                        >
                            <FolderOpen class="h-4 w-4 shrink-0 text-plasma" />
                            {{ workspace.name }}
                        </Link>
                        <p class="nova-code truncate text-ink-muted">
                            {{ workspace.path }}
                        </p>
                        <p class="nova-label">
                            {{ workspace.api_keys_count }} API kulcs
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <Button variant="secondary" size="sm" as-child>
                            <Link :href="show(workspace.id)">
                                <FolderOpen /> Megnyitás</Link
                            >
                        </Button>

                        <Dialog
                            :open="editing?.id === workspace.id"
                            @update:open="
                                (open) => (editing = open ? workspace : null)
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
                                        WorkspaceController.update.form(
                                            workspace.id,
                                        )
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ errors, processing }"
                                    @success="editing = null"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Munkaterület
                                            szerkesztése</DialogTitle
                                        >
                                        <DialogDescription>
                                            Az új mappa a bázismappa alatt jön
                                            létre, ha még nem létezik.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-2">
                                        <Label
                                            :for="`edit-name-${workspace.id}`"
                                            >Név</Label
                                        >
                                        <Input
                                            :id="`edit-name-${workspace.id}`"
                                            name="name"
                                            :default-value="workspace.name"
                                            required
                                        />
                                        <InputError :message="errors.name" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label
                                            :for="`edit-folder-${workspace.id}`"
                                            >Mappa</Label
                                        >
                                        <div
                                            class="flex h-9 items-center overflow-hidden rounded-sm border border-input bg-surface-100 focus-within:border-plasma"
                                        >
                                            <span
                                                class="nova-code shrink-0 self-stretch border-r border-border bg-surface-200 px-3 leading-9 text-ink-faint"
                                                >{{ base }}/</span
                                            >
                                            <input
                                                :id="`edit-folder-${workspace.id}`"
                                                name="folder"
                                                :value="workspace.relative_path"
                                                class="nova-code w-full bg-transparent px-3 text-ink outline-none"
                                                required
                                            />
                                        </div>
                                        <InputError :message="errors.folder" />
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
                                        WorkspaceController.destroy.form(
                                            workspace.id,
                                        )
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ processing }"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Törlöd a(z) "{{ workspace.name }}"
                                            munkaterületet?</DialogTitle
                                        >
                                        <DialogDescription>
                                            Csak a munkaterület rekord törlődik,
                                            a fizikai mappa és a benne lévő
                                            fileok megmaradnak. Az ezt használó
                                            API kulcsok munkaterület nélkül
                                            maradnak.
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
                                            Munkaterület törlése
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
