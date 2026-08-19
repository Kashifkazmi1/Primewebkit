export interface ApiEnvelope<T> {
  status: number;
  success: boolean;
  message: string;
  data: T;
  errors: Record<string, string[]> | Record<string, never>;
  pagination: Pagination | null;
}

export interface Pagination {
  total: number;
  page: number;
  per_page: number;
  last_page: number;
}

export interface User {
  id: string;
  name: string;
  email: string;
  email_verified: boolean;
  role: string | null;
  status: string;
  avatar_url: string | null;
  timezone: string;
  locale: string;
  last_login_at: string | null;
  created_at: string;
}

export interface AuthPayload {
  user: User;
  access_token: string;
  refresh_token: string;
  token_type: "Bearer";
  expires_in: number;
}

export interface Bot {
  id: string;
  name: string;
  description: string | null;
  avatar_url: string | null;
  status: "draft" | "training" | "active" | "archived";
  ai_provider: string;
  model: string;
  system_prompt: string | null;
  temperature: number;
  max_output_tokens: number;
  top_p: number;
  top_k: number;
  safety_settings: Record<string, unknown>;
  language: string;
  personality: string | null;
  tone: string | null;
  welcome_message: string | null;
  primary_color: string | null;
  is_public: boolean;
  lead_capture_enabled: boolean;
  lead_capture_fields: LeadCaptureField[];
  lead_capture_prompt: string | null;
  created_at: string;
  updated_at: string;
}

export type LeadCaptureField = "name" | "email" | "phone";

export interface CreateBotInput {
  name: string;
  description?: string;
  system_prompt?: string;
  model?: string;
  temperature?: number;
  welcome_message?: string;
  primary_color?: string;
  personality?: string;
  tone?: string;
  language?: string;
  lead_capture_enabled?: boolean;
  lead_capture_fields?: LeadCaptureField[];
  lead_capture_prompt?: string;
}

export interface Widget {
  id: string;
  theme: "light" | "dark";
  position: "bottom-right" | "bottom-left";
  primary_color: string | null;
  greeting_message: string | null;
  placeholder_text: string;
  show_branding: boolean;
  custom_css: string | null;
  allowed_domains: string[];
  is_active: boolean;
}

export interface UpdateWidgetInput {
  theme?: "light" | "dark";
  position?: "bottom-right" | "bottom-left";
  primary_color?: string;
  greeting_message?: string;
  placeholder_text?: string;
  show_branding?: boolean;
  custom_css?: string;
  allowed_domains?: string[];
  is_active?: boolean;
}

export interface KnowledgeSource {
  id: string;
  type: "text" | "qa" | "website" | "document";
  source_name: string | null;
  source_url: string | null;
  status: string;
  character_count: number | null;
  chunk_count: number | null;
  error_message: string | null;
  processed_at: string | null;
  created_at: string;
}

export interface Conversation {
  id: string;
  status: string;
  title: string | null;
  message_count: number;
  started_at: string;
  last_message_at: string | null;
  ended_at: string | null;
}

export interface Message {
  id: string;
  role: "user" | "assistant";
  content: string;
  created_at: string;
}

export interface Lead {
  id: string;
  name: string | null;
  email: string | null;
  phone: string | null;
  conversation_id: number | null;
  metadata: { captured_via?: "conversation" | "manual"; [key: string]: unknown } | null;
  created_at: string;
}

export const WEBHOOK_EVENTS = [
  "bot.created",
  "bot.deleted",
  "chat.started",
  "chat.completed",
  "lead.created",
  "subscription.created",
  "subscription.updated",
  "user.created",
  "knowledge.uploaded",
] as const;

export type WebhookEvent = (typeof WEBHOOK_EVENTS)[number];

export interface Webhook {
  id: string;
  url: string;
  events: WebhookEvent[];
  is_active: boolean;
  last_triggered_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface WebhookWithSecret extends Webhook {
  secret: string;
}

export interface WebhookLog {
  id: string;
  event: string;
  status_code: number | null;
  success: boolean;
  attempt: number;
  created_at: string;
}

export interface ApiKey {
  id: string;
  name: string;
  key_prefix: string;
  scopes: string[];
  last_used_at: string | null;
  expires_at: string | null;
  revoked: boolean;
  created_at: string;
}

export interface ApiKeyWithSecret extends ApiKey {
  key: string;
}

export interface Plan {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  monthly_price: number;
  yearly_price: number;
  currency: string;
  limits: {
    bots: number;
    messages_per_month: number;
    knowledge_mb: number;
    storage_mb: number;
    team_members: number;
  };
  features: {
    api_access: boolean;
    analytics: boolean;
    white_label: boolean;
    custom_domain: boolean;
    priority_support: boolean;
    streaming: boolean;
  };
  trial_days: number;
  is_active: boolean;
}

export interface Subscription {
  id: string;
  status: string;
  billing_cycle: string;
  provider: string;
  current_period_start: string;
  current_period_end: string;
  trial_ends_at: string | null;
  grace_period_ends_at: string | null;
  cancel_at_period_end: boolean;
  canceled_at: string | null;
  plan: Plan;
}

export interface Invoice {
  id: string;
  invoice_number: string;
  subtotal: number;
  discount_amount: number;
  tax_amount: number;
  total: number;
  currency: string;
  status: string;
  due_date: string | null;
  paid_at: string | null;
  created_at: string;
}

export interface Team {
  id: string;
  name: string;
  created_at: string;
}

export interface TeamMember {
  user_id: string;
  name: string;
  email: string;
  role: string;
  joined_at: string;
}

export interface NotificationItem {
  id: string;
  title: string;
  body: string | null;
  read_at: string | null;
  created_at: string;
}

export interface UsageSummary {
  messages: { used: number; limit: number };
  knowledge_mb: { used: number; limit: number };
  storage_mb: { used: number; limit: number };
  bots: { used: number; limit: number };
  team_members: { used: number; limit: number };
}
