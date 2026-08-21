import { ArrowUpRight } from "lucide-react";
import * as React from "react";
import { Button, type ButtonProps } from "@/components/ui/button";
import { env } from "@/lib/env";

/**
 * Every "upgrade" action in the app is a plain link out to
 * pay.primewebkit.com (WordPress + WooCommerce + WooCommerce
 * Subscriptions + Stripe) — this app never builds its own payment UI
 * or talks to Stripe directly. `plan` (a plan slug, e.g. "starter")
 * and `billingCycle` tell that site's checkout-redirect page which
 * product to add to the cart; `email` (only available when the
 * visitor is already signed in) pre-fills the checkout email so the
 * WooCommerce order lands on the same account after the webhook syncs
 * it back (see WooCommerceSubscriptionSyncService on the backend).
 */
export function UpgradeButton({
  children = "Upgrade plan",
  showIcon = true,
  plan,
  billingCycle = "monthly",
  email,
  ...props
}: Omit<ButtonProps, "asChild"> & {
  children?: React.ReactNode;
  showIcon?: boolean;
  plan?: string;
  billingCycle?: "monthly" | "yearly";
  email?: string;
}) {
  const params = new URLSearchParams();
  if (plan) params.set("plan", plan);
  if (plan) params.set("cycle", billingCycle);
  if (email) params.set("email", email);
  const query = params.toString();
  const href = query ? `${env.upgradeUrl}?${query}` : env.upgradeUrl;

  return (
    <Button asChild {...props}>
      <a href={href} target="_blank" rel="noopener noreferrer">
        {children}
        {showIcon && <ArrowUpRight className="size-4" />}
      </a>
    </Button>
  );
}
