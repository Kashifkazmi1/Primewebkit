"use client";

import * as TabsPrimitive from "@radix-ui/react-tabs";
import { cn } from "@/lib/utils";

export const Tabs = TabsPrimitive.Root;

export function TabsList({ className, ...props }: TabsPrimitive.TabsListProps) {
  return (
    <TabsPrimitive.List
      className={cn(
        "inline-flex h-11 items-center gap-1 rounded-full border border-border bg-surface-2 p-1",
        className,
      )}
      {...props}
    />
  );
}

export function TabsTrigger({ className, ...props }: TabsPrimitive.TabsTriggerProps) {
  return (
    <TabsPrimitive.Trigger
      className={cn(
        "inline-flex h-9 items-center justify-center whitespace-nowrap rounded-full px-4 text-sm font-medium text-muted-foreground transition-all data-[state=active]:bg-surface data-[state=active]:text-foreground data-[state=active]:shadow-elevated focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring",
        className,
      )}
      {...props}
    />
  );
}

export function TabsContent({ className, ...props }: TabsPrimitive.TabsContentProps) {
  return <TabsPrimitive.Content className={cn("mt-6 animate-fade-in focus-visible:outline-none", className)} {...props} />;
}
