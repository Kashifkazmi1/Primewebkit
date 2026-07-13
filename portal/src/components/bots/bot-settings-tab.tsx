"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { FieldError, Input, Label, Textarea } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ApiError } from "@/lib/api/client";
import { botsApi } from "@/lib/api/endpoints";
import type { Bot } from "@/lib/api/types";

const schema = z.object({
  name: z.string().min(2).max(150),
  description: z.string().max(1000).optional().or(z.literal("")),
  system_prompt: z.string().max(8000).optional().or(z.literal("")),
  welcome_message: z.string().max(500).optional().or(z.literal("")),
  primary_color: z.string().max(20).optional().or(z.literal("")),
  tone: z.string().max(50).optional().or(z.literal("")),
  status: z.enum(["draft", "training", "active", "archived"]),
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
    },
  });

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
