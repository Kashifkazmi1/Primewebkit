"use client";

import { use, useEffect, useState } from "react";
import Link from "next/link";
import { ArrowLeft } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { BotConversationsTab } from "@/components/bots/bot-conversations-tab";
import { BotKnowledgeTab } from "@/components/bots/bot-knowledge-tab";
import { BotLeadsTab } from "@/components/bots/bot-leads-tab";
import { BotSettingsTab } from "@/components/bots/bot-settings-tab";
import { BotWidgetTab } from "@/components/bots/bot-widget-tab";
import { botsApi } from "@/lib/api/endpoints";
import type { Bot } from "@/lib/api/types";

const statusVariant: Record<Bot["status"], "success" | "warning" | "neutral"> = {
  active: "success",
  training: "warning",
  draft: "neutral",
  archived: "neutral",
};

export default function BotDetailPage({ params }: { params: Promise<{ uuid: string }> }) {
  const { uuid } = use(params);
  const [bot, setBot] = useState<Bot | null>(null);

  useEffect(() => {
    botsApi.get(uuid).then(setBot).catch(() => setBot(null));
  }, [uuid]);

  return (
    <div className="mx-auto max-w-5xl space-y-6">
      <Link href="/bots" className="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground">
        <ArrowLeft className="size-4" /> Back to chatbots
      </Link>

      {bot === null ? (
        <Skeleton className="h-24" />
      ) : (
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <div className="flex items-center gap-3">
              <h1 className="font-display text-2xl font-semibold tracking-tight">{bot.name}</h1>
              <Badge variant={statusVariant[bot.status]}>{bot.status}</Badge>
            </div>
            <p className="mt-1 text-sm text-muted-foreground">{bot.description || "No description."}</p>
          </div>
        </div>
      )}

      <Tabs defaultValue="settings">
        <TabsList>
          <TabsTrigger value="settings">Settings</TabsTrigger>
          <TabsTrigger value="knowledge">Knowledge base</TabsTrigger>
          <TabsTrigger value="widget">Widget &amp; embed</TabsTrigger>
          <TabsTrigger value="conversations">Conversations</TabsTrigger>
          <TabsTrigger value="leads">Leads</TabsTrigger>
        </TabsList>
        <TabsContent value="settings">{bot && <BotSettingsTab bot={bot} onUpdated={setBot} />}</TabsContent>
        <TabsContent value="knowledge">
          <BotKnowledgeTab botUuid={uuid} />
        </TabsContent>
        <TabsContent value="widget">
          <BotWidgetTab botUuid={uuid} />
        </TabsContent>
        <TabsContent value="conversations">
          <BotConversationsTab botUuid={uuid} />
        </TabsContent>
        <TabsContent value="leads">
          <BotLeadsTab botUuid={uuid} />
        </TabsContent>
      </Tabs>
    </div>
  );
}
