"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { FieldError, Input, Label, Textarea } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { ApiError } from "@/lib/api/client";
import { botsApi } from "@/lib/api/endpoints";
import type { Bot, LeadCaptureField } from "@/lib/api/types";

const LEAD_CAPTURE_FIELDS: { value: LeadCaptureField; label: string }[] = [
  { value: "name", label: "Name" },
  { value: "email", label: "Email" },
  { value: "phone", label: "Phone" },
];

const schema = z.object({
  name: z.string().min(2).max(150),
  description: z.string().max(1000).optional().or(z.literal("")),
  system_prompt: z.string().max(8000).optional().or(z.literal("")),
  welcome_message: z.string().max(500).optional().or(z.literal("")),
  primary_color: z.string().max(20).optional().or(z.literal("")),
  tone: z.string().max(50).optional().or(z.literal("")),
  status: z.enum(["draft", "training", "active", "archived"]),
  lead_capture_enabled: z.boolean(),
  lead_capture_fields: z.array(z.enum(["name", "email", "phone"])),
  lead_capture_prompt: z.string().max(500).optional().or(z.literal("")),
});
type Values = z.infer<typeof schema>;

export function BotSettingsTab({ bot, onUpdated }: { bot: Bot; onUpdated: (bot: Bot) => void }) {
  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors, isSubmitting, isDirty },
  } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: bot.name,
      description: bot.description ?? "",
      system_prompt: bot.system_prompt ?? "",
      welcome_message: bot.welcome_message ?? "",
      primary_color: bot.primary_color ?? "#6366f1",
      tone: bot.tone ?? "friendly",
      status: bot.status,
      lead_capture_enabled: bot.lead_capture_enabled ?? true,
      lead_capture_fields: bot.lead_capture_fields ?? ["name", "email"],
      lead_capture_prompt: bot.lead_capture_prompt ?? "",
    },
  });

  const leadCaptureEnabled = watch("lead_capture_enabled");
  const leadCaptureFields = watch("lead_capture_fields");
  const leadCapturePrompt = watch("lead_capture_prompt") ?? "";

  function toggleLeadCaptureField(field: LeadCaptureField, checked: boolean) {
    const current = leadCaptureFields ?? [];
    const next = checked ? [...current, field] : current.filter((f) => f !== field);
    setValue("lead_capture_fields", next, { shouldDirty: true });
  }

  async function onSubmit(values: Values) {
    try {
      const updated = await botsApi.update(bot.id, values);
      onUpdated(updated);
      toast.success("Chatbot updated.");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not save changes.");
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>General settings</CardTitle>
        <CardDescription>Name, personality, and behavior for this chatbot.</CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
          <div className="grid gap-5 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="name">Name</Label>
              <Input id="name" {...register("name")} />
              <FieldError message={errors.name?.message} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="status">Status</Label>
              <Select value={watch("status")} onValueChange={(value) => setValue("status", value as Values["status"], { shouldDirty: true })}>
                <SelectTrigger id="status">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="draft">Draft</SelectItem>
                  <SelectItem value="training">Training</SelectItem>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="archived">Archived</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="description">Description</Label>
            <Textarea id="description" rows={2} {...register("description")} />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="system_prompt">System prompt</Label>
            <Textarea
              id="system_prompt"
              rows={5}
              className="font-mono text-xs"
              placeholder="You are a helpful support assistant for..."
              {...register("system_prompt")}
            />
            <FieldError message={errors.system_prompt?.message} />
          </div>
          <div className="grid gap-5 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="welcome_message">Welcome message</Label>
              <Textarea id="welcome_message" rows={2} {...register("welcome_message")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="primary_color">Brand color</Label>
              <div className="flex items-center gap-2">
                <input
                  type="color"
                  className="size-10 shrink-0 cursor-pointer rounded-lg border border-border"
                  value={watch("primary_color") || "#6366f1"}
                  onChange={(e) => setValue("primary_color", e.target.value, { shouldDirty: true })}
                  aria-label="Brand color"
                />
                <Input {...register("primary_color")} />
              </div>
            </div>
          </div>
          <div className="rounded-2xl border border-border bg-surface-2 p-5">
            <div className="flex items-start justify-between gap-4">
              <div>
                <p className="font-display text-sm font-semibold">Lead capture</p>
                <p className="mt-1 text-sm text-muted-foreground">
                  When on, the chatbot asks visitors for these details during the conversation and saves them to
                  Leads automatically.
                </p>
              </div>
              <Switch
                checked={leadCaptureEnabled}
                onCheckedChange={(checked) => setValue("lead_capture_enabled", checked, { shouldDirty: true })}
                aria-label="Enable lead capture"
              />
            </div>

            {leadCaptureEnabled && (
              <div className="mt-5 space-y-5 border-t border-border pt-5">
                <div className="space-y-2">
                  <Label>Details to collect</Label>
                  <div className="flex flex-wrap gap-4">
                    {LEAD_CAPTURE_FIELDS.map((field) => (
                      <label key={field.value} className="flex items-center gap-2 text-sm">
                        <Checkbox
                          checked={leadCaptureFields?.includes(field.value)}
                          onCheckedChange={(checked) => toggleLeadCaptureField(field.value, checked === true)}
                        />
                        {field.label}
                      </label>
                    ))}
                  </div>
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="lead_capture_prompt">Capture instructions</Label>
                  <Textarea
                    id="lead_capture_prompt"
                    rows={3}
                    maxLength={500}
                    placeholder="Ask for the visitor's email before sharing pricing details."
                    {...register("lead_capture_prompt")}
                  />
                  <div className="flex items-center justify-between">
                    <FieldError message={errors.lead_capture_prompt?.message} />
                    <p className="text-xs text-muted-foreground">{leadCapturePrompt.length}/500</p>
                  </div>
                </div>
              </div>
            )}
          </div>
          <div className="flex justify-end">
            <Button type="submit" isLoading={isSubmitting} disabled={!isDirty}>
              Save changes
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
