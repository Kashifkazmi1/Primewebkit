import { ArrowUpRight } from "lucide-react";
import * as React from "react";
import { Button, type ButtonProps } from "@/components/ui/button";
import { env } from "@/lib/env";

/**
 * Every "upgrade" action in the app is a plain link to the hosted
 * checkout page — this app never builds its own payment UI. Pass
 * `plan` (a plan slug, e.g. "starter") to route the checkout page to
 * that specific plan via a query param.
 */
export function UpgradeButton({
  children = "Upgrade plan",
  showIcon = true,
  plan,
  ...props
}: Omit<ButtonProps, "asChild"> & { children?: React.ReactNode; showIcon?: boolean; plan?: string }) {
  const href = plan ? `${env.upgradeUrl}?plan=${encodeURIComponent(plan)}` : env.upgradeUrl;

  return (
    <Button asChild {...props}>
      <a href={href} target="_blank" rel="noopener noreferrer">
        {children}
        {showIcon && <ArrowUpRight className="size-4" />}
      </a>
    </Button>
  );
}
