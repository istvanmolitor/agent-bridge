<script setup lang="ts">
import { Form, Head, Link } from "@inertiajs/vue3";
import { FolderOpen } from "@lucide/vue";
import { ref } from "vue";
import WorkspaceController from "@/actions/App/Http/Controllers/Admin/WorkspaceController";
import Heading from "@/components/Heading.vue";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { index, show } from "@/routes/admin/workspaces";

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
                title: "Munkaterületek",
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

    <div class="space-y-6 px-4 py-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Munkaterületek"
                description="Egy munkaterület egy név és egy fizikai mappa. Mentéskor a mappa létrejön, ha még nem létezik, és a claude parancs ebben a mappában fut."
            />

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button data-test="create-workspace-button"
                        >Új munkaterület</Button
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
                                class="flex items-center rounded-md border focus-within:ring-1 focus-within:ring-ring"
                            >
                                <span
                                    class="shrink-0 border-r bg-muted px-2 py-1 font-mono text-xs text-muted-foreground"
                                    >{{ base }}/</span
                                >
                                <input
                                    id="create-folder"
                                    name="folder"
                                    placeholder="pl. project-1"
                                    class="w-full bg-transparent px-2 py-1 font-mono text-xs outline-none"
                                    required
                                />
                            </div>
                            <InputError :message="errors.folder" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" type="button"
                                    >Mégse</Button
                                >
                            </DialogClose>

                            <Button type="submit" :disabled="processing">
                                Munkaterület létrehozása
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="workspaces.length === 0"
            class="text-sm text-muted-foreground"
        >
            Még nincs munkaterületed.
        </div>

        <div v-else class="divide-y rounded-lg border">
            <div
                v-for="workspace in workspaces"
                :key="workspace.id"
                class="flex items-start justify-between gap-4 p-4"
            >
                <div class="min-w-0 space-y-1">
                    <Link
                        :href="show(workspace.id)"
                        class="flex items-center gap-2 font-medium hover:underline"
                    >
                        <FolderOpen
                            class="h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        {{ workspace.name }}
                    </Link>
                    <p class="truncate font-mono text-xs text-muted-foreground">
                        {{ workspace.path }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ workspace.api_keys_count }} API kulcs
                    </p>
                </div>

                <div class="flex shrink-0 gap-2">
                    <Button variant="secondary" size="sm" as-child>
                        <Link :href="show(workspace.id)">Megnyitás</Link>
                    </Button>

                    <Dialog
                        :open="editing?.id === workspace.id"
                        @update:open="
                            (open) => (editing = open ? workspace : null)
                        "
                    >
                        <DialogTrigger as-child>
                            <Button variant="secondary" size="sm"
                                >Szerkesztés</Button
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
                                        >Munkaterület szerkesztése</DialogTitle
                                    >
                                    <DialogDescription>
                                        Az új mappa a bázismappa alatt jön
                                        létre, ha még nem létezik.
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="grid gap-2">
                                    <Label :for="`edit-name-${workspace.id}`"
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
                                    <Label :for="`edit-folder-${workspace.id}`"
                                        >Mappa</Label
                                    >
                                    <div
                                        class="flex items-center rounded-md border focus-within:ring-1 focus-within:ring-ring"
                                    >
                                        <span
                                            class="shrink-0 border-r bg-muted px-2 py-1 font-mono text-xs text-muted-foreground"
                                            >{{ base }}/</span
                                        >
                                        <input
                                            :id="`edit-folder-${workspace.id}`"
                                            name="folder"
                                            :value="workspace.relative_path"
                                            class="w-full bg-transparent px-2 py-1 font-mono text-xs outline-none"
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
                                            >Mégse</Button
                                        >
                                    </DialogClose>

                                    <Button type="submit" :disabled="processing"
                                        >Mentés</Button
                                    >
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>

                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive" size="sm"
                                >Törlés</Button
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
                                        Csak a munkaterület rekord törlődik, a
                                        fizikai mappa és a benne lévő fileok
                                        megmaradnak. Az ezt használó API kulcsok
                                        munkaterület nélkül maradnak.
                                    </DialogDescription>
                                </DialogHeader>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button
                                            variant="secondary"
                                            type="button"
                                            >Mégse</Button
                                        >
                                    </DialogClose>

                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        :disabled="processing"
                                    >
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
</template>
