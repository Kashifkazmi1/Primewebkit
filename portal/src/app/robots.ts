import type { MetadataRoute } from "next";
import { env } from "@/lib/env";

export default function robots(): MetadataRoute.Robots {
  const disallow = [
    "/dashboard",
    "/bots",
    "/billing",
    "/team",
    "/api-keys",
    "/webhooks",
    "/settings",
    "/admin",
    "/login",
    "/register",
    "/forgot-password",
    "/reset-password",
    "/verify-email",
    "/chat",
  ];

  return {
    rules: [
      { userAgent: "*", allow: "/", disallow },
      { userAgent: "GPTBot", allow: "/", disallow },
      { userAgent: "OAI-SearchBot", allow: "/", disallow },
      { userAgent: "ClaudeBot", allow: "/", disallow },
      { userAgent: "Claude-SearchBot", allow: "/", disallow },
      { userAgent: "PerplexityBot", allow: "/", disallow },
      { userAgent: "Google-Extended", allow: "/", disallow },
      { userAgent: "CCBot", allow: "/", disallow },
    ],
    sitemap: `${env.siteUrl}/sitemap.xml`,
  };
}
