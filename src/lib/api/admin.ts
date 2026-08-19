import { apiFetch, apiFetchPaginated } from "./client";

export interface AdminOverview {
  users: { total: number; active: number; new_today: number; monthly_signups: number };
  bots: { total: number; active: number };
  conversations: { total: number; messages_today: number };
  ai: { total_requests: number; requests_today: number; total_tokens: number; estimated_cost: number };
  storage: { knowledge_mb: number; uploads_mb: number; disk_free_mb: number | null; disk_total_mb: number | null };
  revenue: { total_paid: number; this_month: number };
  subscriptions: Record<string, number>;
  pending_payments: number;
  webhooks: { success: number; failed: number; pending: number };
  system_health: { database: string; php_version: string; server_time: string };
  cron_jobs: { job_name: string; status: string; started_at: string | null; finished_at: string | null }[];
}

export interface AdminUser {
  id: string;
  name: string;
  email: string;
  status: string;
  role: string | null;
  last_login_at?: string | null;
  created_at: string;
}

export const adminApi = {
  overview: () => apiFetch<AdminOverview>("/admin/dashboard"),
  users: (params: { q?: string; status?: "active" | "suspended"; page?: number; perPage?: number } = {}) =>
    apiFetchPaginated<AdminUser[]>("/admin/users", {
      query: { q: params.q || undefined, status: params.status, page: params.page ?? 1, per_page: params.perPage ?? 20 },
    }),
  suspendUser: (uuid: string) => apiFetch<null>(`/admin/users/${uuid}/suspend`, { method: "POST" }),
  activateUser: (uuid: string) => apiFetch<null>(`/admin/users/${uuid}/activate`, { method: "POST" }),
};
