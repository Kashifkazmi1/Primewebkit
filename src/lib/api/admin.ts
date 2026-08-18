import { apiFetch, apiFetchPaginated } from "./client";

export interface AdminOverview {
  total_users: number;
  total_bots: number;
  active_subscriptions: number;
  mrr: number;
  [key: string]: unknown;
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
