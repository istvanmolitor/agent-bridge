<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Check, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import PromptController from '@/actions/App/Http/Controllers/Admin/PromptController';
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
import { index } from '@/routes/admin/prompts';

type Prompt = {
    id: number;
    name: string;
    content: string;
    created_at: string | null;
};

defineProps<{
    prompts: Prompt[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Promptok',
                href: index(),
            },
        ],
    },
});

const createOpen = ref(false);
const editing = ref<Prompt | null>(null);
</script>

<template>
    <Head title="Promptok" />

    <h1 class="sr-only">Promptok</h1>

    <div class="nova-page space-y-6 px-6 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                eyebrow="Könyvtár"
                title="Promptok"
                description="Elnevezett promptok, amelyeket az API-n keresztül név alapján lekérhetsz."
            />

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button data-test="create-prompt-button">
                        <Plus /> Új prompt</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="PromptController.store.form()"
                        reset-on-success
                        :options="{ preserveScroll: true }"
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                        @success="createOpen = false"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Új prompt létrehozása</DialogTitle>
                            <DialogDescription>
                                Add meg a prompt nevét és tartalmát. A név
                                egyedi.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="create-name">Név</Label>
                            <Input
                                id="create-name"
                                name="name"
                                placeholder="pl. udvozlo-szoveg"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-content">Tartalom</Label>
                            <Textarea
                                id="create-content"
                                name="content"
                                rows="8"
                                placeholder="A prompt szövege…"
                                required
                            />
                            <InputError :message="errors.content" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" type="button">
                                    <X /> Mégse</Button
                                >
                            </DialogClose>

                            <Button type="submit" :disabled="processing">
                                <Plus />
                                Prompt létrehozása
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="prompts.length === 0"
            class="rounded-lg border border-border bg-surface-100 p-8 text-center text-sm text-ink-muted"
        >
            Még nincs promptod.
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
                    >{{ prompts.length }} prompt</span
                >
            </div>

            <div class="divide-y divide-border">
                <div
                    v-for="prompt in prompts"
                    :key="prompt.id"
                    class="flex items-start justify-between gap-4 p-5 transition-colors duration-[120ms] ease-out hover:bg-surface-200"
                >
                    <div class="min-w-0 space-y-1.5">
                        <p class="nova-code font-medium text-ink">
                            {{ prompt.name }}
                        </p>
                        <p class="line-clamp-2 text-sm text-ink-muted">
                            {{ prompt.content }}
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <Dialog
                            :open="editing?.id === prompt.id"
                            @update:open="
                                (open) => (editing = open ? prompt : null)
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
                                        PromptController.update.form(prompt.id)
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ errors, processing }"
                                    @success="editing = null"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Prompt szerkesztése</DialogTitle
                                        >
                                        <DialogDescription>
                                            Módosítsd a prompt nevét vagy
                                            tartalmát.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-2">
                                        <Label :for="`edit-name-${prompt.id}`"
                                            >Név</Label
                                        >
                                        <Input
                                            :id="`edit-name-${prompt.id}`"
                                            name="name"
                                            :default-value="prompt.name"
                                            required
                                        />
                                        <InputError :message="errors.name" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label
                                            :for="`edit-content-${prompt.id}`"
                                            >Tartalom</Label
                                        >
                                        <Textarea
                                            :id="`edit-content-${prompt.id}`"
                                            name="content"
                                            rows="8"
                                            :default-value="prompt.content"
                                            required
                                        />
                                        <InputError :message="errors.content" />
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
                                        PromptController.destroy.form(prompt.id)
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ processing }"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Törlöd a(z) "{{ prompt.name }}"
                                            promptot?</DialogTitle
                                        >
                                        <DialogDescription>
                                            Ez a művelet nem vonható vissza.
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
                                            Prompt törlése
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
