function required(name: string, value: string | undefined, fallback?: string): string {
  if (value && value.length > 0) return value;
  if (fallback !== undefined) return fallback;
  return "";
}

// The backend (this repo's PHP API) is deployed on its own subdomain,
// api.primewebkit.com, separate from this frontend at chat.primewebkit.com —
// two origins, talking cross-origin via CORS (see backend CORS_ALLOWED_ORIGINS).
const apiUrl = required("NEXT_PUBLIC_API_URL", process.env.NEXT_PUBLIC_API_URL, "https://api.primewebkit.com/api/v1");

export const env = {
  apiUrl,
  siteUrl: required("NEXT_PUBLIC_SITE_URL", process.env.NEXT_PUBLIC_SITE_URL, "https://chat.primewebkit.com"),
  // public/widget.js is a real static file served from the API's own
  // origin (same host as apiUrl, one level up from /api/v1) — the
  // self-contained embeddable chat bubble script.
  widgetJsUrl: apiUrl.replace(/\/api\/v\d+\/?$/, "") + "/widget.js",
  // Same default as the backend's config/google.php — a public OAuth
  // client id, not a secret. Overridable via env var per environment.
  googleClientId: required(
    "NEXT_PUBLIC_GOOGLE_CLIENT_ID",
    process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID,
    "1044212666179-nmo21qhhgr7hc4n8sdm34ccsgs5sdo84.apps.googleusercontent.com",
  ),
  // External checkout page — this app never collects payment details
  // itself, every upgrade CTA links out to this URL. The plan's slug is
  // appended as a query param (?plan=starter) so the checkout page can
  // route to the right price.
  upgradeUrl: required(
    "NEXT_PUBLIC_UPGRADE_BASIC_URL",
    process.env.NEXT_PUBLIC_UPGRADE_BASIC_URL,
    "https://pay.primewebkit.com/ai/basic-plan/",
  ),
  // The bot embedded as a live chat widget on this marketing site itself.
  widgetBotId: required("NEXT_PUBLIC_WIDGET_BOT_ID", process.env.NEXT_PUBLIC_WIDGET_BOT_ID, "868e0570-58f4-4e96-8d0a-9927e518190a"),
};
