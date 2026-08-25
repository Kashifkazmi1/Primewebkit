"use client";

import { Ban, CheckCircle2, ChevronLeft, ChevronRight, CreditCard, DollarSign, Search, ShieldAlert, Users } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { ConfirmDialog } from "@/components/ui/confirm-dialog";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input, Label } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { adminApi, type AdminOverview, type AdminUser } from "@/lib/api/admin";
import { ApiError } from "@/lib/api/client";
import { subscriptionsApi } from "@/lib/api/endpoints";
import { useAuth } from "@/lib/auth/auth-context";
import type { Pagination, Plan } from "@/lib/api/types";
import { formatDate } from "@/lib/utils";

const PER_PAGE = 20;
type StatusFilter = "all" | "active" | "suspended";

export default function AdminPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === "super-admin" || user?.role === "admin";
  const [overview, setOverview] = useState<AdminOverview | null>(null);
  const [users, setUsers] = useState<AdminUser[] | null>(null);
  const [pagination, setPagination] = useState<Pagination | null>(null);
  const [query, setQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState<StatusFilter>("all");
  const [page, setPage] = useState(1);
  const [pendingAction, setPendingAction] = useState<{ user: AdminUser; suspend: boolean } | null>(null);
  const [actionLoading, setActionLoading] = useState(false);
  const [plans, setPlans] = useState<Plan[] | null>(null);
  const [assignTarget, setAssignTarget] = useState<AdminUser | null>(null);
  const [assignPlanId, setAssignPlanId] = useState<string>("");
  const [assignCycle, setAssignCycle] = useState<"monthly" | "yearly">("monthly");
  const [assignLoading, setAssignLoading] = useState(false);

  useEffect(() => {
    if (!isAdmin) return;
    subscriptionsApi.plans().then(setPlans).catch(() => setPlans([]));
  }, [isAdmin]);

  function openAssignDialog(target: AdminUser) {
    setAssignTarget(target);
    setAssignPlanId(plans?.[0]?.id ?? "");
    setAssignCycle("monthly");
  }

  async function confirmAssignPlan() {
    if (!assignTarget || !assignPlanId) return;
    setAssignLoading(true);
    try {
      await adminApi.assignPlan(assignTarget.id, assignPlanId, assignCycle);
      const planName = plans?.find((p) => p.id === assignPlanId)?.name ?? "the selected plan";
      toast.success(`${assignTarget.name} is now on ${planName}.`);
      setAssignTarget(null);
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not assign this plan.");
    } finally {
      setAssignLoading(false);
    }
  }

  const loadUsers = useCallback(() => {
    if (!isAdmin) return;
    adminApi
      .users({ q: query || undefined, status: statusFilter === "all" ? undefined : statusFilter, page, perPage: PER_PAGE })
      .then((res) => {
        setUsers(res.data);
        setPagination(res.pagination);
      })
      .catch(() => setUsers([]));
  }, [isAdmin, query, statusFilter, page]);

  useEffect(() => {
    if (!isAdmin) return;
    adminApi.overview().then(setOverview).catch(() => setOverview(null));
  }, [isAdmin]);

  useEffect(() => {
    setUsers(null);
    loadUsers();
  }, [loadUsers]);

  useEffect(() => {
    setPage(1);
  }, [query, statusFilter]);

  async function confirmAction() {
    if (!pendingAction) return;
    const { user: target, suspend } = pendingAction;
    setActionLoading(true);
    try {
      if (suspend) await adminApi.suspendUser(target.id);
      else await adminApi.activateUser(target.id);
      setUsers((prev) => prev?.map((u) => (u.id === target.id ? { ...u, status: suspend ? "suspended" : "active" } : u)) ?? prev);
      toast.success(suspend ? `${target.name} has been blocked.` : `${target.name} has been activated.`);
      setPendingAction(null);
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not update this user.");
      loadUsers();
    } finally {
      setActionLoading(false);
    }
  }

  if (!isAdmin) {
    return (
      <div className="mx-auto flex max-w-lg flex-col items-center gap-3 py-24 text-center">
        <ShieldAlert className="size-10 text-muted-foreground" />
        <h1 className="font-display text-xl font-semibold">Admin access required</h1>
        <p className="text-sm text-muted-foreground">You don&apos;t have permission to view this area.</p>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-6xl space-y-8">
      <div>
        <h1 className="font-display text-2xl font-semibold tracking-tight">Admin overview</h1>
        <p className="text-sm text-muted-foreground">Platform-wide stats and user management.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <StatTile icon={Users} label="Total users" value={overview?.users.total} />
        <StatTile icon={CheckCircle2} label="Active subscriptions" value={overview?.subscriptions.active ?? 0} />
        <StatTile icon={DollarSign} label="Revenue this month" value={overview ? `$${overview.revenue.this_month.toFixed(2)}` : undefined} />
      </div>

      <Card>
        <CardContent className="space-y-4 p-6">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="relative w-full sm:max-w-xs">
              <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Search by name or email…"
                className="pl-9"
                aria-label="Search users"
              />
            </div>
            <Select value={statusFilter} onValueChange={(value) => setStatusFilter(value as StatusFilter)}>
              <SelectTrigger className="w-full sm:w-44">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All statuses</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="suspended">Suspended</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {users === null ? (
            <Skeleton className="h-32" />
          ) : users.length === 0 ? (
            <p className="py-10 text-center text-sm text-muted-foreground">No users match this search.</p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Email</TableHead>
                    <TableHead>Role</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Last login</TableHead>
                    <TableHead>Joined</TableHead>
                    <TableHead className="sr-only">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {users.map((u) => (
                    <TableRow key={u.id}>
                      <TableCell>{u.name}</TableCell>
                      <TableCell>{u.email}</TableCell>
                      <TableCell>
                        <Badge variant="outline">{u.role ?? "user"}</Badge>
                      </TableCell>
                      <TableCell>
                        <Badge variant={u.status === "active" ? "success" : "danger"}>{u.status}</Badge>
                      </TableCell>
                      <TableCell className="text-sm text-muted-foreground">
                        {u.last_login_at ? formatDate(u.last_login_at) : "Never"}
                      </TableCell>
                      <TableCell className="text-sm text-muted-foreground">{formatDate(u.created_at)}</TableCell>
                      <TableCell>
                        <div className="flex justify-end gap-1">
                          <Button variant="ghost" size="icon" aria-label="Assign plan" onClick={() => openAssignDialog(u)}>
                            <CreditCard className="size-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            aria-label={u.status === "active" ? "Suspend user" : "Activate user"}
                            onClick={() => setPendingAction({ user: u, suspend: u.status === "active" })}
                          >
                            {u.status === "active" ? <Ban className="size-4" /> : <CheckCircle2 className="size-4" />}
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {pagination && pagination.last_page > 1 && (
                <div className="flex items-center justify-between pt-1">
                  <p className="text-xs text-muted-foreground">
                    {pagination.total} users &middot; page {pagination.page} of {pagination.last_page}
                  </p>
                  <div className="flex gap-2">
                    <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                      <ChevronLeft className="size-4" /> Prev
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={page >= pagination.last_page}
                      onClick={() => setPage((p) => p + 1)}
                    >
                      Next <ChevronRight className="size-4" />
                    </Button>
                  </div>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>

      <ConfirmDialog
        open={pendingAction !== null}
        onOpenChange={(open) => !open && setPendingAction(null)}
        title={pendingAction?.suspend ? `Block ${pendingAction.user.name}?` : `Activate ${pendingAction?.user.name}?`}
        description={
          pendingAction?.suspend
            ? "They will be immediately signed out and won't be able to log back in until reactivated."
            : "They will regain access to their account and can sign in again."
        }
        confirmLabel={pendingAction?.suspend ? "Block user" : "Activate user"}
        confirmVariant={pendingAction?.suspend ? "danger" : "primary"}
        isLoading={actionLoading}
        onConfirm={confirmAction}
      />

      <Dialog open={assignTarget !== null} onOpenChange={(open) => !open && setAssignTarget(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Assign a plan to {assignTarget?.name}</DialogTitle>
            <DialogDescription>
              Puts this account on the selected plan immediately — no payment is charged. Use this to comp access, or
              to fix an account stuck on the wrong plan while a payment provider's webhook catches up.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-2">
            <div className="space-y-1.5">
              <Label htmlFor="assign-plan">Plan</Label>
              <Select value={assignPlanId} onValueChange={setAssignPlanId}>
                <SelectTrigger id="assign-plan">
                  <SelectValue placeholder="Select a plan" />
                </SelectTrigger>
                <SelectContent>
                  {(plans ?? []).map((plan) => (
                    <SelectItem key={plan.id} value={plan.id}>
                      {plan.name} — ${plan.monthly_price}/mo
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="assign-cycle">Billing cycle</Label>
              <Select value={assignCycle} onValueChange={(value) => setAssignCycle(value as "monthly" | "yearly")}>
                <SelectTrigger id="assign-cycle">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="monthly">Monthly</SelectItem>
                  <SelectItem value="yearly">Yearly</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setAssignTarget(null)}>
              Cancel
            </Button>
            <Button isLoading={assignLoading} disabled={!assignPlanId} onClick={confirmAssignPlan}>
              Assign plan
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function StatTile({
  icon: Icon,
  label,
  value,
}: {
  icon: React.ComponentType<{ className?: string }>;
  label: string;
  value?: number | string;
}) {
  return (
    <Card>
      <CardContent className="flex items-center justify-between p-5">
        <div>
          <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</p>
          {value === undefined ? (
            <Skeleton className="mt-2 h-7 w-16" />
          ) : (
            <p className="mt-1 font-display text-2xl font-semibold">{value}</p>
          )}
        </div>
        <span className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
          <Icon className="size-5" />
        </span>
      </CardContent>
    </Card>
  );
}
