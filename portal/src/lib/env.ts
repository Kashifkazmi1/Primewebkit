function required(name: string, value: string | undefined, fallback?: string): string {
  if (value && value.length > 0) return value;
  if (fallback !== undefined) return fallback;
  return "";
}

export const env = {
  apiUrl: required("NEXT_PUBLIC_API_URL", process.env.NEXT_PUBLIC_API_URL, "https://primewebkit.com/api/v1"),
  siteUrl: required("NEXT_PUBLIC_SITE_URL", process.env.NEXT_PUBLIC_SITE_URL, "https://primewebkit.com"),
  widgetUrl: required("NEXT_PUBLIC_WIDGET_URL", process.env.NEXT_PUBLIC_WIDGET_URL, "https://primewebkit.com/widget.js"),
  googleClientId: required("NEXT_PUBLIC_GOOGLE_CLIENT_ID", process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID),
};
