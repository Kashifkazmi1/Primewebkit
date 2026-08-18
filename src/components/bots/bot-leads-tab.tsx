"use client";

import { ChevronLeft, ChevronRight, Download, TriangleAlert, Users } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ApiError } from "@/lib/api/client";
import { botsApi } from "@/lib/api/endpoints";
import type { Lead, Pagination } from "@/lib/api/types";
import { formatDate } from "@/lib/utils";

const PER_PAGE = 20;

function sourceLabel(lead: Lead): string {
  const source = lead.metadata?.captured_via;
  if (source === "manual") return "Manual";
  if (source === "conversation") return "Conversation";
  return "—";
}

function toCsvValue(value: string | null | undefined): string {
  const text = value ?? "";
  return `"${text.replace(/"/g, '""')}"`;
}

export function BotLeadsTab({ botUuid, botName }: { botUuid: string; botName: string }) {
  const [leads, setLeads] = useState<Lead[] | null>(null);
  const [pagination, setPagination] = useState<Pagination | null>(null);
  const [page, setPage] = useState(1);
  const [error, setError] = useState<string | null>(null);
  const [exporting, setExporting] = useState(false);

  const load = useCallback(
    (targetPage: number) => {
      setError(null);
      botsApi
        .leads(botUuid, targetPage, PER_PAGE)
        .then((res) => {
          setLeads(res.data);
          setPagination(res.pagination);
        })
        .catch((err) => {
          setLeads([]);
          setError(err instanceof ApiError ? err.message : "Could not load leads.");
        });
    },
    [botUuid],
  );

  useEffect(() => {
    setLeads(null);
    load(page);
  }, [load, page]);

  async function handleExportCsv() {
    setExporting(true);
    try {
      const rows: Lead[] = [];
      const first = await botsApi.leads(botUuid, 1, 100);
      rows.push(...first.data);
      const lastPage = first.pagination?.last_page ?? 1;
      for (let p = 2; p <= lastPage; p++) {
        const next = await botsApi.leads(botUuid, p, 100);
        rows.push(...next.data);
      }

      const header = ["Name", "Email", "Phone", "Source", "Captured at"];
      const lines = rows.map((lead) =>
        [toCsvValue(lead.name), toCsvValue(lead.email), toCsvValue(lead.phone), toCsvValue(sourceLabel(lead)), toCsvValue(formatDate(lead.created_at))].join(","),
      );
      const csv = [header.join(","), ...lines].join("\n");

      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      const safeName = botName.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "") || "bot";
      link.href = url;
      link.download = `leads-${safeName}.csv`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    } finally {
      setExporting(false);
    }
  }

  if (leads === null) {
    return (
      <div className="space-y-2">
        <Skeleton className="h-12" />
        <Skeleton className="h-12" />
      </div>
    );
  }

  if (error) {
    return (
      <EmptyState icon={TriangleAlert} title="Couldn't load leads" description={error} />
    );
  }

  if (leads.length === 0) {
    return (
      <EmptyState
        icon={Users}
        title="No leads captured yet"
        description="Enable lead capture in this bot's Settings tab to collect visitor contact details."
      />
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">{pagination?.total ?? leads.length} leads captured</p>
        <Button variant="outline" size="sm" onClick={handleExportCsv} isLoading={exporting}>
          <Download className="size-4" /> Export CSV
        </Button>
      </div>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Phone</TableHead>
            <TableHead>Source</TableHead>
            <TableHead>Captured</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {leads.map((lead) => (
            <TableRow key={lead.id}>
              <TableCell>{lead.name || "—"}</TableCell>
              <TableCell>{lead.email || "—"}</TableCell>
              <TableCell>{lead.phone || "—"}</TableCell>
              <TableCell className="text-sm text-muted-foreground">{sourceLabel(lead)}</TableCell>
              <TableCell className="text-sm text-muted-foreground">{formatDate(lead.created_at)}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>

      {pagination && pagination.last_page > 1 && (
        <div className="flex items-center justify-between pt-1">
          <p className="text-xs text-muted-foreground">
            Page {pagination.page} of {pagination.last_page}
          </p>
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
            >
              <ChevronLeft className="size-4" /> Prev
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={page >= pagination.last_page}
              onClick={() => setPage((p) => p + 1)}
            >
              Next <ChevronRight className="size-4" />
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
