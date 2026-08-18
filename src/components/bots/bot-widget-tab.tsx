"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { Check, Copy } from "lucide-react";
import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input, Label, Textarea } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { Switch } from "@/components/ui/switch";
import { ApiError } from "@/lib/api/client";
import { botsApi } from "@/lib/api/endpoints";
import type { Widget } from "@/lib/api/types";
import { env } from "@/lib/env";

const schema = z.object({
  theme: z.enum(["light", "dark"]),
  position: z.enum(["bottom-right", "bottom-left"]),
  primary_color: z.string().max(20).optional().or(z.literal("")),
  greeting_message: z.string().max(500).optional().or(z.literal("")),
  placeholder_text: z.string().max(150),
  show_branding: z.boolean(),
  is_active: z.boolean(),
  allowed_domains: z.string(),
  custom_css: z.string().max(20000).optional().or(z.literal("")),
});
type Values = z.infer<typeof schema>;

function toValues(widget: Widget): Values {
  return {
    theme: widget.theme,
    position: widget.position,
    primary_color: widget.primary_color ?? "#4f46e5",
    greeting_message: widget.greeting_message ?? "",
    placeholder_text: widget.placeholder_text,
    show_branding: widget.show_branding,
    is_active: widget.is_active,
    allowed_domains: widget.allowed_domains.join("\n"),
    custom_css: widget.custom_css ?? "",
  };
}

function AppearanceForm({ widget, onUpdated }: { widget: Widget; onUpdated: (widget: Widget) => void }) {
  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { isSubmitting, isDirty },
  } = useForm<Values>({ resolver: zodResolver(schema), defaultValues: toValues(widget) });

  async function onSubmit(values: Values) {
    try {
      const updated = await botsApi.updateWidget(widget.id, {
        theme: values.theme,
        position: values.position,
        primary_color: values.primary_color || undefined,
        greeting_message: values.greeting_message || undefined,
        placeholder_text: values.placeholder_text,
        show_branding: values.show_branding,
        is_active: values.is_active,
        allowed_domains: values.allowed_domains
          .split(/[\n,]/)
          .map((d) => d.trim())
          .filter(Boolean),
        custom_css: values.custom_css || undefined,
      });
      onUpdated(updated);
      toast.success("Widget settings saved.");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not save widget settings.");
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Appearance &amp; behavior</CardTitle>
        <CardDescription>How the chat bubble looks and behaves on your site.</CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
          <div className="grid gap-5 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="theme">Theme</Label>
              <Select value={watch("theme")} onValueChange={(value) => setValue("theme", value as Values["theme"], { shouldDirty: true })}>
                <SelectTrigger id="theme">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="light">Light</SelectItem>
                  <SelectItem value="dark">Dark</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="position">Position</Label>
              <Select
                value={watch("position")}
                onValueChange={(value) => setValue("position", value as Values["position"], { shouldDirty: true })}
              >
                <SelectTrigger id="position">
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
            <Label htmlFor="primary_color">Primary color</Label>
            <div className="flex items-center gap-2">
              <input
                type="color"
                className="size-10 shrink-0 cursor-pointer rounded-lg border border-border"
                value={watch("primary_color") || "#4f46e5"}
                onChange={(e) => setValue("primary_color", e.target.value, { shouldDirty: true })}
                aria-label="Primary color"
              />
              <Input {...register("primary_color")} />
            </div>
          </div>

          <div className="grid gap-5 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="greeting_message">Greeting message</Label>
              <Textarea id="greeting_message" rows={2} {...register("greeting_message")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="placeholder_text">Input placeholder</Label>
              <Input id="placeholder_text" {...register("placeholder_text")} />
            </div>
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="allowed_domains">Allowed domains</Label>
            <Textarea
              id="allowed_domains"
              rows={3}
              placeholder="example.com&#10;www.example.com"
              {...register("allowed_domains")}
            />
            <p className="text-xs text-muted-foreground">
              One domain per line. Leave empty to allow the widget to load on any domain.
            </p>
          </div>

          <div className="flex flex-wrap gap-6">
            <label className="flex items-center gap-2.5 text-sm">
              <Switch checked={watch("show_branding")} onCheckedChange={(checked) => setValue("show_branding", checked, { shouldDirty: true })} />
              Show &quot;Powered by&quot; branding
            </label>
            <label className="flex items-center gap-2.5 text-sm">
              <Switch checked={watch("is_active")} onCheckedChange={(checked) => setValue("is_active", checked, { shouldDirty: true })} />
              Widget is active
            </label>
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="custom_css">Custom CSS (advanced)</Label>
            <Textarea id="custom_css" rows={4} className="font-mono text-xs" {...register("custom_css")} />
          </div>

          <div className="flex justify-end">
            <Button type="submit" isLoading={isSubmitting} disabled={!isDirty}>
              Save widget settings
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}

export function BotWidgetTab({ botUuid }: { botUuid: string }) {
  const [widget, setWidget] = useState<Widget | null>(null);
  const [script, setScript] = useState<string | null>(null);
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    botsApi.widget(botUuid).then(setWidget).catch(() => setWidget(null));
    botsApi
      .embedScript(botUuid)
      .then((res) => setScript(res.snippet))
      .catch(() =>
        setScript(
          `<script>\n  (function () {\n    var s = document.createElement('script');\n    s.src = "${env.apiUrl}/widget/${botUuid}/config";\n    s.async = true;\n    s.setAttribute('data-bot-id', "${botUuid}");\n    document.body.appendChild(s);\n  })();\n</script>`,
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
    <div className="space-y-6">
      {widget === null ? <Skeleton className="h-64" /> : <AppearanceForm widget={widget} onUpdated={setWidget} />}

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
    </div>
  );
}
