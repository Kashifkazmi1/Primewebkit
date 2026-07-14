"use client";

import { Gauge, HelpCircle, MessagesSquare, Star, Target, Timer } from "lucide-react";
import { useEffect, useState } from "react";
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { botsApi } from "@/lib/api/endpoints";
import type { AnalyticsSeriesPoint, BotAnalytics } from "@/lib/api/types";

function chronological(series: AnalyticsSeriesPoint[]): AnalyticsSeriesPoint[] {
  return [...series].reverse();
}

function MiniAreaChart({ data, color }: { data: AnalyticsSeriesPoint[]; color: string }) {
  if (data.length === 0) {
    return <div className="flex h-40 items-center justify-center text-sm text-muted-foreground">No data yet</div>;
  }
  const gradientId = `gradient-${color.replace(/[^a-z0-9]/gi, "")}`;
  return (
    <ResponsiveContainer width="100%" height={160}>
      <AreaChart data={chronological(data)} margin={{ top: 8, right: 8, left: -24, bottom: 0 }}>
        <defs>
          <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor={color} stopOpacity={0.35} />
            <stop offset="100%" stopColor={color} stopOpacity={0} />
          </linearGradient>
        </defs>
        <XAxis dataKey="bucket" tick={{ fontSize: 11 }} tickLine={false} axisLine={false} minTickGap={24} />
        <YAxis tick={{ fontSize: 11 }} tickLine={false} axisLine={false} allowDecimals={false} width={32} />
        <Tooltip
          contentStyle={{
            background: "var(--surface)",
            border: "1px solid var(--border)",
            borderRadius: 12,
            fontSize: 12,
          }}
        />
        <Area type="monotone" dataKey="total" stroke={color} strokeWidth={2} fill={`url(#${gradientId})`} />
      </AreaChart>
    </ResponsiveContainer>
  );
}

export function BotAnalyticsTab({ botUuid }: { botUuid: string }) {
  const [analytics, setAnalytics] = useState<BotAnalytics | null>(null);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    botsApi
      .analytics(botUuid, "day", 30)
      .then(setAnalytics)
      .catch(() => setFailed(true));
  }, [botUuid]);

  if (failed) {
    return (
      <EmptyState
        icon={Gauge}
        title="Analytics unavailable"
        description="We couldn't load analytics for this chatbot yet."
      />
    );
  }

  if (!analytics) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <Skeleton key={i} className="h-24" />
        ))}
      </div>
    );
  }

  const metrics = [
    {
      label: "Avg. response time",
      value: `${(analytics.averages.response_time_ms / 1000).toFixed(2)}s`,
      icon: Timer,
    },
    {
      label: "Tokens / message",
      value: Math.round(analytics.averages.tokens_per_message).toLocaleString(),
      icon: Gauge,
    },
    {
      label: "Lead conversion rate",
      value: `${analytics.lead_conversion_rate.toFixed(1)}%`,
      icon: Target,
    },
    {
      label: "Avg. rating",
      value: analytics.average_rating > 0 ? `${analytics.average_rating.toFixed(1)} / 5` : "—",
      icon: Star,
    },
  ];

  return (
    <div className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {metrics.map((metric) => (
          <Card key={metric.label}>
            <CardContent className="flex items-center gap-3 pt-6">
              <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <metric.icon className="size-5" />
              </span>
              <div>
                <p className="text-lg font-semibold">{metric.value}</p>
                <p className="text-xs text-muted-foreground">{metric.label}</p>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle className="text-sm flex items-center gap-2">
              <MessagesSquare className="size-4" /> Conversations
            </CardTitle>
            <CardDescription>Last 30 days</CardDescription>
          </CardHeader>
          <CardContent>
            <MiniAreaChart data={analytics.conversations_by_period} color="var(--primary)" />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm flex items-center gap-2">
              <MessagesSquare className="size-4" /> Messages
            </CardTitle>
            <CardDescription>Last 30 days</CardDescription>
          </CardHeader>
          <CardContent>
            <MiniAreaChart data={analytics.messages_by_period} color="var(--accent)" />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm flex items-center gap-2">
              <Target className="size-4" /> Leads
            </CardTitle>
            <CardDescription>Last 30 days</CardDescription>
          </CardHeader>
          <CardContent>
            <MiniAreaChart data={analytics.leads_by_period} color="var(--success)" />
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <HelpCircle className="size-4" /> Most asked questions
          </CardTitle>
          <CardDescription>What visitors ask this chatbot most often.</CardDescription>
        </CardHeader>
        <CardContent>
          {analytics.most_asked_questions.length === 0 ? (
            <p className="text-sm text-muted-foreground">Not enough conversation history yet.</p>
          ) : (
            <ol className="space-y-2">
              {analytics.most_asked_questions.slice(0, 6).map((q, i) => (
                <li key={q.question} className="flex items-center justify-between gap-4 rounded-xl border border-border p-3">
                  <span className="flex items-center gap-3 text-sm">
                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground">
                      {i + 1}
                    </span>
                    {q.question}
                  </span>
                  <span className="shrink-0 text-xs font-medium text-muted-foreground">{q.total}×</span>
                </li>
              ))}
            </ol>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
