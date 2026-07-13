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
  created_at: string;
}

export const adminApi = {
  overview: () => apiFetch<AdminOverview>("/admin/dashboard"),
  users: (page = 1, perPage = 20) => apiFetchPaginated<AdminUser[]>("/admin/users", { query: { page, per_page: perPage } }),
  suspendUser: (uuid: string) => apiFetch<null>(`/admin/users/${uuid}/suspend`, { method: "POST" }),
  activateUser: (uuid: string) => apiFetch<null>(`/admin/users/${uuid}/activate`, { method: "POST" }),
};
