import { ArrowUpRight } from "lucide-react";
import * as React from "react";
import { Button, type ButtonProps } from "@/components/ui/button";
import { env } from "@/lib/env";

/**
 * Every "upgrade" action in the app is a plain link to the hosted
 * checkout page — this app never builds its own payment UI.
 */
export function UpgradeButton({
  children = "Upgrade plan",
  showIcon = true,
  ...props
}: Omit<ButtonProps, "asChild"> & { children?: React.ReactNode; showIcon?: boolean }) {
  return (
    <Button asChild {...props}>
      <a href={env.upgradeUrl} target="_blank" rel="noopener noreferrer">
        {children}
        {showIcon && <ArrowUpRight className="size-4" />}
      </a>
    </Button>
  );
}
