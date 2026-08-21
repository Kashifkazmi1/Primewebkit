"use client";

import { useEffect, useState } from "react";
import { subscriptionsApi } from "@/lib/api/endpoints";
import type { Plan, Subscription } from "@/lib/api/types";

interface PlanFeaturesState {
  subscription: Subscription | null;
  isLoading: boolean;
  /** True once a subscription has actually loaded (even if null/free). */
  isReady: boolean;
  hasFeature: (feature: keyof Plan["features"]) => boolean;
}

/**
 * A visitor with no active subscription is on the free tier, which has
 * every gated feature (lead capture, conversation history, white-label)
 * turned off — mirrors the backend's config/default_plan_limits.php.
 */
export function usePlanFeatures(): PlanFeaturesState {
  const [subscription, setSubscription] = useState<Subscription | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isReady, setIsReady] = useState(false);

  useEffect(() => {
    subscriptionsApi
      .current()
      .then((res) => setSubscription(res.subscription))
      .catch(() => setSubscription(null))
      .finally(() => {
        setIsLoading(false);
        setIsReady(true);
      });
  }, []);

  function hasFeature(feature: keyof Plan["features"]): boolean {
    if (subscription === null) return false;
    if (subscription.status !== "active" && subscription.status !== "trialing") return false;
    return subscription.plan.features[feature];
  }

  return { subscription, isLoading, isReady, hasFeature };
}
