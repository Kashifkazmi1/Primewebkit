"use client";

import { Check, Copy, MessageCircle } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input, Label, Textarea } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { Switch } from "@/components/ui/switch";
import { ApiError } from "@/lib/api/client";
import { botsApi } from "@/lib/api/endpoints";
import type { Widget } from "@/lib/api/types";
import { env } from "@/lib/env";
import { cn } from "@/lib/utils";

function normalizeDomains(raw: string): string[] {
  return raw
    .split(/[\n,]/)
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line) => line.replace(/^https?:\/\//, "").replace(/\/.*$/, ""));
}

export function BotWidgetTab({ botUuid }: { botUuid: string }) {
  const [script, setScript] = useState<string | null>(null);
  const [copied, setCopied] = useState(false);
  const [widget, setWidget] = useState<Widget | null>(null);
  const [domainsInput, setDomainsInput] = useState("");
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    botsApi
      .embedScript(botUuid)
      .then((res) => setScript(res.script))
      .catch(() => setScript(`<script src="${env.widgetUrl}" data-bot-id="${botUuid}" async></script>`));

    botsApi
      .widget(botUuid)
      .then((res) => {
        setWidget(res);
        setDomainsInput(res.allowed_domains.join("\n"));
      })
      .catch(() => setWidget(null));
  }, [botUuid]);

  async function copy() {
    if (!script) return;
    await navigator.clipboard.writeText(script);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  function updateField<K extends keyof Widget>(key: K, value: Widget[K]) {
    setWidget((prev) => (prev ? { ...prev, [key]: value } : prev));
  }

  async function handleSave() {
    if (!widget) return;
    setSaving(true);
    try {
      const allowed_domains = normalizeDomains(domainsInput);
      const updated = await botsApi.updateWidget(botUuid, {
        theme: widget.theme,
        position: widget.position,
        primary_color: widget.primary_color,
        greeting_message: widget.greeting_message,
        placeholder_text: widget.placeholder_text,
        show_branding: widget.show_branding,
        allowed_domains,
        is_active: widget.is_active,
      });
      setWidget(updated);
      setDomainsInput(updated.allowed_domains.join("\n"));
      toast.success("Widget settings saved.");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not save widget settings.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="space-y-6">
      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Embed on your website</CardTitle>
            <CardDescription>
              Paste this script tag before the closing <code>&lt;/body&gt;</code> tag.
            </CardDescription>
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
            <p className="mt-3 text-xs text-muted-foreground">Works on WordPress, Shopify, Webflow, or any custom HTML site.</p>
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

      <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
        <Card>
          <CardHeader>
            <CardTitle>Appearance</CardTitle>
            <CardDescription>How the widget looks and behaves on your site.</CardDescription>
          </CardHeader>
          {widget === null ? (
            <CardContent className="space-y-4">
              <Skeleton className="h-10" />
              <Skeleton className="h-10" />
              <Skeleton className="h-10" />
            </CardContent>
          ) : (
            <>
              <CardContent className="space-y-5">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-1.5">
                    <Label htmlFor="widget-theme">Theme</Label>
                    <Select value={widget.theme} onValueChange={(v) => updateField("theme", v as Widget["theme"])}>
                      <SelectTrigger id="widget-theme">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="light">Light</SelectItem>
                        <SelectItem value="dark">Dark</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-1.5">
                    <Label htmlFor="widget-position">Position</Label>
                    <Select value={widget.position} onValueChange={(v) => updateField("position", v as Widget["position"])}>
                      <SelectTrigger id="widget-position">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="bottom-right">Bottom right</SelectItem>
                        <SelectItem value="bottom-left">Bottom left</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="widget-color">Accent color</Label>
                  <div className="flex items-center gap-3">
                    <input
                      id="widget-color"
                      type="color"
                      value={widget.primary_color ?? "#0d9488"}
                      onChange={(e) => updateField("primary_color", e.target.value)}
                      className="size-10 cursor-pointer rounded-lg border border-border bg-transparent p-1"
                    />
                    <Input
                      value={widget.primary_color ?? ""}
                      onChange={(e) => updateField("primary_color", e.target.value)}
                      placeholder="#0d9488"
                      className="w-32"
                    />
                  </div>
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="widget-greeting">Greeting message</Label>
                  <Textarea
                    id="widget-greeting"
                    rows={2}
                    maxLength={500}
                    value={widget.greeting_message ?? ""}
                    onChange={(e) => updateField("greeting_message", e.target.value)}
                  />
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="widget-placeholder">Input placeholder</Label>
                  <Input
                    id="widget-placeholder"
                    maxLength={150}
                    value={widget.placeholder_text ?? ""}
                    onChange={(e) => updateField("placeholder_text", e.target.value)}
                  />
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="widget-domains">Allowed domains</Label>
                  <Textarea
                    id="widget-domains"
                    rows={3}
                    placeholder={"example.com\napp.example.com"}
                    value={domainsInput}
                    onChange={(e) => setDomainsInput(e.target.value)}
                  />
                  <p className="text-xs text-muted-foreground">One domain per line. Leave empty to allow any domain.</p>
                </div>

                <div className="flex items-center justify-between rounded-xl border border-border p-3">
                  <div>
                    <p className="text-sm font-medium">Show &quot;Powered by PrimeWebKit&quot;</p>
                    <p className="text-xs text-muted-foreground">Displays branding under the chat input.</p>
                  </div>
                  <Switch checked={widget.show_branding} onCheckedChange={(v) => updateField("show_branding", v)} />
                </div>

                <div className="flex items-center justify-between rounded-xl border border-border p-3">
                  <div>
                    <p className="text-sm font-medium">Widget active</p>
                    <p className="text-xs text-muted-foreground">Turn the embedded widget on or off without removing the script.</p>
                  </div>
                  <Switch checked={widget.is_active} onCheckedChange={(v) => updateField("is_active", v)} />
                </div>
              </CardContent>
              <CardFooter className="justify-end">
                <Button isLoading={saving} onClick={handleSave}>
                  Save widget settings
                </Button>
              </CardFooter>
            </>
          )}
        </Card>

        <div className="lg:sticky lg:top-6 lg:self-start">
          <p className="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Live preview</p>
          <div className="relative h-80 overflow-hidden rounded-2xl border border-border bg-canvas">
            {widget && (
              <div
                className={cn(
                  "absolute bottom-4 flex w-64 flex-col overflow-hidden rounded-2xl border border-border bg-surface shadow-floating",
                  widget.position === "bottom-left" ? "left-4" : "right-4",
                )}
              >
                <div
                  className="flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-white"
                  style={{ backgroundColor: widget.primary_color || "#0d9488" }}
                >
                  <MessageCircle className="size-4" /> Chat with us
                </div>
                <div className="space-y-2 p-3">
                  <div className="max-w-[85%] rounded-xl rounded-tl-sm bg-muted px-3 py-2 text-xs">
                    {widget.greeting_message || "Hi! How can I help you today?"}
                  </div>
                </div>
                <div className="border-t border-border p-2">
                  <div className="rounded-full border border-border px-3 py-1.5 text-xs text-muted-foreground">
                    {widget.placeholder_text || "Type a message…"}
                  </div>
                  {widget.show_branding && (
                    <p className="mt-1.5 text-center text-[10px] text-muted-foreground">Powered by PrimeWebKit</p>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
