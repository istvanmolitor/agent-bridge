<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { edit, reset, run } from '@/routes/admin/playground';

type ApiKey = {
    id: number;
    name: string;
    claude_session_id: string | null;
    agent: { id: number; name: string } | null;
};

const props = defineProps<{
    apiKeys: ApiKey[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Playground',
                href: edit(),
            },
        ],
    },
});

const http = useHttp<{ prompt: string; api_key_id: string | null }>({
    prompt: '',
    api_key_id: props.apiKeys[0] ? String(props.apiKeys[0].id) : null,
});

const response = ref<unknown>(null);

const selectedKey = computed(
    () =>
        props.apiKeys.find((key) => String(key.id) === http.api_key_id) ?? null,
);

const hasSession = computed(() => selectedKey.value?.claude_session_id != null);

function submit() {
    http.post(run.url(), {
        onSuccess: (result: unknown) => {
            response.value = result;
            http.prompt = '';
            router.reload({ only: ['apiKeys'] });
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
        {
            preserveScroll: true,
            onSuccess: () => {
                response.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Playground" />

    <h1 class="sr-only">Playground</h1>

    <div class="space-y-6 px-4 py-6">
        <Heading
            variant="small"
            title="Prompt playground"
            description="Válassz egy API kulcsot — ez határozza meg az ügynököt és a vele folytatott munkamenetet — majd beszélgess vele."
        />

        <div v-if="apiKeys.length === 0" class="text-sm text-muted-foreground">
            Előbb hozz létre egy ügynököt és egy API kulcsot.
        </div>

        <template v-else>
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
                <InputError :message="http.errors.api_key_id" />
            </div>

            <div class="flex items-center gap-3 text-sm text-muted-foreground">
                <span v-if="hasSession"
                    >Folytatódó munkamenet ehhez a kulcshoz.</span
                >
                <span v-else>Új munkamenet indul a következő prompttal.</span>
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

            <div class="space-y-2">
                <Textarea
                    v-model="http.prompt"
                    rows="6"
                    placeholder="Kérdezz valamit…"
                    data-test="playground-prompt"
                />
                <InputError :message="http.errors.prompt" />
            </div>

            <Button
                :disabled="
                    http.processing ||
                    http.prompt.trim() === '' ||
                    !http.api_key_id
                "
                data-test="playground-submit"
                @click="submit"
            >
                {{ http.processing ? 'Futtatás…' : 'Prompt futtatása' }}
            </Button>

            <pre
                v-if="response"
                class="overflow-auto rounded-lg border bg-muted p-4 text-sm"
                data-test="playground-response"
                >{{ JSON.stringify(response, null, 2) }}</pre>
        </template>
    </div>
</template>
