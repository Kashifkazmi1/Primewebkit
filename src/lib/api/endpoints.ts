import { apiFetch, apiFetchPaginated } from "./client";
import type {
  ApiKey,
  ApiKeyWithSecret,
  AuthPayload,
  Bot,
  Conversation,
  CreateBotInput,
  Invoice,
  KnowledgeSource,
  Lead,
  Message,
  NotificationItem,
  Plan,
  Subscription,
  Team,
  TeamMember,
  UsageSummary,
  User,
  Webhook,
  WebhookEvent,
  WebhookLog,
  WebhookWithSecret,
} from "./types";

export const authApi = {
  register: (data: { name: string; email: string; password: string }) =>
    apiFetch<AuthPayload>("/auth/register", { method: "POST", body: data, skipAuth: true }),
  login: (data: { email: string; password: string }) =>
    apiFetch<AuthPayload>("/auth/login", { method: "POST", body: data, skipAuth: true }),
  google: (credential: string) =>
    apiFetch<AuthPayload>("/auth/google", { method: "POST", body: { credential }, skipAuth: true }),
  logout: (refreshToken: string) => apiFetch<null>("/auth/logout", { method: "POST", body: { refresh_token: refreshToken } }),
  logoutAll: () => apiFetch<null>("/auth/logout-all", { method: "POST" }),
  forgotPassword: (email: string) =>
    apiFetch<null>("/auth/forgot-password", { method: "POST", body: { email }, skipAuth: true }),
  resetPassword: (data: { token: string; password: string }) =>
    apiFetch<null>("/auth/reset-password", { method: "POST", body: data, skipAuth: true }),
  resendVerification: (email: string) =>
    apiFetch<null>("/auth/resend-verification", { method: "POST", body: { email }, skipAuth: true }),
  verifyEmail: (token: string) => apiFetch<null>(`/auth/verify-email/${token}`, { skipAuth: true }),
  me: () => apiFetch<User>("/auth/me"),
  updateProfile: (data: Partial<Pick<User, "name" | "timezone" | "locale">>) =>
    apiFetch<User>("/auth/profile", { method: "PUT", body: data }),
  changePassword: (data: { current_password: string; new_password: string }) =>
    apiFetch<null>("/auth/change-password", { method: "POST", body: data }),
  deleteAccount: (password: string) => apiFetch<null>("/auth/account", { method: "DELETE", body: { password } }),
};

export const botsApi = {
  list: () => apiFetch<Bot[]>("/bots"),
  get: (uuid: string) => apiFetch<Bot>(`/bots/${uuid}`),
  create: (data: CreateBotInput) => apiFetch<Bot>("/bots", { method: "POST", body: data }),
  update: (uuid: string, data: Partial<CreateBotInput & { status: Bot["status"]; is_public: boolean }>) =>
    apiFetch<Bot>(`/bots/${uuid}`, { method: "PUT", body: data }),
  remove: (uuid: string) => apiFetch<null>(`/bots/${uuid}`, { method: "DELETE" }),
  reembed: (uuid: string) => apiFetch<null>(`/bots/${uuid}/reembed`, { method: "POST" }),

  knowledgeSources: (uuid: string) => apiFetch<KnowledgeSource[]>(`/bots/${uuid}/knowledge-sources`),
  addText: (uuid: string, data: { title: string; content: string }) =>
    apiFetch<KnowledgeSource>(`/bots/${uuid}/knowledge-sources/text`, { method: "POST", body: data }),
  addQa: (uuid: string, data: { question: string; answer: string }) =>
    apiFetch<KnowledgeSource>(`/bots/${uuid}/knowledge-sources/qa`, { method: "POST", body: data }),
  addWebsite: (uuid: string, data: { url: string }) =>
    apiFetch<KnowledgeSource>(`/bots/${uuid}/knowledge-sources/website`, { method: "POST", body: data }),
  removeKnowledgeSource: (uuid: string, sourceUuid: string) =>
    apiFetch<null>(`/bots/${uuid}/knowledge-sources/${sourceUuid}`, { method: "DELETE" }),

  widget: (uuid: string) => apiFetch<Record<string, unknown>>(`/bots/${uuid}/widget`),
  updateWidget: (uuid: string, data: Record<string, unknown>) =>
    apiFetch<Record<string, unknown>>(`/bots/${uuid}/widget`, { method: "PUT", body: data }),
  embedScript: (uuid: string) => apiFetch<{ script: string }>(`/bots/${uuid}/widget/embed-script`),

  conversations: (uuid: string, page = 1, perPage = 20) =>
    apiFetchPaginated<Conversation[]>(`/bots/${uuid}/conversations`, { query: { page, per_page: perPage } }),
  conversation: (uuid: string, conversationUuid: string) =>
    apiFetch<{ conversation: Conversation; messages: Message[] }>(`/bots/${uuid}/conversations/${conversationUuid}`),
  closeConversation: (uuid: string, conversationUuid: string) =>
    apiFetch<null>(`/bots/${uuid}/conversations/${conversationUuid}/close`, { method: "POST" }),

  leads: (uuid: string) => apiFetch<Lead[]>(`/bots/${uuid}/leads`),
  usageSummary: (uuid: string) => apiFetch<UsageSummary>(`/bots/${uuid}/usage/summary`),
  analytics: (uuid: string) => apiFetch<Record<string, unknown>>(`/bots/${uuid}/analytics`),
};

export const webhooksApi = {
  events: () => apiFetch<{ events: WebhookEvent[] }>("/webhooks/events"),
  list: () => apiFetch<Webhook[]>("/webhooks"),
  create: (data: { url: string; events: WebhookEvent[] }) =>
    apiFetch<WebhookWithSecret>("/webhooks", { method: "POST", body: data }),
  remove: (uuid: string) => apiFetch<null>(`/webhooks/${uuid}`, { method: "DELETE" }),
  toggle: (uuid: string, isActive: boolean) =>
    apiFetch<Webhook>(`/webhooks/${uuid}`, { method: "PUT", body: { is_active: isActive } }),
  logs: (uuid: string, page = 1, perPage = 20) =>
    apiFetchPaginated<WebhookLog[]>(`/webhooks/${uuid}/logs`, { query: { page, per_page: perPage } }),
};

export const apiKeysApi = {
  list: () => apiFetch<ApiKey[]>("/api-keys"),
  create: (name: string) => apiFetch<ApiKeyWithSecret>("/api-keys", { method: "POST", body: { name } }),
  remove: (uuid: string) => apiFetch<null>(`/api-keys/${uuid}`, { method: "DELETE" }),
  rotate: (uuid: string) => apiFetch<ApiKeyWithSecret>(`/api-keys/${uuid}/rotate`, { method: "POST" }),
};

export const subscriptionsApi = {
  plans: () => apiFetch<Plan[]>("/subscriptions/plans"),
  current: () => apiFetch<Subscription | null>("/subscriptions/current"),
  history: () => apiFetch<Subscription[]>("/subscriptions/history"),
  subscribe: (planUuid: string) => apiFetch<Subscription>("/subscriptions", { method: "POST", body: { plan_id: planUuid } }),
  cancel: (uuid: string) => apiFetch<null>(`/subscriptions/${uuid}/cancel`, { method: "POST" }),
  invoices: () => apiFetch<Invoice[]>("/subscriptions/invoices"),
};

export const teamsApi = {
  list: () => apiFetch<Team[]>("/teams"),
  create: (name: string) => apiFetch<Team>("/teams", { method: "POST", body: { name } }),
  get: (uuid: string) => apiFetch<Team>(`/teams/${uuid}`),
  members: (uuid: string) => apiFetch<TeamMember[]>(`/teams/${uuid}/members`),
  invite: (uuid: string, data: { email: string; role: string }) =>
    apiFetch<null>(`/teams/${uuid}/invite`, { method: "POST", body: data }),
  acceptInvitation: (token: string) => apiFetch<Team>(`/teams/invitations/${token}/accept`, { method: "POST" }),
  removeMember: (uuid: string, targetUserId: string) =>
    apiFetch<null>(`/teams/${uuid}/members/${targetUserId}`, { method: "DELETE" }),
  updateMemberRole: (uuid: string, targetUserId: string, role: string) =>
    apiFetch<null>(`/teams/${uuid}/members/${targetUserId}/role`, { method: "PUT", body: { role } }),
};

export const notificationsApi = {
  list: () => apiFetch<NotificationItem[]>("/notifications"),
  unreadCount: () => apiFetch<{ count: number }>("/notifications/unread-count"),
  markRead: (uuid: string) => apiFetch<null>(`/notifications/${uuid}/read`, { method: "POST" }),
  markAllRead: () => apiFetch<null>("/notifications/read-all", { method: "POST" }),
};

export const whiteLabelApi = {
  get: () => apiFetch<Record<string, unknown>>("/white-label"),
  update: (data: Record<string, unknown>) => apiFetch<Record<string, unknown>>("/white-label", { method: "PUT", body: data }),
};
