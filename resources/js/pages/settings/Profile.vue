<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profil beállítások',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase() ?? '')
        .join('');
}
</script>

<template>
    <Head title="Profil beállítások" />

    <h1 class="sr-only">Profil beállítások</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profil"
            description="Név és email cím módosítása"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="avatar">Igazolványkép</Label>
                <div class="flex items-center gap-3">
                    <Avatar class="size-14 shrink-0">
                        <AvatarImage
                            v-if="user.avatar_url"
                            :src="user.avatar_url"
                            :alt="user.name"
                        />
                        <AvatarFallback
                            class="bg-surface-200 text-base font-medium text-ink-muted"
                        >
                            {{ initials(user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <input
                        id="avatar"
                        type="file"
                        name="avatar"
                        accept="image/*"
                        class="block w-full text-sm text-ink-muted file:mr-3 file:rounded-md file:border-0 file:bg-surface-200 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ink hover:file:bg-surface-300"
                    />
                </div>
                <p class="text-xs text-ink-faint">
                    Új kép feltöltése lecseréli a jelenlegit.
                </p>
                <InputError class="mt-2" :message="errors.avatar" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Név</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Teljes név"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email cím</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email cím"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-profile-button"
                >
                    <Check />
                    Mentés
                </Button>
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
