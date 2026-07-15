"use client";

import { ArrowUpRight, Bot as BotIcon, Plus, Sparkles, Webhook as WebhookIcon } from "lucide-react";
import Link from "next/link";
import { useEffect, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { botsApi, subscriptionsApi } from "@/lib/api/endpoints";
import type { Bot, Subscription } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-context";
import { formatDate } from "@/lib/utils";

export default function DashboardPage() {
  const { user } = useAuth();
  const [bots, setBots] = useState<Bot[] | null>(null);
  const [subscription, setSubscription] = useState<Subscription | null>(null);

  useEffect(() => {
    botsApi.list().then(setBots).catch(() => setBots([]));
    subscriptionsApi
      .current()
      .then(setSubscription)
      .catch(() => setSubscription(null));
  }, []);

  const firstName = user?.name.split(" ")[0];

  return (
    <div className="mx-auto max-w-6xl space-y-8">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 className="font-display text-2xl font-semibold tracking-tight">Welcome back{firstName ? `, ${firstName}` : ""}</h1>
          <p className="text-sm text-muted-foreground">Here&apos;s what&apos;s happening across your chatbots.</p>
        </div>
        <Button asChild>
          <Link href="/bots/new">
            <Plus className="size-4" /> New chatbot
          </Link>
        </Button>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard label="Chatbots" value={bots ? bots.length : undefined} icon={BotIcon} />
        <StatCard
          label="Current plan"
          valueLabel={subscription?.plan.name ?? "Free"}
          icon={Sparkles}
          href="/billing"
        />
        <StatCard label="Webhooks" valueLabel="Manage" icon={WebhookIcon} href="/webhooks" />
      </div>

      <Card>
        <CardHeader className="flex-row items-center justify-between space-y-0">
          <CardTitle>Your chatbots</CardTitle>
          <Link href="/bots" className="flex items-center gap-1 text-sm font-medium text-primary hover:underline">
            View all <ArrowUpRight className="size-3.5" />
          </Link>
        </CardHeader>
        <CardContent>
          {bots === null ? (
            <div className="space-y-3">
              <Skeleton className="h-16" />
              <Skeleton className="h-16" />
            </div>
          ) : bots.length === 0 ? (
            <EmptyState
              icon={BotIcon}
              title="No chatbots yet"
              description="Create your first AI chatbot trained on your website or documents."
              action={
                <Button asChild size="sm">
                  <Link href="/bots/new">
                    <Plus className="size-4" /> Create chatbot
                  </Link>
                </Button>
              }
            />
          ) : (
            <div className="space-y-2">
              {bots.slice(0, 5).map((bot) => (
                <Link
                  key={bot.id}
                  href={`/bots/detail?id=${bot.id}`}
                  className="flex items-center justify-between rounded-xl border border-border p-4 transition-colors hover:border-border-strong hover:bg-muted/50"
                >
                  <div className="flex items-center gap-3">
                    <span className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                      <BotIcon className="size-5" />
                    </span>
                    <div>
                      <p className="font-medium">{bot.name}</p>
                      <p className="text-xs text-muted-foreground">Created {formatDate(bot.created_at)}</p>
                    </div>
                  </div>
                  <Badge variant={bot.status === "active" ? "success" : "neutral"}>{bot.status}</Badge>
                </Link>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

function StatCard({
  label,
  value,
  valueLabel,
  icon: Icon,
  href,
}: {
  label: string;
  value?: number;
  valueLabel?: string;
  icon: React.ComponentType<{ className?: string }>;
  href?: string;
}) {
  const content = (
    <Card className="transition-shadow hover:shadow-floating">
      <CardContent className="flex items-center justify-between p-5">
        <div>
          <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</p>
          {value === undefined && valueLabel === undefined ? (
            <Skeleton className="mt-2 h-7 w-12" />
          ) : (
            <p className="mt-1 font-display text-2xl font-semibold">{valueLabel ?? value}</p>
          )}
        </div>
        <span className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
          <Icon className="size-5" />
        </span>
      </CardContent>
    </Card>
  );

  return href ? (
    <Link href={href} className="block">
      {content}
    </Link>
  ) : (
    content
  );
}
