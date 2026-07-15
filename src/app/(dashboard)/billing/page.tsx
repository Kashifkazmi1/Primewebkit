"use client";

import { Check, CreditCard } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ApiError } from "@/lib/api/client";
import { subscriptionsApi } from "@/lib/api/endpoints";
import type { Invoice, Plan, Subscription } from "@/lib/api/types";
import { cn, formatDate } from "@/lib/utils";

export default function BillingPage() {
  const [plans, setPlans] = useState<Plan[] | null>(null);
  const [subscription, setSubscription] = useState<Subscription | null>(null);
  const [invoices, setInvoices] = useState<Invoice[] | null>(null);
  const [subscribing, setSubscribing] = useState<string | null>(null);

  useEffect(() => {
    subscriptionsApi.plans().then(setPlans).catch(() => setPlans([]));
    subscriptionsApi
      .current()
      .then(setSubscription)
      .catch(() => setSubscription(null));
    subscriptionsApi.invoices().then(setInvoices).catch(() => setInvoices([]));
  }, []);

  async function handleSubscribe(planId: string) {
    setSubscribing(planId);
    try {
      const sub = await subscriptionsApi.subscribe(planId);
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
            return (
              <Card key={plan.id} className={cn("flex flex-col", isCurrent && "border-primary shadow-glow")}>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle>{plan.name}</CardTitle>
                    {isCurrent && <Badge variant="primary">Current</Badge>}
                  </div>
                  <CardDescription>
                    <span className="font-display text-2xl font-semibold text-foreground">
                      ${plan.price_monthly}
                    </span>{" "}
                    /month
                  </CardDescription>
                </CardHeader>
                <CardContent className="flex flex-1 flex-col justify-between gap-4">
                  <ul className="space-y-2 text-sm">
                    {plan.features.map((feature) => (
                      <li key={feature} className="flex items-start gap-2">
                        <Check className="mt-0.5 size-4 shrink-0 text-success" />
                        {feature}
                      </li>
                    ))}
                  </ul>
                  <Button
                    variant={isCurrent ? "outline" : "primary"}
                    disabled={isCurrent}
                    isLoading={subscribing === plan.id}
                    onClick={() => handleSubscribe(plan.id)}
                    className="w-full"
                  >
                    {isCurrent ? "Current plan" : "Switch plan"}
                  </Button>
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
                    <TableCell>{formatDate(invoice.issued_at)}</TableCell>
                    <TableCell>
                      {invoice.currency} {invoice.amount.toFixed(2)}
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
