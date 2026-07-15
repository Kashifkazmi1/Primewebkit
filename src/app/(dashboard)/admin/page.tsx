"use client";

import { Ban, CheckCircle2, DollarSign, ShieldAlert, Users } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { adminApi, type AdminOverview, type AdminUser } from "@/lib/api/admin";
import { ApiError } from "@/lib/api/client";
import { useAuth } from "@/lib/auth/auth-context";
import { formatDate } from "@/lib/utils";

export default function AdminPage() {
  const { user } = useAuth();
  const isAdmin = user?.role === "super-admin" || user?.role === "admin";
  const [overview, setOverview] = useState<AdminOverview | null>(null);
  const [users, setUsers] = useState<AdminUser[] | null>(null);

  useEffect(() => {
    if (!isAdmin) return;
    adminApi.overview().then(setOverview).catch(() => setOverview(null));
    adminApi
      .users()
      .then((res) => setUsers(res.data))
      .catch(() => setUsers([]));
  }, [isAdmin]);

  async function handleSuspend(uuid: string, suspend: boolean) {
    try {
      if (suspend) await adminApi.suspendUser(uuid);
      else await adminApi.activateUser(uuid);
      setUsers((prev) => prev?.map((u) => (u.id === uuid ? { ...u, status: suspend ? "suspended" : "active" } : u)) ?? prev);
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not update this user.");
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
        <StatTile icon={Users} label="Total users" value={overview?.total_users} />
        <StatTile icon={CheckCircle2} label="Active subscriptions" value={overview?.active_subscriptions} />
        <StatTile icon={DollarSign} label="MRR" value={overview ? `$${overview.mrr}` : undefined} />
      </div>

      <Card>
        <CardContent className="p-0">
          {users === null ? (
            <Skeleton className="m-6 h-32" />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead>Status</TableHead>
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
                    <TableCell className="text-sm text-muted-foreground">{formatDate(u.created_at)}</TableCell>
                    <TableCell>
                      <Button
                        variant="ghost"
                        size="icon"
                        aria-label={u.status === "active" ? "Suspend user" : "Activate user"}
                        onClick={() => handleSuspend(u.id, u.status === "active")}
                      >
                        {u.status === "active" ? <Ban className="size-4" /> : <CheckCircle2 className="size-4" />}
                      </Button>
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
