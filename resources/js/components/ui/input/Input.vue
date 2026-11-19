<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :class="cn(
      'file:text-foreground placeholder:text-ink-faint selection:bg-plasma-deep selection:text-ink border-input bg-surface-100 text-ink h-9 w-full min-w-0 rounded-sm border px-3 font-mono text-[13px] transition-[border-color,box-shadow] duration-[120ms] ease-out file:inline-flex file:h-7 file:border-0 file:bg-transparent file:font-sans file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-45',
      'hover:not-disabled:border-plasma',
      'aria-invalid:border-destructive',
      props.class,
    )"
  >
</template>
