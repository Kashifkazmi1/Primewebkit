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
  created_at: string;
  updated_at: string;
}

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
}

export interface KnowledgeSource {
  id: string;
  type: "text" | "qa" | "website" | "document";
  source_name: string;
  source_url: string | null;
  status: "pending" | "processing" | "crawling" | "ready" | "failed" | (string & {});
  character_count: number | null;
  chunk_count: number | null;
  error_message: string | null;
  processed_at: string | null;
  created_at: string;
}

export interface Widget {
  id: string;
  theme: "light" | "dark";
  position: "bottom-right" | "bottom-left";
  primary_color: string | null;
  greeting_message: string | null;
  placeholder_text: string | null;
  show_branding: boolean;
  custom_css: string | null;
  allowed_domains: string[];
  is_active: boolean;
}

export interface Conversation {
  id: string;
  visitor_name: string | null;
  visitor_email: string | null;
  status: string;
  last_message_at: string | null;
  message_count: number;
  rating: number | null;
  created_at: string;
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
  conversation_id: string;
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
  prefix: string;
  last_used_at: string | null;
  created_at: string;
}

export interface ApiKeyWithSecret extends ApiKey {
  key: string;
}

export interface Plan {
  id: string;
  name: string;
  slug: string;
  price_monthly: number;
  price_yearly: number | null;
  currency: string;
  features: string[];
  limits: Record<string, number>;
  is_active: boolean;
}

export interface Subscription {
  id: string;
  plan: Plan;
  status: string;
  current_period_start: string;
  current_period_end: string;
  cancel_at_period_end: boolean;
}

export interface Invoice {
  id: string;
  amount: number;
  currency: string;
  status: string;
  issued_at: string;
  paid_at: string | null;
}

export interface Team {
  id: string;
  name: string;
  owner_id: string;
  created_at: string;
}

export interface TeamMember {
  id: string;
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

export interface AnalyticsSeriesPoint {
  bucket: string;
  total: number;
}

export interface BotAnalytics {
  conversations_by_period: AnalyticsSeriesPoint[];
  messages_by_period: AnalyticsSeriesPoint[];
  leads_by_period: AnalyticsSeriesPoint[];
  averages: {
    response_time_ms: number;
    tokens_per_message: number;
    cost_per_message: number;
  };
  most_asked_questions: { question: string; total: number }[];
  lead_conversion_rate: number;
  average_rating: number;
}

export interface UsageSummary {
  messages: { used: number; limit: number };
  knowledge_mb: { used: number; limit: number };
  storage_mb: { used: number; limit: number };
  bots: { used: number; limit: number };
  team_members: { used: number; limit: number };
}
