"use client";

import { MessageSquare } from "lucide-react";
import { useEffect, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { botsApi } from "@/lib/api/endpoints";
import type { Conversation } from "@/lib/api/types";
import { formatDateTime } from "@/lib/utils";

export function BotConversationsTab({ botUuid }: { botUuid: string }) {
  const [conversations, setConversations] = useState<Conversation[] | null>(null);

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
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Visitor</TableHead>
          <TableHead>Messages</TableHead>
          <TableHead>Rating</TableHead>
          <TableHead>Status</TableHead>
          <TableHead>Last activity</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {conversations.map((conversation) => (
          <TableRow key={conversation.id}>
            <TableCell>{conversation.visitor_name || conversation.visitor_email || "Anonymous"}</TableCell>
            <TableCell>{conversation.message_count}</TableCell>
            <TableCell>{conversation.rating ? `${conversation.rating}/5` : "—"}</TableCell>
            <TableCell>
              <Badge variant={conversation.status === "open" ? "success" : "neutral"}>{conversation.status}</Badge>
            </TableCell>
            <TableCell className="text-sm text-muted-foreground">
              {conversation.last_message_at ? formatDateTime(conversation.last_message_at) : "—"}
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
