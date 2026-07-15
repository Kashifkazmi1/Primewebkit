"use client";

import * as AvatarPrimitive from "@radix-ui/react-avatar";
import { cn } from "@/lib/utils";

export function Avatar({ className, ...props }: AvatarPrimitive.AvatarProps) {
  return (
    <AvatarPrimitive.Root
      className={cn("relative flex size-9 shrink-0 overflow-hidden rounded-full", className)}
      {...props}
    />
  );
}

export function AvatarImage({ className, ...props }: AvatarPrimitive.AvatarImageProps) {
  return <AvatarPrimitive.Image className={cn("aspect-square h-full w-full object-cover", className)} {...props} />;
}

export function AvatarFallback({ className, ...props }: AvatarPrimitive.AvatarFallbackProps) {
  return (
    <AvatarPrimitive.Fallback
      className={cn(
        "flex h-full w-full items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary",
        className,
      )}
      {...props}
    />
  );
}
