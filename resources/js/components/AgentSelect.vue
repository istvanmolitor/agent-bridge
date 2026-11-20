<script lang="ts">
export default {
    inheritAttrs: false,
};
</script>

<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
} from '@/components/ui/select';

export type AgentOption = {
    id: number;
    name: string;
    avatar_url?: string | null;
};

const props = withDefaults(
    defineProps<{
        agents: AgentOption[];
        /** Hidden input name for native/Inertia form submission. */
        name?: string;
        /** Initial value when used without v-model (e.g. inside a Form). */
        defaultValue?: string;
        placeholder?: string;
        /** Render a leading "all agents" option (filter use). */
        includeAll?: boolean;
        allValue?: string;
        allLabel?: string;
        /** Forwarded to the trigger element. */
        triggerId?: string;
        triggerClass?: string;
        emptyText?: string;
    }>(),
    {
        placeholder: 'Válassz ügynököt…',
        includeAll: false,
        allValue: 'all',
        allLabel: 'Összes ügynök',
        emptyText: 'Még nincs ügynököd.',
    },
);

const model = defineModel<string | undefined>();

// Seed from defaultValue when the parent does not bind v-model (form usage).
if (model.value === undefined && props.defaultValue !== undefined) {
    model.value = props.defaultValue;
}

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase() ?? '')
        .join('');
}

const selectedAgent = computed<AgentOption | null>(() => {
    const value = model.value;
    if (value == null) {
        return null;
    }
    return props.agents.find((agent) => String(agent.id) === value) ?? null;
});

const isAllSelected = computed(
    () => props.includeAll && model.value === props.allValue,
);
</script>

<template>
    <Select v-model="model" :name="name">
        <SelectTrigger :id="triggerId" :class="triggerClass" v-bind="$attrs">
            <span v-if="selectedAgent" class="flex items-center gap-2">
                <Avatar class="size-5 shrink-0">
                    <AvatarImage
                        v-if="selectedAgent.avatar_url"
                        :src="selectedAgent.avatar_url"
                        :alt="selectedAgent.name"
                    />
                    <AvatarFallback
                        class="bg-surface-200 text-[10px] font-medium text-ink-muted"
                    >
                        {{ initials(selectedAgent.name) }}
                    </AvatarFallback>
                </Avatar>
                <span class="truncate">{{ selectedAgent.name }}</span>
            </span>
            <span v-else-if="isAllSelected" class="truncate">
                {{ allLabel }}
            </span>
            <span v-else class="truncate text-ink-faint">
                {{ placeholder }}
            </span>
        </SelectTrigger>

        <SelectContent>
            <SelectItem v-if="includeAll" :value="allValue">
                {{ allLabel }}
            </SelectItem>

            <SelectItem
                v-for="agent in agents"
                :key="agent.id"
                :value="String(agent.id)"
            >
                <Avatar class="size-6 shrink-0">
                    <AvatarImage
                        v-if="agent.avatar_url"
                        :src="agent.avatar_url"
                        :alt="agent.name"
                    />
                    <AvatarFallback
                        class="bg-surface-200 text-[11px] font-medium text-ink-muted"
                    >
                        {{ initials(agent.name) }}
                    </AvatarFallback>
                </Avatar>
                <span class="truncate">{{ agent.name }}</span>
            </SelectItem>

            <p
                v-if="agents.length === 0"
                class="px-2 py-1.5 text-sm text-ink-muted"
            >
                {{ emptyText }}
            </p>
        </SelectContent>
    </Select>
</template>
