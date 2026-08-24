"use client";

import { Lock } from "lucide-react";
import { UpgradeButton } from "@/components/marketing/upgrade-button";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import type { Plan } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-context";
import { usePlanFeatures } from "@/lib/billing/use-plan-features";

const FEATURE_COPY: Record<keyof Plan["features"], { title: string; description: string }> = {
  lead_capture: {
    title: "Lead capture is a paid feature",
    description: "Upgrade to Starter or above to let your chatbot collect and save visitor leads automatically.",
  },
  conversation_history: {
    title: "Conversation history is a paid feature",
    description: "Upgrade to Starter or above to read full chat transcripts and export conversations.",
  },
  white_label: {
    title: "Removing PrimeWebKit branding is a Pro feature",
    description: "Upgrade to the Pro plan to hide \"Powered by PrimeWebKit\" on your widget.",
  },
  api_access: { title: "API access is a paid feature", description: "Upgrade your plan to generate API keys." },
  analytics: { title: "Analytics is a paid feature", description: "Upgrade your plan to unlock analytics." },
  custom_domain: { title: "Custom domain is a paid feature", description: "Upgrade your plan to use a custom domain." },
  priority_support: { title: "Priority support is a paid feature", description: "Upgrade your plan for priority support." },
  streaming: { title: "Streaming is a paid feature", description: "Upgrade your plan to enable streaming responses." },
};

/**
 * Wraps a dashboard feature that's gated behind a paid plan. Shows the
 * children once the current subscription's plan includes the feature;
 * otherwise shows an upgrade prompt instead. This is a UX convenience
 * only — the API enforces the same gate server-side regardless.
 */
export function FeatureGate({ feature, children }: { feature: keyof Plan["features"]; children: React.ReactNode }) {
  const { isReady, hasFeature } = usePlanFeatures();
  const { user } = useAuth();

  if (!isReady) return <Skeleton className="h-48" />;
  if (hasFeature(feature)) return <>{children}</>;

  const copy = FEATURE_COPY[feature];
  const plan = feature === "white_label" ? "pro" : "starter";

  return (
    <EmptyState
      icon={Lock}
      title={copy.title}
      description={copy.description}
      action={
        <UpgradeButton plan={plan} email={user?.email} showIcon={false}>
          Upgrade now
        </UpgradeButton>
      }
    />
  );
}
