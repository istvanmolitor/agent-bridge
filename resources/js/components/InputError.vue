<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p class="text-[13px] leading-[19px] text-signal-alert">
            {{ message }}
        </p>
    </div>
</template>
