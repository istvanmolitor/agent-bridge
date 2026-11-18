<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
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
import { index } from '@/routes/admin/api-keys';

type ApiKey = {
    id: number;
    name: string;
    created_at: string | null;
    last_used_at: string | null;
};

defineProps<{
    apiKeys: ApiKey[];
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

const generatedApiKey = ref<string | null>(null);
const copied = ref(false);

function handleFlash(event: Event) {
    const flash = (event as CustomEvent).detail?.flash;
    const token = flash?.generatedApiKey as string | undefined;

    if (token) {
        generatedApiKey.value = token;
        copied.value = false;
    }
}

let unsubscribeFlash: (() => void) | undefined;

onMounted(() => {
    unsubscribeFlash = router.on('flash', handleFlash);
});
onUnmounted(() => unsubscribeFlash?.());

async function copyToken() {
    if (!generatedApiKey.value) {
        return;
    }

    await navigator.clipboard.writeText(generatedApiKey.value);
    copied.value = true;
}
</script>

<template>
    <Head title="API kulcsok" />

    <h1 class="sr-only">API kulcsok</h1>

    <div class="space-y-6 px-4 py-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="API kulcsok"
                description="Kulcsok generálása a prompt API külső alkalmazásokból történő hívásához"
            />

            <Dialog>
                <DialogTrigger as-child>
                    <Button data-test="create-api-key-button"
                        >Új kulcs</Button
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
                                Adj nevet a kulcsnak, hogy később
                                felismerhesd.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="name" class="sr-only">Név</Label>
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
                                    Mégse
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                :disabled="processing"
                                data-test="confirm-create-api-key-button"
                            >
                                Kulcs létrehozása
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div v-if="apiKeys.length === 0" class="text-sm text-muted-foreground">
            Még nincs API kulcsod.
        </div>

        <div v-else class="divide-y rounded-lg border">
            <div
                v-for="apiKey in apiKeys"
                :key="apiKey.id"
                class="flex items-center justify-between gap-4 p-4"
            >
                <div class="space-y-1">
                    <p class="font-medium">{{ apiKey.name }}</p>
                    <p class="text-sm text-muted-foreground">
                        Létrehozva: {{ formatDate(apiKey.created_at) }} ·
                        Utoljára használva: {{
                            formatDate(apiKey.last_used_at)
                        }}
                    </p>
                </div>

                <Dialog>
                    <DialogTrigger as-child>
                        <Button variant="destructive" size="sm"
                            >Törlés</Button
                        >
                    </DialogTrigger>
                    <DialogContent>
                        <Form
                            v-bind="ApiKeyController.destroy.form(apiKey.id)"
                            :options="{ preserveScroll: true }"
                            class="space-y-6"
                            v-slot="{ processing }"
                        >
                            <DialogHeader class="space-y-3">
                                <DialogTitle
                                    >Törlöd a(z) "{{
                                        apiKey.name
                                    }}" kulcsot?</DialogTitle
                                >
                                <DialogDescription>
                                    Az ezt a kulcsot használó alkalmazások
                                    azonnal elveszítik a hozzáférést. Ez nem
                                    vonható vissza.
                                </DialogDescription>
                            </DialogHeader>

                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <Button variant="secondary"
                                        >Mégse</Button
                                    >
                                </DialogClose>

                                <Button
                                    type="submit"
                                    variant="destructive"
                                    :disabled="processing"
                                >
                                    Kulcs törlése
                                </Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </div>

    <Dialog
        :open="generatedApiKey !== null"
        @update:open="(open) => !open && (generatedApiKey = null)"
    >
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>Az új API kulcsod</DialogTitle>
                <DialogDescription>
                    Másold ki most a kulcsot — többé nem fog megjelenni.
                </DialogDescription>
            </DialogHeader>

            <div class="flex items-center gap-2">
                <Input :model-value="generatedApiKey ?? ''" readonly />
                <Button variant="secondary" @click="copyToken">
                    {{ copied ? 'Kimásolva' : 'Másolás' }}
                </Button>
            </div>

            <DialogFooter>
                <DialogClose as-child>
                    <Button @click="generatedApiKey = null">Kész</Button>
                </DialogClose>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
