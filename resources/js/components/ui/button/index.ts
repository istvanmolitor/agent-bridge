import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

// Nova: gombfelirat display családdal, nagybetűsítve, 0.08em betűközzel.
// A fókuszgyűrűt a base réteg :focus-visible szabálya rajzolja (2px tömör,
// 2px réssel), ezért itt nincs outline-none és nincs külön ring utility.
export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-sm border border-transparent font-display text-sm font-semibold tracking-[0.08em] uppercase transition-[background-color,border-color,box-shadow,color] duration-[120ms] ease-out disabled:pointer-events-none disabled:opacity-45 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "bg-primary text-primary-foreground hover:shadow-glow-plasma",
        destructive:
          "bg-destructive text-destructive-foreground hover:shadow-glow-alert",
        outline:
          "border-border-strong bg-transparent text-ink hover:bg-surface-200",
        secondary:
          "border-border-strong bg-surface-200 text-ink hover:bg-surface-300",
        ghost:
          "text-ink-muted hover:bg-surface-200 hover:text-ink",
        link: "text-primary normal-case tracking-normal underline-offset-4 hover:underline",
      },
      size: {
        "default": "h-9 px-4 has-[>svg]:px-3",
        "sm": "h-7 gap-1.5 px-3 text-xs has-[>svg]:px-2.5",
        "lg": "h-11 px-6 text-[15px] has-[>svg]:px-4",
        "icon": "size-9",
        "icon-sm": "size-7",
        "icon-lg": "size-11",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
