import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

// Nova StatusBadge: mono mikrofelirat, currentColor keret, surface-200 ágy.
// Az állapotot szín és szó együtt jelzi — a szín önmagában soha.
export const badgeVariants = cva(
  "inline-flex h-7 w-fit shrink-0 items-center justify-center gap-2 overflow-hidden rounded-sm border border-current bg-surface-200 px-3 font-mono text-[11px] font-semibold tracking-[0.14em] whitespace-nowrap uppercase [&>svg]:pointer-events-none [&>svg]:size-3",
  {
    variants: {
      variant: {
        default: "text-primary",
        secondary: "text-ink-muted",
        destructive: "text-signal-alert",
        outline: "bg-transparent text-ink-muted",
        ok: "text-signal-ok",
        warn: "text-signal-warn",
        alert: "text-signal-alert",
        live: "text-ember",
        idle: "text-ink-muted",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
