<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

const props = defineProps<{
  defaultValue?: string
  modelValue?: string
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <textarea
    v-model="modelValue"
    data-slot="textarea"
    :class="cn(
      'placeholder:text-ink-faint selection:bg-plasma-deep selection:text-ink border-input bg-surface-100 text-ink min-h-24 w-full rounded-sm border px-3 py-2 font-mono text-[13px] leading-5 transition-[border-color,box-shadow] duration-[120ms] ease-out disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-45',
      'hover:not-disabled:border-plasma',
      'aria-invalid:border-destructive',
      props.class,
    )"
  />
</template>
