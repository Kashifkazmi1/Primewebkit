"use client";

import { Bell } from "lucide-react";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { EmptyState } from "@/components/ui/empty-state";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { notificationsApi } from "@/lib/api/endpoints";
import type { NotificationItem } from "@/lib/api/types";
import { cn, formatDateTime } from "@/lib/utils";

export function NotificationsMenu() {
  const [items, setItems] = useState<NotificationItem[]>([]);
  const [unread, setUnread] = useState(0);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    notificationsApi
      .unreadCount()
      .then((res) => setUnread(res.count))
      .catch(() => {});
  }, []);

  useEffect(() => {
    if (!open) return;
    notificationsApi
      .list()
      .then(setItems)
      .catch(() => {});
  }, [open]);

  async function markAllRead() {
    await notificationsApi.markAllRead().catch(() => {});
    setUnread(0);
    setItems((prev) => prev.map((item) => ({ ...item, read_at: new Date().toISOString() })));
  }

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button variant="ghost" size="icon" aria-label={`Notifications${unread > 0 ? ` (${unread} unread)` : ""}`}>
          <span className="relative">
            <Bell className="size-4.5" />
            {unread > 0 && (
              <span className="absolute -right-1 -top-1 flex size-3.5 items-center justify-center rounded-full bg-danger text-[9px] font-bold text-danger-foreground">
                {unread > 9 ? "9+" : unread}
              </span>
            )}
          </span>
        </Button>
      </PopoverTrigger>
      <PopoverContent align="end" className="w-80 p-0">
        <div className="flex items-center justify-between border-b border-border px-4 py-3">
          <p className="font-display text-sm font-semibold">Notifications</p>
          {items.length > 0 && (
            <button onClick={markAllRead} className="text-xs font-medium text-primary hover:underline">
              Mark all read
            </button>
          )}
        </div>
        <div className="max-h-80 overflow-y-auto">
          {items.length === 0 ? (
            <EmptyState title="You're all caught up" description="New notifications will show up here." className="border-none py-8" />
          ) : (
            items.map((item) => (
              <div
                key={item.id}
                className={cn("border-b border-border px-4 py-3 last:border-0", !item.read_at && "bg-primary/5")}
              >
                <p className="text-sm font-medium">{item.title}</p>
                {item.body && <p className="mt-0.5 text-xs text-muted-foreground">{item.body}</p>}
                <p className="mt-1 text-[11px] text-muted-foreground">{formatDateTime(item.created_at)}</p>
              </div>
            ))
          )}
        </div>
      </PopoverContent>
    </Popover>
  );
}
