"use client";

import { Check, CreditCard } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { UpgradeButton } from "@/components/marketing/upgrade-button";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ApiError } from "@/lib/api/client";
import { subscriptionsApi } from "@/lib/api/endpoints";
import type { Invoice, Plan, Subscription } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-context";
import { cn, formatDate } from "@/lib/utils";

export default function BillingPage() {
  const { user } = useAuth();
  const [plans, setPlans] = useState<Plan[] | null>(null);
  const [subscription, setSubscription] = useState<Subscription | null>(null);
  const [invoices, setInvoices] = useState<Invoice[] | null>(null);
  const [subscribing, setSubscribing] = useState<string | null>(null);

  useEffect(() => {
    subscriptionsApi.plans().then(setPlans).catch(() => setPlans([]));
    subscriptionsApi
      .current()
      .then((res) => setSubscription(res.subscription))
      .catch(() => setSubscription(null));
    subscriptionsApi.invoices().then(setInvoices).catch(() => setInvoices([]));
  }, []);

  async function handleSubscribe(planId: string) {
    setSubscribing(planId);
    try {
      const sub = await subscriptionsApi.subscribe(planId, "monthly");
      setSubscription(sub);
      toast.success("Plan updated.");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not change your plan.");
    } finally {
      setSubscribing(null);
    }
  }

  return (
    <div className="mx-auto max-w-5xl space-y-8">
      <div>
        <h1 className="font-display text-2xl font-semibold tracking-tight">Billing</h1>
        <p className="text-sm text-muted-foreground">Manage your plan, usage, and invoices.</p>
      </div>

      {plans === null ? (
        <div className="grid gap-4 sm:grid-cols-3">
          <Skeleton className="h-64" />
          <Skeleton className="h-64" />
          <Skeleton className="h-64" />
        </div>
      ) : (
        <div className="grid gap-4 sm:grid-cols-3">
          {plans.map((plan) => {
            const isCurrent = subscription?.plan.id === plan.id;
            const featureLabels: Record<keyof Plan["features"], string> = {
              api_access: "API access",
              analytics: "Analytics",
              white_label: "White-label branding",
              custom_domain: "Custom domain",
              priority_support: "Priority support",
              streaming: "Streaming responses",
              lead_capture: "Lead capture",
              conversation_history: "Conversation history",
            };
            return (
              <Card key={plan.id} className={cn("flex flex-col", isCurrent && "border-primary shadow-glow")}>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle>{plan.name}</CardTitle>
                    {isCurrent && <Badge variant="primary">Current</Badge>}
                  </div>
                  <CardDescription>
                    <span className="font-display text-2xl font-semibold text-foreground">
                      ${plan.monthly_price}
                    </span>{" "}
                    /month
                  </CardDescription>
                </CardHeader>
                <CardContent className="flex flex-1 flex-col justify-between gap-4">
                  <ul className="space-y-2 text-sm">
                    <li className="flex items-start gap-2">
                      <Check className="mt-0.5 size-4 shrink-0 text-success" />
                      {plan.limits.bots} chatbot{plan.limits.bots === 1 ? "" : "s"}
                    </li>
                    <li className="flex items-start gap-2">
                      <Check className="mt-0.5 size-4 shrink-0 text-success" />
                      {plan.limits.messages_per_month.toLocaleString()} messages / month
                    </li>
                    {(Object.keys(featureLabels) as (keyof Plan["features"])[])
                      .filter((key) => plan.features[key])
                      .map((key) => (
                        <li key={key} className="flex items-start gap-2">
                          <Check className="mt-0.5 size-4 shrink-0 text-success" />
                          {featureLabels[key]}
                        </li>
                      ))}
                  </ul>
                  {isCurrent ? (
                    <Button variant="outline" disabled className="w-full">
                      Current plan
                    </Button>
                  ) : plan.monthly_price === 0 ? (
                    <Button
                      variant="primary"
                      isLoading={subscribing === plan.id}
                      onClick={() => handleSubscribe(plan.id)}
                      className="w-full"
                    >
                      Switch to Free
                    </Button>
                  ) : (
                    <UpgradeButton className="w-full" plan={plan.slug} email={user?.email}>
                      Upgrade to {plan.name}
                    </UpgradeButton>
                  )}
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Invoices</CardTitle>
          <CardDescription>Your billing history.</CardDescription>
        </CardHeader>
        <CardContent>
          {invoices === null ? (
            <Skeleton className="h-24" />
          ) : invoices.length === 0 ? (
            <EmptyState icon={CreditCard} title="No invoices yet" description="Invoices will appear here once billing begins." />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Date</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {invoices.map((invoice) => (
                  <TableRow key={invoice.id}>
                    <TableCell>{formatDate(invoice.created_at)}</TableCell>
                    <TableCell>
                      {invoice.currency} {invoice.total.toFixed(2)}
                    </TableCell>
                    <TableCell>
                      <Badge variant={invoice.status === "paid" ? "success" : "warning"}>{invoice.status}</Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
