"use client";

import { Users } from "lucide-react";
import { useEffect, useState } from "react";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { botsApi } from "@/lib/api/endpoints";
import type { Lead } from "@/lib/api/types";
import { formatDate } from "@/lib/utils";

export function BotLeadsTab({ botUuid }: { botUuid: string }) {
  const [leads, setLeads] = useState<Lead[] | null>(null);

  useEffect(() => {
    botsApi
      .leads(botUuid)
      .then(setLeads)
      .catch(() => setLeads([]));
  }, [botUuid]);

  if (leads === null) {
    return (
      <div className="space-y-2">
        <Skeleton className="h-12" />
        <Skeleton className="h-12" />
      </div>
    );
  }

  if (leads.length === 0) {
    return (
      <EmptyState
        icon={Users}
        title="No leads captured yet"
        description="Enable lead capture in your widget settings to collect visitor contact details."
      />
    );
  }

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Name</TableHead>
          <TableHead>Email</TableHead>
          <TableHead>Phone</TableHead>
          <TableHead>Captured</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {leads.map((lead) => (
          <TableRow key={lead.id}>
            <TableCell>{lead.name || "—"}</TableCell>
            <TableCell>{lead.email || "—"}</TableCell>
            <TableCell>{lead.phone || "—"}</TableCell>
            <TableCell className="text-sm text-muted-foreground">{formatDate(lead.created_at)}</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
