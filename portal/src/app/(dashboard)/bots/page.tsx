"use client";

import { Bot as BotIcon, MoreVertical, Plus, Trash2 } from "lucide-react";
import Link from "next/link";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { ApiError } from "@/lib/api/client";
import { botsApi } from "@/lib/api/endpoints";
import type { Bot } from "@/lib/api/types";
import { formatDate } from "@/lib/utils";

const statusVariant: Record<Bot["status"], "success" | "warning" | "neutral"> = {
  active: "success",
  training: "warning",
  draft: "neutral",
  archived: "neutral",
};

export default function BotsPage() {
  const [bots, setBots] = useState<Bot[] | null>(null);

  useEffect(() => {
    botsApi
      .list()
      .then(setBots)
      .catch((error) => {
        setBots([]);
        toast.error(error instanceof ApiError ? error.message : "Could not load chatbots.");
      });
  }, []);

  async function handleDelete(uuid: string) {
    if (!confirm("Delete this chatbot? This cannot be undone.")) return;
    try {
      await botsApi.remove(uuid);
      setBots((prev) => prev?.filter((bot) => bot.id !== uuid) ?? prev);
      toast.success("Chatbot deleted.");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not delete this chatbot.");
    }
  }

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 className="font-display text-2xl font-semibold tracking-tight">Chatbots</h1>
          <p className="text-sm text-muted-foreground">Create and manage the AI chatbots trained on your content.</p>
        </div>
        <Button asChild>
          <Link href="/bots/new">
            <Plus className="size-4" /> New chatbot
          </Link>
        </Button>
      </div>

      {bots === null ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Skeleton className="h-40" />
          <Skeleton className="h-40" />
          <Skeleton className="h-40" />
        </div>
      ) : bots.length === 0 ? (
        <EmptyState
          icon={BotIcon}
          title="No chatbots yet"
          description="Create your first AI chatbot trained on your website, documents, or FAQs."
          action={
            <Button asChild size="sm">
              <Link href="/bots/new">
                <Plus className="size-4" /> Create chatbot
              </Link>
            </Button>
          }
        />
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {bots.map((bot) => (
            <Card key={bot.id} className="group relative transition-shadow hover:shadow-floating">
              <CardContent className="p-5">
                <div className="flex items-start justify-between">
                  <span
                    className="flex size-11 items-center justify-center rounded-xl text-lg font-semibold text-white"
                    style={{ backgroundColor: bot.primary_color ?? "var(--color-primary)" }}
                  >
                    {bot.name.slice(0, 1).toUpperCase()}
                  </span>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon" aria-label={`Actions for ${bot.name}`}>
                        <MoreVertical className="size-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem onSelect={() => handleDelete(bot.id)} className="text-danger focus:text-danger">
                        <Trash2 className="size-4" /> Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
                <Link href={`/bots/${bot.id}`} className="mt-4 block">
                  <h3 className="font-display text-base font-semibold group-hover:text-primary">{bot.name}</h3>
                  <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                    {bot.description || "No description yet."}
                  </p>
                </Link>
                <div className="mt-4 flex items-center justify-between">
                  <Badge variant={statusVariant[bot.status]}>{bot.status}</Badge>
                  <span className="text-xs text-muted-foreground">Updated {formatDate(bot.updated_at)}</span>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
