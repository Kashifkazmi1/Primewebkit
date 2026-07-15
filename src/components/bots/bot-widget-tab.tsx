"use client";

import { Check, Copy } from "lucide-react";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { botsApi } from "@/lib/api/endpoints";
import { env } from "@/lib/env";

export function BotWidgetTab({ botUuid }: { botUuid: string }) {
  const [script, setScript] = useState<string | null>(null);
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    botsApi
      .embedScript(botUuid)
      .then((res) => setScript(res.script))
      .catch(() =>
        setScript(
          `<script src="${env.widgetUrl}" data-bot-id="${botUuid}" async></script>`,
        ),
      );
  }, [botUuid]);

  async function copy() {
    if (!script) return;
    await navigator.clipboard.writeText(script);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  return (
    <div className="grid gap-6 lg:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>Embed on your website</CardTitle>
          <CardDescription>Paste this script tag before the closing <code>&lt;/body&gt;</code> tag.</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="relative rounded-xl border border-border bg-muted p-4">
            <pre className="overflow-x-auto whitespace-pre-wrap break-all font-mono text-xs">{script ?? "Loading…"}</pre>
            <Button
              variant="outline"
              size="icon"
              className="absolute right-3 top-3 bg-surface"
              onClick={copy}
              aria-label="Copy embed script"
            >
              {copied ? <Check className="size-4 text-success" /> : <Copy className="size-4" />}
            </Button>
          </div>
          <p className="mt-3 text-xs text-muted-foreground">
            Works on WordPress, Shopify, Webflow, or any custom HTML site.
          </p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Full-page chat</CardTitle>
          <CardDescription>Share a direct link or embed as an iframe.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-3">
          <div className="rounded-xl border border-border bg-muted p-3 font-mono text-xs break-all">
            {env.siteUrl}/chat?id={botUuid}
          </div>
          <Button
            variant="outline"
            size="sm"
            onClick={() => window.open(`${env.siteUrl}/chat?id=${botUuid}`, "_blank", "noopener,noreferrer")}
          >
            Open full-page chat
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
