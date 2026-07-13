"use client";

import { Check, Copy, Key, Plus, RefreshCw, Trash2 } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { EmptyState } from "@/components/ui/empty-state";
import { Input, Label } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ApiError } from "@/lib/api/client";
import { apiKeysApi } from "@/lib/api/endpoints";
import type { ApiKey, ApiKeyWithSecret } from "@/lib/api/types";
import { formatDate } from "@/lib/utils";

export default function ApiKeysPage() {
  const [keys, setKeys] = useState<ApiKey[] | null>(null);
  const [createOpen, setCreateOpen] = useState(false);
  const [revealed, setRevealed] = useState<ApiKeyWithSecret | null>(null);
  const [name, setName] = useState("");
  const [submitting, setSubmitting] = useState(false);

  async function load() {
    try {
      setKeys(await apiKeysApi.list());
    } catch {
      setKeys([]);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function handleCreate() {
    setSubmitting(true);
    try {
      const key = await apiKeysApi.create(name);
      setKeys((prev) => (prev ? [key, ...prev] : [key]));
      setRevealed(key);
      setCreateOpen(false);
      setName("");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not create the API key.");
    } finally {
      setSubmitting(false);
    }
  }

  async function handleRotate(uuid: string) {
    try {
      const key = await apiKeysApi.rotate(uuid);
      setKeys((prev) => prev?.map((k) => (k.id === uuid ? key : k)) ?? prev);
      setRevealed(key);
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not rotate this key.");
    }
  }

  async function handleDelete(uuid: string) {
    if (!confirm("Revoke this API key? Any integration using it will stop working immediately.")) return;
    try {
      await apiKeysApi.remove(uuid);
      setKeys((prev) => prev?.filter((k) => k.id !== uuid) ?? prev);
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not revoke this key.");
    }
  }

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 className="font-display text-2xl font-semibold tracking-tight">API keys</h1>
          <p className="text-sm text-muted-foreground">Authenticate server-to-server requests to the PrimeWebKit API.</p>
        </div>
        <Dialog open={createOpen} onOpenChange={setCreateOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="size-4" /> New key
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create API key</DialogTitle>
              <DialogDescription>Give it a name so you remember what it&apos;s used for.</DialogDescription>
            </DialogHeader>
            <div className="space-y-1.5">
              <Label htmlFor="key-name">Name</Label>
              <Input id="key-name" placeholder="Production server" value={name} onChange={(e) => setName(e.target.value)} />
            </div>
            <DialogFooter>
              <Button onClick={handleCreate} disabled={!name} isLoading={submitting}>
                Create key
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      {keys === null ? (
        <Skeleton className="h-32" />
      ) : keys.length === 0 ? (
        <EmptyState
          icon={Key}
          title="No API keys yet"
          description="Create a key to call the PrimeWebKit API from your own backend."
        />
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Key</TableHead>
              <TableHead>Last used</TableHead>
              <TableHead>Created</TableHead>
              <TableHead className="sr-only">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {keys.map((key) => (
              <TableRow key={key.id}>
                <TableCell>{key.name}</TableCell>
                <TableCell className="font-mono text-xs">{key.prefix}&hellip;</TableCell>
                <TableCell className="text-sm text-muted-foreground">
                  {key.last_used_at ? formatDate(key.last_used_at) : "Never"}
                </TableCell>
                <TableCell className="text-sm text-muted-foreground">{formatDate(key.created_at)}</TableCell>
                <TableCell>
                  <div className="flex justify-end gap-1">
                    <Button variant="ghost" size="icon" aria-label="Rotate key" onClick={() => handleRotate(key.id)}>
                      <RefreshCw className="size-4" />
                    </Button>
                    <Button variant="ghost" size="icon" aria-label="Revoke key" onClick={() => handleDelete(key.id)}>
                      <Trash2 className="size-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      <RevealKeyDialog apiKey={revealed} onClose={() => setRevealed(null)} />
    </div>
  );
}

function RevealKeyDialog({ apiKey, onClose }: { apiKey: ApiKeyWithSecret | null; onClose: () => void }) {
  const [copied, setCopied] = useState(false);

  async function copy() {
    if (!apiKey) return;
    await navigator.clipboard.writeText(apiKey.key);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  return (
    <Dialog open={apiKey !== null} onOpenChange={(open) => !open && onClose()}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Save your API key</DialogTitle>
          <DialogDescription>This key is shown only once. Store it somewhere safe.</DialogDescription>
        </DialogHeader>
        <div className="flex items-center gap-2 rounded-xl border border-border bg-muted p-3">
          <code className="flex-1 overflow-x-auto whitespace-nowrap font-mono text-xs">{apiKey?.key}</code>
          <Button variant="outline" size="icon" onClick={copy} aria-label="Copy key">
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
