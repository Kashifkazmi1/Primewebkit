import { apiFetch } from "./client";

export interface WidgetBotConfig {
  id: string;
  name: string;
  avatar_url: string | null;
  welcome_message: string | null;
  primary_color: string | null;
}

export interface WidgetMessage {
  id: string;
  role: "user" | "assistant";
  content: string;
  created_at: string;
}

export interface WidgetConversation {
  id: string;
  status: string;
  title: string | null;
}

function getOrCreateId(key: string): string {
  if (typeof window === "undefined") return "";
  let value = window.localStorage.getItem(key);
  if (!value) {
    value = crypto.randomUUID();
    window.localStorage.setItem(key, value);
  }
  return value;
}

export function getSessionId(botUuid: string): string {
  return getOrCreateId(`pwk_session_${botUuid}`);
}

export function getFingerprint(): string {
  return getOrCreateId("pwk_fingerprint");
}

export const widgetApi = {
  config: (botUuid: string) =>
    apiFetch<{ bot: WidgetBotConfig; widget: Record<string, unknown> }>(`/widget/${botUuid}/config`, { skipAuth: true }),
  sendMessage: (botUuid: string, data: { session_id: string; fingerprint: string; message: string }) =>
    apiFetch<{ conversation: WidgetConversation; message: WidgetMessage; user_message: WidgetMessage }>(
      `/widget/${botUuid}/messages`,
      { method: "POST", body: data, skipAuth: true },
    ),
  captureLead: (botUuid: string, data: { session_id: string; name?: string; email?: string; phone?: string }) =>
    apiFetch<{ id: string }>(`/widget/${botUuid}/leads`, { method: "POST", body: data, skipAuth: true }),
  rateConversation: (botUuid: string, conversationUuid: string, rating: number, comment?: string) =>
    apiFetch<null>(`/widget/${botUuid}/conversations/${conversationUuid}/rate`, {
      method: "POST",
      body: { rating, comment },
      skipAuth: true,
    }),
};
