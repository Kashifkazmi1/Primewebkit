"use client";

import { MessageSquare } from "lucide-react";
import { useEffect, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ApiError } from "@/lib/api/client";
import { botsApi } from "@/lib/api/endpoints";
import type { Conversation, Message } from "@/lib/api/types";
import { formatDateTime } from "@/lib/utils";

function ConversationTranscript({
  botUuid,
  conversation,
  onClose,
}: {
  botUuid: string;
  conversation: Conversation;
  onClose: () => void;
}) {
  const [messages, setMessages] = useState<Message[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setMessages(null);
    setError(null);
    botsApi
      .conversation(botUuid, conversation.id)
      .then((res) => setMessages(res.messages))
      .catch((err) => setError(err instanceof ApiError ? err.message : "Could not load this conversation."));
  }, [botUuid, conversation.id]);

  return (
    <Dialog open onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>{conversation.title || "Untitled conversation"}</DialogTitle>
          <DialogDescription>{conversation.message_count} messages</DialogDescription>
        </DialogHeader>

        {error ? (
          <p className="text-sm text-danger">{error}</p>
        ) : messages === null ? (
          <div className="space-y-2">
            <Skeleton className="h-10" />
            <Skeleton className="h-10" />
            <Skeleton className="h-10" />
          </div>
        ) : messages.length === 0 ? (
          <p className="text-sm text-muted-foreground">No messages in this conversation.</p>
        ) : (
          <div className="max-h-96 space-y-3 overflow-y-auto pr-1">
            {messages.map((message) => (
              <div key={message.id} className={message.role === "user" ? "flex justify-end" : "flex justify-start"}>
                <div
                  className={
                    message.role === "user"
                      ? "max-w-[85%] rounded-2xl rounded-br-sm bg-primary px-3.5 py-2 text-sm text-primary-foreground"
                      : "max-w-[85%] rounded-2xl rounded-bl-sm bg-muted px-3.5 py-2 text-sm"
                  }
                >
                  {message.content}
                </div>
              </div>
            ))}
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}

export function BotConversationsTab({ botUuid }: { botUuid: string }) {
  const [conversations, setConversations] = useState<Conversation[] | null>(null);
  const [selected, setSelected] = useState<Conversation | null>(null);

  useEffect(() => {
    botsApi
      .conversations(botUuid)
      .then((res) => setConversations(res.data))
      .catch(() => setConversations([]));
  }, [botUuid]);

  if (conversations === null) {
    return (
      <div className="space-y-2">
        <Skeleton className="h-12" />
        <Skeleton className="h-12" />
      </div>
    );
  }

  if (conversations.length === 0) {
    return (
      <EmptyState
        icon={MessageSquare}
        title="No conversations yet"
        description="Once visitors start chatting, transcripts will show up here."
      />
    );
  }

  return (
    <>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Conversation</TableHead>
            <TableHead>Messages</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Started</TableHead>
            <TableHead>Last activity</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {conversations.map((conversation) => (
            <TableRow
              key={conversation.id}
              className="cursor-pointer hover:bg-muted/50"
              onClick={() => setSelected(conversation)}
            >
              <TableCell>{conversation.title || "Untitled conversation"}</TableCell>
              <TableCell>{conversation.message_count}</TableCell>
              <TableCell>
                <Badge variant={conversation.status === "open" ? "success" : "neutral"}>{conversation.status}</Badge>
              </TableCell>
              <TableCell className="text-sm text-muted-foreground">{formatDateTime(conversation.started_at)}</TableCell>
              <TableCell className="text-sm text-muted-foreground">
                {conversation.last_message_at ? formatDateTime(conversation.last_message_at) : "—"}
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>

      {selected && <ConversationTranscript botUuid={botUuid} conversation={selected} onClose={() => setSelected(null)} />}
    </>
  );
}
