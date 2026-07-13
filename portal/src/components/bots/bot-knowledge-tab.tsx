"use client";

import { FileText, Globe, MessageSquareText, Plus, Trash2 } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
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
import { Input, Label, Textarea } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ApiError } from "@/lib/api/client";
import { botsApi } from "@/lib/api/endpoints";
import type { KnowledgeSource } from "@/lib/api/types";
import { formatDate } from "@/lib/utils";

const typeIcon = { text: FileText, qa: MessageSquareText, website: Globe, document: FileText } as const;

export function BotKnowledgeTab({ botUuid }: { botUuid: string }) {
  const [sources, setSources] = useState<KnowledgeSource[] | null>(null);
  const [open, setOpen] = useState(false);

  async function load() {
    try {
      setSources(await botsApi.knowledgeSources(botUuid));
    } catch {
      setSources([]);
    }
  }

  useEffect(() => {
    load();
  }, [botUuid]);

  async function handleRemove(sourceUuid: string) {
    try {
      await botsApi.removeKnowledgeSource(botUuid, sourceUuid);
      setSources((prev) => prev?.filter((s) => s.id !== sourceUuid) ?? prev);
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not remove this source.");
    }
  }

  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between space-y-0">
        <div>
          <CardTitle>Knowledge base</CardTitle>
          <CardDescription>Website pages, documents, and Q&amp;A pairs this chatbot answers from.</CardDescription>
        </div>
        <AddSourceDialog
          open={open}
          onOpenChange={setOpen}
          botUuid={botUuid}
          onAdded={(source) => {
            setSources((prev) => (prev ? [source, ...prev] : [source]));
            setOpen(false);
          }}
        />
      </CardHeader>
      <CardContent>
        {sources === null ? (
          <div className="space-y-2">
            <Skeleton className="h-14" />
            <Skeleton className="h-14" />
          </div>
        ) : sources.length === 0 ? (
          <EmptyState
            icon={FileText}
            title="No knowledge sources yet"
            description="Add a website to crawl, upload a document, or write Q&A pairs."
          />
        ) : (
          <div className="space-y-2">
            {sources.map((source) => {
              const Icon = typeIcon[source.type];
              return (
                <div key={source.id} className="flex items-center justify-between rounded-xl border border-border p-3">
                  <div className="flex items-center gap-3">
                    <span className="flex size-9 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                      <Icon className="size-4" />
                    </span>
                    <div>
                      <p className="text-sm font-medium">{source.title}</p>
                      <p className="text-xs text-muted-foreground">Added {formatDate(source.created_at)}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <Badge variant={source.status === "ready" ? "success" : "warning"}>{source.status}</Badge>
                    <Button variant="ghost" size="icon" aria-label="Remove source" onClick={() => handleRemove(source.id)}>
                      <Trash2 className="size-4" />
                    </Button>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function AddSourceDialog({
  open,
  onOpenChange,
  botUuid,
  onAdded,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  botUuid: string;
  onAdded: (source: KnowledgeSource) => void;
}) {
  const [submitting, setSubmitting] = useState(false);
  const [textTitle, setTextTitle] = useState("");
  const [textContent, setTextContent] = useState("");
  const [qaQuestion, setQaQuestion] = useState("");
  const [qaAnswer, setQaAnswer] = useState("");
  const [websiteUrl, setWebsiteUrl] = useState("");

  async function submit(fn: () => Promise<KnowledgeSource>) {
    setSubmitting(true);
    try {
      const source = await fn();
      onAdded(source);
      toast.success("Knowledge source added — training will begin shortly.");
    } catch (error) {
      toast.error(error instanceof ApiError ? error.message : "Could not add this source.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogTrigger asChild>
        <Button size="sm">
          <Plus className="size-4" /> Add source
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add knowledge source</DialogTitle>
          <DialogDescription>Website pages, raw text, or Q&amp;A pairs your chatbot can answer from.</DialogDescription>
        </DialogHeader>
        <Tabs defaultValue="website">
          <TabsList className="w-full">
            <TabsTrigger value="website" className="flex-1">
              Website
            </TabsTrigger>
            <TabsTrigger value="text" className="flex-1">
              Text
            </TabsTrigger>
            <TabsTrigger value="qa" className="flex-1">
              Q&amp;A
            </TabsTrigger>
          </TabsList>
          <TabsContent value="website" className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="website-url">Website URL</Label>
              <Input id="website-url" placeholder="https://example.com" value={websiteUrl} onChange={(e) => setWebsiteUrl(e.target.value)} />
            </div>
            <DialogFooter>
              <Button
                disabled={!websiteUrl}
                isLoading={submitting}
                onClick={() => submit(() => botsApi.addWebsite(botUuid, { url: websiteUrl }))}
              >
                Crawl website
              </Button>
            </DialogFooter>
          </TabsContent>
          <TabsContent value="text" className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="text-title">Title</Label>
              <Input id="text-title" value={textTitle} onChange={(e) => setTextTitle(e.target.value)} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="text-content">Content</Label>
              <Textarea id="text-content" rows={6} value={textContent} onChange={(e) => setTextContent(e.target.value)} />
            </div>
            <DialogFooter>
              <Button
                disabled={!textTitle || !textContent}
                isLoading={submitting}
                onClick={() => submit(() => botsApi.addText(botUuid, { title: textTitle, content: textContent }))}
              >
                Add text
              </Button>
            </DialogFooter>
          </TabsContent>
          <TabsContent value="qa" className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="qa-question">Question</Label>
              <Input id="qa-question" value={qaQuestion} onChange={(e) => setQaQuestion(e.target.value)} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="qa-answer">Answer</Label>
              <Textarea id="qa-answer" rows={4} value={qaAnswer} onChange={(e) => setQaAnswer(e.target.value)} />
            </div>
            <DialogFooter>
              <Button
                disabled={!qaQuestion || !qaAnswer}
                isLoading={submitting}
                onClick={() => submit(() => botsApi.addQa(botUuid, { question: qaQuestion, answer: qaAnswer }))}
              >
                Add Q&amp;A
              </Button>
            </DialogFooter>
          </TabsContent>
        </Tabs>
      </DialogContent>
    </Dialog>
  );
}
