import { Slot } from "@radix-ui/react-slot";
import { cva, type VariantProps } from "class-variance-authority";
import { Loader2 } from "lucide-react";
import * as React from "react";
import { cn } from "@/lib/utils";

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full text-sm font-medium transition-all duration-200 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 active:scale-[0.98]",
  {
    variants: {
      variant: {
        primary:
          "bg-primary text-primary-foreground shadow-elevated hover:bg-primary-hover hover:shadow-glow",
        secondary: "bg-surface-2 text-foreground border border-border hover:border-border-strong hover:bg-muted",
        outline: "border border-border bg-transparent hover:bg-muted hover:border-border-strong",
        ghost: "bg-transparent hover:bg-muted",
        danger: "bg-danger text-danger-foreground hover:opacity-90",
        link: "bg-transparent text-primary underline-offset-4 hover:underline p-0 h-auto rounded-none",
      },
      size: {
        sm: "h-8 px-3.5 text-xs",
        md: "h-10 px-5",
        lg: "h-12 px-7 text-base",
        icon: "size-10 p-0",
      },
    },
    defaultVariants: { variant: "primary", size: "md" },
  },
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  asChild?: boolean;
  isLoading?: boolean;
}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, asChild, isLoading, disabled, children, ...props }, ref) => {
    // Radix's Slot (used for asChild) requires exactly one child element,
    // so the loading spinner can only be injected in native <button> mode.
    if (asChild) {
      return (
        <Slot
          ref={ref}
          className={cn(buttonVariants({ variant, size }), className)}
          {...props}
        >
          {children}
        </Slot>
      );
    }

    return (
      <button
        ref={ref}
        className={cn(buttonVariants({ variant, size }), className)}
        disabled={disabled || isLoading}
        aria-busy={isLoading || undefined}
        {...props}
      >
        {isLoading && <Loader2 className="animate-spin" aria-hidden />}
        {children}
      </button>
    );
  },
);
Button.displayName = "Button";
