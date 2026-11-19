<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Check, Pencil, Trash2, UserPlus, X } from '@lucide/vue';
import { ref } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { index } from '@/routes/admin/users';
import { usePage } from '@inertiajs/vue3';

type User = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    email_verified_at: string | null;
    created_at: string | null;
};

defineProps<{
    users: User[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Felhasználók',
                href: index(),
            },
        ],
    },
});

const page = usePage();
const currentUserId = page.props.auth.user.id;

const createOpen = ref(false);
const editing = ref<User | null>(null);
</script>

<template>
    <Head title="Felhasználók" />

    <h1 class="sr-only">Felhasználók</h1>

    <div class="nova-page space-y-6 px-6 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                eyebrow="Hozzáférés"
                title="Felhasználók"
                description="Hozz létre és kezelj felhasználói fiókokat. Admin jogosultsággal a felhasználó eléri az adminisztrációs felületet."
            />

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button data-test="create-user-button">
                        <UserPlus /> Új felhasználó</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="UserController.store.form()"
                        reset-on-success
                        :options="{ preserveScroll: true }"
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                        @success="createOpen = false"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle
                                >Új felhasználó létrehozása</DialogTitle
                            >
                            <DialogDescription>
                                Add meg a felhasználó adatait. A fiók azonnal
                                aktív lesz.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="create-name">Név</Label>
                            <Input
                                id="create-name"
                                name="name"
                                placeholder="pl. Kovács Anna"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-email">E-mail</Label>
                            <Input
                                id="create-email"
                                name="email"
                                type="email"
                                placeholder="pl. anna@example.com"
                                required
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-password">Jelszó</Label>
                            <Input
                                id="create-password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                required
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox id="create-is_admin" name="is_admin" />
                            <Label
                                for="create-is_admin"
                                class="font-sans text-sm font-normal tracking-normal text-ink normal-case"
                                >Admin jogosultság</Label
                            >
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" type="button">
                                    <X /> Mégse</Button
                                >
                            </DialogClose>

                            <Button type="submit" :disabled="processing">
                                <UserPlus />
                                Felhasználó létrehozása
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="users.length === 0"
            class="rounded-lg border border-border bg-surface-100 p-8 text-center text-sm text-ink-muted"
        >
            Még nincs felhasználó.
        </div>

        <div
            v-else
            class="overflow-hidden rounded-lg border border-border bg-surface-100 shadow-raised"
        >
            <div
                class="flex items-center justify-between border-b border-border bg-surface-200 px-5 py-3"
            >
                <span class="nova-label">Fiókok</span>
                <span class="nova-label text-ink-muted"
                    >{{ users.length }} felhasználó</span
                >
            </div>

            <div class="divide-y divide-border">
                <div
                    v-for="user in users"
                    :key="user.id"
                    class="flex items-start justify-between gap-4 p-5 transition-colors duration-[120ms] ease-out hover:bg-surface-200"
                >
                    <div class="min-w-0 space-y-1.5">
                        <p class="flex items-center gap-2 font-medium text-ink">
                            {{ user.name }}
                            <span
                                v-if="user.is_admin"
                                class="nova-label rounded-sm border border-plasma bg-plasma-deep px-1.5 py-0.5 text-plasma"
                                >Admin</span
                            >
                        </p>
                        <p class="nova-code text-ink-muted">
                            {{ user.email }}
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <Dialog
                            :open="editing?.id === user.id"
                            @update:open="
                                (open) => (editing = open ? user : null)
                            "
                        >
                            <DialogTrigger as-child>
                                <Button variant="secondary" size="sm">
                                    <Pencil /> Szerkesztés</Button
                                >
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    v-bind="UserController.update.form(user.id)"
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ errors, processing }"
                                    @success="editing = null"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Felhasználó
                                            szerkesztése</DialogTitle
                                        >
                                        <DialogDescription>
                                            Hagyd üresen a jelszó mezőt, ha nem
                                            szeretnéd megváltoztatni.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-2">
                                        <Label :for="`edit-name-${user.id}`"
                                            >Név</Label
                                        >
                                        <Input
                                            :id="`edit-name-${user.id}`"
                                            name="name"
                                            :default-value="user.name"
                                            required
                                        />
                                        <InputError :message="errors.name" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`edit-email-${user.id}`"
                                            >E-mail</Label
                                        >
                                        <Input
                                            :id="`edit-email-${user.id}`"
                                            name="email"
                                            type="email"
                                            :default-value="user.email"
                                            required
                                        />
                                        <InputError :message="errors.email" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`edit-password-${user.id}`"
                                            >Új jelszó</Label
                                        >
                                        <Input
                                            :id="`edit-password-${user.id}`"
                                            name="password"
                                            type="password"
                                            autocomplete="new-password"
                                            placeholder="Változatlan, ha üres"
                                        />
                                        <InputError
                                            :message="errors.password"
                                        />
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Checkbox
                                            :id="`edit-is_admin-${user.id}`"
                                            name="is_admin"
                                            :default-value="user.is_admin"
                                            :disabled="
                                                user.id === currentUserId
                                            "
                                        />
                                        <Label
                                            :for="`edit-is_admin-${user.id}`"
                                            class="font-sans text-sm font-normal tracking-normal text-ink normal-case"
                                            >Admin jogosultság</Label
                                        >
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

                        <Dialog v-if="user.id !== currentUserId">
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
                                        UserController.destroy.form(user.id)
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ processing }"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Törlöd a(z) "{{ user.name }}"
                                            felhasználót?</DialogTitle
                                        >
                                        <DialogDescription>
                                            A felhasználó fiókja véglegesen
                                            törlődik. Ez nem vonható vissza.
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
                                            Felhasználó törlése
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
