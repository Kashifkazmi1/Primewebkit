import { env } from "@/lib/env";
import type { ApiEnvelope, AuthPayload } from "./types";

export class ApiError extends Error {
  readonly status: number;
  readonly errors: Record<string, string[]>;
  readonly code?: string;

  constructor(message: string, status: number, errors: Record<string, string[]> = {}) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.errors = errors;
  }

  firstFieldError(field: string): string | undefined {
    return this.errors[field]?.[0];
  }
}

const ACCESS_TOKEN_KEY = "pwk_access_token";
const REFRESH_TOKEN_KEY = "pwk_refresh_token";

let accessToken: string | null = null;
let refreshToken: string | null = null;
let refreshInFlight: Promise<boolean> | null = null;
const authListeners = new Set<(payload: AuthPayload | null) => void>();

function isBrowser(): boolean {
  return typeof window !== "undefined";
}

export function loadStoredTokens(): void {
  if (!isBrowser()) return;
  accessToken = window.localStorage.getItem(ACCESS_TOKEN_KEY);
  refreshToken = window.localStorage.getItem(REFRESH_TOKEN_KEY);
}

export function setTokens(access: string, refresh: string): void {
  accessToken = access;
  refreshToken = refresh;
  if (isBrowser()) {
    window.localStorage.setItem(ACCESS_TOKEN_KEY, access);
    window.localStorage.setItem(REFRESH_TOKEN_KEY, refresh);
  }
}

export function clearTokens(): void {
  accessToken = null;
  refreshToken = null;
  if (isBrowser()) {
    window.localStorage.removeItem(ACCESS_TOKEN_KEY);
    window.localStorage.removeItem(REFRESH_TOKEN_KEY);
  }
}

export function getAccessToken(): string | null {
  return accessToken;
}

export function getRefreshToken(): string | null {
  return refreshToken;
}

export function onAuthChange(listener: (payload: AuthPayload | null) => void): () => void {
  authListeners.add(listener);
  return () => authListeners.delete(listener);
}

export function emitAuthChange(payload: AuthPayload | null): void {
  authListeners.forEach((listener) => listener(payload));
}

interface RequestOptions {
  method?: "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
  body?: unknown;
  query?: Record<string, string | number | boolean | undefined>;
  signal?: AbortSignal;
  skipAuth?: boolean;
  skipRefreshRetry?: boolean;
}

function buildUrl(path: string, query?: RequestOptions["query"]): string {
  const url = new URL(path.replace(/^\//, ""), env.apiUrl.replace(/\/?$/, "/"));
  if (query) {
    Object.entries(query).forEach(([key, value]) => {
      if (value !== undefined) url.searchParams.set(key, String(value));
    });
  }
  return url.toString();
}

async function refreshAccessToken(): Promise<boolean> {
  if (!refreshToken) return false;
  if (refreshInFlight) return refreshInFlight;

  refreshInFlight = (async () => {
    try {
      const res = await fetch(buildUrl("/auth/refresh"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ refresh_token: refreshToken }),
      });
      const json = (await res.json()) as ApiEnvelope<{ access_token: string; refresh_token: string }>;
      if (!res.ok || !json.success) {
        clearTokens();
        emitAuthChange(null);
        return false;
      }
      setTokens(json.data.access_token, json.data.refresh_token);
      return true;
    } catch {
      return false;
    } finally {
      refreshInFlight = null;
    }
  })();

  return refreshInFlight;
}

export async function apiFetch<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { method = "GET", body, query, signal, skipAuth = false, skipRefreshRetry = false } = options;

  const headers: Record<string, string> = { Accept: "application/json" };
  if (body !== undefined) headers["Content-Type"] = "application/json";
  if (!skipAuth && accessToken) headers.Authorization = `Bearer ${accessToken}`;

  const res = await fetch(buildUrl(path, query), {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
    signal,
  });

  if (res.status === 204) return null as T;

  let json: ApiEnvelope<T>;
  try {
    json = (await res.json()) as ApiEnvelope<T>;
  } catch {
    throw new ApiError("The server returned an unexpected response. Please try again.", res.status);
  }

  if (!res.ok || !json.success) {
    const isExpiredToken = res.status === 401 && !skipAuth && !skipRefreshRetry;
    if (isExpiredToken) {
      const refreshed = await refreshAccessToken();
      if (refreshed) {
        return apiFetch<T>(path, { ...options, skipRefreshRetry: true });
      }
    }
    throw new ApiError(json.message ?? "Request failed.", json.status ?? res.status, json.errors as Record<string, string[]>);
  }

  return json.data;
}

export async function apiUpload<T>(path: string, formData: FormData): Promise<T> {
  const headers: Record<string, string> = { Accept: "application/json" };
  if (accessToken) headers.Authorization = `Bearer ${accessToken}`;

  const res = await fetch(buildUrl(path), { method: "POST", headers, body: formData });

  let json: ApiEnvelope<T>;
  try {
    json = (await res.json()) as ApiEnvelope<T>;
  } catch {
    throw new ApiError("The server returned an unexpected response. Please try again.", res.status);
  }

  if (!res.ok || !json.success) {
    if (res.status === 401) {
      const refreshed = await refreshAccessToken();
      if (refreshed) return apiUpload<T>(path, formData);
    }
    throw new ApiError(json.message ?? "Request failed.", json.status ?? res.status, json.errors as Record<string, string[]>);
  }

  return json.data;
}

export function apiFetchPaginated<T>(
  path: string,
  options: RequestOptions = {},
): Promise<{ data: T; pagination: ApiEnvelope<T>["pagination"] }> {
  return apiFetchWithEnvelope<T>(path, options);
}

async function apiFetchWithEnvelope<T>(
  path: string,
  options: RequestOptions = {},
): Promise<{ data: T; pagination: ApiEnvelope<T>["pagination"] }> {
  const { method = "GET", body, query, signal, skipAuth = false } = options;
  const headers: Record<string, string> = { Accept: "application/json" };
  if (body !== undefined) headers["Content-Type"] = "application/json";
  if (!skipAuth && accessToken) headers.Authorization = `Bearer ${accessToken}`;

  const res = await fetch(buildUrl(path, query), {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
    signal,
  });
  const json = (await res.json()) as ApiEnvelope<T>;

  if (!res.ok || !json.success) {
    if (res.status === 401 && !skipAuth) {
      const refreshed = await refreshAccessToken();
      if (refreshed) return apiFetchWithEnvelope<T>(path, options);
    }
    throw new ApiError(json.message ?? "Request failed.", json.status ?? res.status, json.errors as Record<string, string[]>);
  }

  return { data: json.data, pagination: json.pagination };
}
