<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Prefer the uploaded avatar URL, falling back to any legacy avatar field.
const avatarSrc = computed(
    () => props.user.avatar_url || props.user.avatar || '',
);
const showAvatar = computed(() => avatarSrc.value !== '');
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-sm">
        <AvatarImage v-if="showAvatar" :src="avatarSrc" :alt="user.name" />
        <AvatarFallback class="rounded-sm bg-surface-300 text-ink">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="nova-code truncate text-ink-faint">{{
            user.email
        }}</span>
    </div>
</template>
