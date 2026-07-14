"use client";

import { Check, CreditCard } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { EmptyState } from "@/components/ui/empty-state";
import { FieldError, Input, Label } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ApiError } from "@/lib/api/client";
import { subscriptionsApi } from "@/lib/api/endpoints";
import type { Invoice, Plan, Subscription } from "@/lib/api/types";
import { cn, formatDate } from "@/lib/utils";

type BillingCycle = "monthly" | "yearly";

export default function BillingPage() {
  const [plans, setPlans] = useState<Plan[] | null>(null);
  const [subscription, setSubscription] = useState<Subscription | null>(null);
  const [invoices, setInvoices] = useState<Invoice[] | null>(null);
  const [cycle, setCycle] = useState<BillingCycle>("monthly");
  const [subscribeTarget, setSubscribeTarget] = useState<Plan | null>(null);
  const [couponCode, setCouponCode] = useState("");
  const [subscribing, setSubscribing] = useState(false);
  const [cancelling, setCancelling] = useState(false);

  useEffect(() => {
    subscriptionsApi.plans().then(setPlans).catch(() => setPlans([]));
    subscriptionsApi
      .current()
      .then(setSubscription)
      .catch(() => setSubscription(null));
    subscriptionsApi.invoices().then(setInvoices).catch(() => setInvoices([]));
  }, []);

  async function handleSubscribe() {
    if (!subscribeTarget) return;
    setSubscribing(true);
    try {
      const sub = await subscriptionsApi.subscribe({
        plan_id: subscribeTarget.id,
        billing_cycle: cycle,
        coupon_code: couponCode.trim() || undefined,
      });
      setSubscription(sub);
      toast.success(`You're now on the ${subscribeTarget.name} plan.`);
      setSubscribeTarget(null);
      setCouponCode("");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not change your plan.");
    } finally {
      setSubscribing(false);
    }
  }

  async function handleCancel() {
    if (!subscription) return;
    setCancelling(true);
    try {
      await subscriptionsApi.cancel(subscription.id);
      setSubscription({ ...subscription, cancel_at_period_end: true });
      toast.success("Your subscription will end at the close of the current billing period.");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not cancel your subscription.");
    } finally {
      setCancelling(false);
    }
  }

  return (
    <div className="mx-auto max-w-5xl space-y-8">
      <div>
        <h1 className="font-display text-2xl font-semibold tracking-tight">Billing</h1>
        <p className="text-sm text-muted-foreground">Manage your plan, usage, and invoices.</p>
      </div>

      {subscription && (
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle>Current subscription</CardTitle>
              <Badge variant={subscription.status === "active" ? "success" : "warning"}>{subscription.status}</Badge>
            </div>
            <CardDescription>
              {subscription.plan.name} ·{" "}
              {subscription.cancel_at_period_end
                ? `Ends ${formatDate(subscription.current_period_end)}`
                : `Renews ${formatDate(subscription.current_period_end)}`}
            </CardDescription>
          </CardHeader>
          {!subscription.cancel_at_period_end && (
            <CardFooter className="justify-end">
              <Button variant="outline" isLoading={cancelling} onClick={handleCancel}>
                Cancel subscription
              </Button>
            </CardFooter>
          )}
        </Card>
      )}

      <div className="flex items-center justify-center gap-3">
        <button
          type="button"
          onClick={() => setCycle("monthly")}
          className={cn(
            "rounded-full px-4 py-1.5 text-sm font-medium transition-colors",
            cycle === "monthly" ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:text-foreground",
          )}
        >
          Monthly
        </button>
        <button
          type="button"
          onClick={() => setCycle("yearly")}
          className={cn(
            "rounded-full px-4 py-1.5 text-sm font-medium transition-colors",
            cycle === "yearly" ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:text-foreground",
          )}
        >
          Yearly <span className="opacity-75">(save ~20%)</span>
        </button>
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
            const isCurrent = subscription?.plan.id === plan.id && !subscription.cancel_at_period_end;
            const price = cycle === "yearly" && plan.price_yearly != null ? plan.price_yearly : plan.price_monthly;
            return (
              <Card key={plan.id} className={cn("flex flex-col", isCurrent && "border-primary shadow-glow")}>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle>{plan.name}</CardTitle>
                    {isCurrent && <Badge variant="primary">Current</Badge>}
                  </div>
                  <CardDescription>
                    <span className="font-display text-2xl font-semibold text-foreground">${price}</span>{" "}
                    /{cycle === "yearly" ? "year" : "month"}
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
                    onClick={() => setSubscribeTarget(plan)}
                    className="w-full"
                  >
                    {isCurrent ? "Current plan" : "Choose plan"}
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

      <Dialog open={!!subscribeTarget} onOpenChange={(open) => !open && setSubscribeTarget(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Switch to {subscribeTarget?.name}</DialogTitle>
            <DialogDescription>
              Billed {cycle}. You can cancel anytime — access continues until the end of the current period.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label htmlFor="coupon">Coupon code (optional)</Label>
            <Input
              id="coupon"
              value={couponCode}
              onChange={(e) => setCouponCode(e.target.value)}
              placeholder="e.g. LAUNCH20"
            />
            <FieldError message={undefined} />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setSubscribeTarget(null)} disabled={subscribing}>
              Cancel
            </Button>
            <Button onClick={handleSubscribe} isLoading={subscribing}>
              Confirm
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
