<script lang="ts" setup>
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <ol
    data-slot="breadcrumb-list"
    :class="cn('text-ink-faint flex flex-wrap items-center gap-1.5 font-mono text-[11px] font-semibold tracking-[0.14em] break-words uppercase sm:gap-2.5', props.class)"
  >
    <slot />
  </ol>
</template>
