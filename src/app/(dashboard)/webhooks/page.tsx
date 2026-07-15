"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { Check, Copy, MoreVertical, Plus, Trash2, Webhook as WebhookIcon } from "lucide-react";
import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { EmptyState } from "@/components/ui/empty-state";
import { FieldError, Input, Label } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Switch } from "@/components/ui/switch";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ApiError } from "@/lib/api/client";
import { webhooksApi } from "@/lib/api/endpoints";
import type { Webhook, WebhookEvent, WebhookWithSecret } from "@/lib/api/types";
import { formatDateTime } from "@/lib/utils";
import { WebhookLogsDialog } from "@/components/webhooks/webhook-logs-dialog";

const createWebhookSchema = z.object({
  url: z.string().min(1, "A URL is required").url("Enter a valid URL, e.g. https://example.com/hooks"),
  events: z.array(z.string()).min(1, "Select at least one event"),
});
type CreateWebhookValues = z.infer<typeof createWebhookSchema>;

export default function WebhooksPage() {
  const [webhooks, setWebhooks] = useState<Webhook[] | null>(null);
  const [availableEvents, setAvailableEvents] = useState<WebhookEvent[]>([]);
  const [createOpen, setCreateOpen] = useState(false);
  const [createdSecret, setCreatedSecret] = useState<WebhookWithSecret | null>(null);
  const [logsFor, setLogsFor] = useState<Webhook | null>(null);

  async function load() {
    try {
      const list = await webhooksApi.list();
      setWebhooks(list);
    } catch (error) {
      setWebhooks([]);
      toast.error(error instanceof ApiError ? error.message : "Could not load webhooks.");
    }
  }

  useEffect(() => {
    load();
    webhooksApi
      .events()
      .then((res) => setAvailableEvents(res.events))
      .catch(() => setAvailableEvents([]));
  }, []);

  async function handleToggle(webhook: Webhook, active: boolean) {
    setWebhooks((prev) => prev?.map((w) => (w.id === webhook.id ? { ...w, is_active: active } : w)) ?? prev);
    try {
      await webhooksApi.toggle(webhook.id, active);
    } catch (error) {
      setWebhooks((prev) => prev?.map((w) => (w.id === webhook.id ? { ...w, is_active: !active } : w)) ?? prev);
      toast.error(error instanceof ApiError ? error.message : "Could not update the webhook.");
    }
  }

  async function handleDelete(webhook: Webhook) {
    try {
      await webhooksApi.remove(webhook.id);
      setWebhooks((prev) => prev?.filter((w) => w.id !== webhook.id) ?? prev);
      toast.success("Webhook deleted.");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not delete the webhook.");
    }
  }

  return (
    <div className="mx-auto max-w-5xl space-y-6">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 className="font-display text-2xl font-semibold tracking-tight">Webhooks</h1>
          <p className="text-sm text-muted-foreground">
            Get notified in real time when events happen across your chatbots.
          </p>
        </div>
        <CreateWebhookDialog
          open={createOpen}
          onOpenChange={setCreateOpen}
          availableEvents={availableEvents}
          onCreated={(webhook) => {
            setWebhooks((prev) => (prev ? [webhook, ...prev] : [webhook]));
            setCreatedSecret(webhook);
            setCreateOpen(false);
          }}
        />
      </div>

      {webhooks === null ? (
        <div className="space-y-3">
          <Skeleton className="h-16" />
          <Skeleton className="h-16" />
        </div>
      ) : webhooks.length === 0 ? (
        <EmptyState
          icon={WebhookIcon}
          title="No webhooks yet"
          description="Register an endpoint to receive events like new leads, completed chats, and subscription changes."
          action={
            <Button size="sm" onClick={() => setCreateOpen(true)}>
              <Plus className="size-4" /> Add webhook
            </Button>
          }
        />
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Endpoint</TableHead>
              <TableHead>Events</TableHead>
              <TableHead>Last triggered</TableHead>
              <TableHead>Active</TableHead>
              <TableHead className="sr-only">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {webhooks.map((webhook) => (
              <TableRow key={webhook.id}>
                <TableCell className="max-w-xs truncate font-mono text-xs">{webhook.url}</TableCell>
                <TableCell>
                  <div className="flex flex-wrap gap-1">
                    {webhook.events.slice(0, 3).map((event) => (
                      <Badge key={event} variant="outline">
                        {event}
                      </Badge>
                    ))}
                    {webhook.events.length > 3 && <Badge variant="neutral">+{webhook.events.length - 3}</Badge>}
                  </div>
                </TableCell>
                <TableCell className="text-sm text-muted-foreground">
                  {webhook.last_triggered_at ? formatDateTime(webhook.last_triggered_at) : "Never"}
                </TableCell>
                <TableCell>
                  <Switch
                    checked={webhook.is_active}
                    onCheckedChange={(checked) => handleToggle(webhook, checked)}
                    aria-label={`${webhook.is_active ? "Disable" : "Enable"} webhook for ${webhook.url}`}
                  />
                </TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon" aria-label="Webhook actions">
                        <MoreVertical className="size-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem onSelect={() => setLogsFor(webhook)}>View delivery logs</DropdownMenuItem>
                      <DropdownMenuItem onSelect={() => handleDelete(webhook)} className="text-danger focus:text-danger">
                        <Trash2 className="size-4" /> Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      <SecretRevealDialog
        webhook={createdSecret}
        onClose={() => setCreatedSecret(null)}
      />
      {logsFor && <WebhookLogsDialog webhook={logsFor} onOpenChange={(open) => !open && setLogsFor(null)} />}
    </div>
  );
}



function CreateWebhookDialog({
  open,
  onOpenChange,
  availableEvents,
  onCreated,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  availableEvents: WebhookEvent[];
  onCreated: (webhook: WebhookWithSecret) => void;
}) {
  const {
    register,
    handleSubmit,
    watch,
    setValue,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<CreateWebhookValues>({
    resolver: zodResolver(createWebhookSchema),
    defaultValues: { url: "", events: [] },
  });
  const selectedEvents = watch("events") ?? [];

  function toggleEvent(event: string, checked: boolean) {
    const next = checked ? [...selectedEvents, event] : selectedEvents.filter((e) => e !== event);
    setValue("events", next, { shouldValidate: true });
  }

  async function onSubmit(values: CreateWebhookValues) {
    try {
      const webhook = await webhooksApi.create({ url: values.url, events: values.events as WebhookEvent[] });
      reset({ url: "", events: [] });
      onCreated(webhook);
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not create the webhook.");
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogTrigger asChild>
        <Button>
          <Plus className="size-4" /> Add webhook
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add webhook endpoint</DialogTitle>
          <DialogDescription>
            We&apos;ll POST a signed JSON payload to this URL whenever a selected event occurs.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
          <div className="space-y-1.5">
            <Label htmlFor="url">Endpoint URL</Label>
            <Input id="url" placeholder="https://example.com/webhooks/primewebkit" {...register("url")} />
            <FieldError message={errors.url?.message} />
          </div>
          <div className="space-y-2">
            <Label>Events</Label>
            <div className="grid max-h-56 grid-cols-1 gap-2 overflow-y-auto rounded-xl border border-border p-3 sm:grid-cols-2">
              {availableEvents.map((event) => (
                <label key={event} className="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-muted">
                  <Checkbox
                    checked={selectedEvents.includes(event)}
                    onCheckedChange={(checked) => toggleEvent(event, checked === true)}
                  />
                  <span className="font-mono text-xs">{event}</span>
                </label>
              ))}
            </div>
            <FieldError message={errors.events?.message as string | undefined} />
          </div>
          <DialogFooter>
            <Button type="submit" isLoading={isSubmitting}>
              Create webhook
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function SecretRevealDialog({ webhook, onClose }: { webhook: WebhookWithSecret | null; onClose: () => void }) {
  const [copied, setCopied] = useState(false);

  async function copySecret() {
    if (!webhook) return;
    await navigator.clipboard.writeText(webhook.secret);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  return (
    <Dialog open={webhook !== null} onOpenChange={(open) => !open && onClose()}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Save your signing secret</DialogTitle>
          <DialogDescription>
            This secret is shown only once. Use it to verify the <code className="font-mono">X-Webhook-Signature</code>{" "}
            header on incoming requests. If you lose it, delete this webhook and create a new one.
          </DialogDescription>
        </DialogHeader>
        <div className="flex items-center gap-2 rounded-xl border border-border bg-muted p-3">
          <code className="flex-1 overflow-x-auto whitespace-nowrap font-mono text-xs">{webhook?.secret}</code>
          <Button variant="outline" size="icon" onClick={copySecret} aria-label="Copy secret">
            {copied ? <Check className="size-4 text-success" /> : <Copy className="size-4" />}
          </Button>
        </div>
        <DialogFooter>
          <Button onClick={onClose}>Done</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
