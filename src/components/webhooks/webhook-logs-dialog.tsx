"use client";

import { useEffect, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { webhooksApi } from "@/lib/api/endpoints";
import type { Webhook, WebhookLog } from "@/lib/api/types";
import { formatDateTime } from "@/lib/utils";

export function WebhookLogsDialog({
  webhook,
  onOpenChange,
}: {
  webhook: Webhook;
  onOpenChange: (open: boolean) => void;
}) {
  const [logs, setLogs] = useState<WebhookLog[] | null>(null);

  useEffect(() => {
    let cancelled = false;
    webhooksApi
      .logs(webhook.id)
      .then((res) => {
        if (!cancelled) setLogs(res.data);
      })
      .catch(() => {
        if (!cancelled) setLogs([]);
      });
    return () => {
      cancelled = true;
    };
  }, [webhook.id]);

  return (
    <Dialog open onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Delivery logs</DialogTitle>
          <DialogDescription className="truncate font-mono text-xs">{webhook.url}</DialogDescription>
        </DialogHeader>
        {logs === null ? (
          <div className="space-y-2">
            <Skeleton className="h-10" />
            <Skeleton className="h-10" />
            <Skeleton className="h-10" />
          </div>
        ) : logs.length === 0 ? (
          <EmptyState title="No deliveries yet" description="Events will appear here once this endpoint is triggered." />
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Event</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Attempt</TableHead>
                <TableHead>When</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {logs.map((log) => (
                <TableRow key={log.id}>
                  <TableCell className="font-mono text-xs">{log.event}</TableCell>
                  <TableCell>
                    <Badge variant={log.success ? "success" : "danger"}>{log.status_code ?? "—"}</Badge>
                  </TableCell>
                  <TableCell className="text-sm text-muted-foreground">#{log.attempt}</TableCell>
                  <TableCell className="text-sm text-muted-foreground">{formatDateTime(log.created_at)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </DialogContent>
    </Dialog>
  );
}
