function required(name: string, value: string | undefined, fallback?: string): string {
  if (value && value.length > 0) return value;
  if (fallback !== undefined) return fallback;
  return "";
}

// The backend (this repo's PHP API) is deployed on its own subdomain,
// api.primewebkit.com, separate from this frontend at primewebkit.com —
// two origins, talking cross-origin via CORS (see backend CORS_ALLOWED_ORIGINS).
export const env = {
  apiUrl: required("NEXT_PUBLIC_API_URL", process.env.NEXT_PUBLIC_API_URL, "https://api.primewebkit.com/api/v1"),
  siteUrl: required("NEXT_PUBLIC_SITE_URL", process.env.NEXT_PUBLIC_SITE_URL, "https://primewebkit.com"),
  widgetUrl: required("NEXT_PUBLIC_WIDGET_URL", process.env.NEXT_PUBLIC_WIDGET_URL, "https://api.primewebkit.com/widget.js"),
  // Same default as the backend's config/google.php — a public OAuth
  // client id, not a secret. Overridable via env var per environment.
  googleClientId: required(
    "NEXT_PUBLIC_GOOGLE_CLIENT_ID",
    process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID,
    "1044212666179-nmo21qhhgr7hc4n8sdm34ccsgs5sdo84.apps.googleusercontent.com",
  ),
};
