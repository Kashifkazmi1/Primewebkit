import type { MetadataRoute } from "next";
import { env } from "@/lib/env";
import { blogPosts } from "@/lib/content/blog";

const staticRoutes = [
  { path: "/", priority: 1, changeFrequency: "weekly" as const },
  { path: "/features", priority: 0.9, changeFrequency: "monthly" as const },
  { path: "/pricing", priority: 0.9, changeFrequency: "monthly" as const },
  { path: "/templates", priority: 0.7, changeFrequency: "monthly" as const },
  { path: "/integrations", priority: 0.6, changeFrequency: "monthly" as const },
  { path: "/use-cases", priority: 0.7, changeFrequency: "monthly" as const },
  { path: "/industries", priority: 0.7, changeFrequency: "monthly" as const },
  { path: "/docs", priority: 0.8, changeFrequency: "weekly" as const },
  { path: "/api", priority: 0.7, changeFrequency: "weekly" as const },
  { path: "/blog", priority: 0.7, changeFrequency: "weekly" as const },
  { path: "/about", priority: 0.5, changeFrequency: "yearly" as const },
  { path: "/contact", priority: 0.5, changeFrequency: "yearly" as const },
  { path: "/security", priority: 0.5, changeFrequency: "monthly" as const },
  { path: "/help", priority: 0.6, changeFrequency: "monthly" as const },
  { path: "/status", priority: 0.3, changeFrequency: "daily" as const },
  { path: "/changelog", priority: 0.5, changeFrequency: "weekly" as const },
  { path: "/roadmap", priority: 0.4, changeFrequency: "monthly" as const },
  { path: "/careers", priority: 0.2, changeFrequency: "monthly" as const },
  { path: "/privacy", priority: 0.3, changeFrequency: "yearly" as const },
  { path: "/terms", priority: 0.3, changeFrequency: "yearly" as const },
  { path: "/cookies", priority: 0.3, changeFrequency: "yearly" as const },
  { path: "/login", priority: 0.2, changeFrequency: "yearly" as const },
  { path: "/register", priority: 0.4, changeFrequency: "yearly" as const },
];

export default function sitemap(): MetadataRoute.Sitemap {
  const now = new Date().toISOString();

  const staticEntries = staticRoutes.map((route) => ({
    url: `${env.siteUrl}${route.path}`,
    lastModified: now,
    changeFrequency: route.changeFrequency,
    priority: route.priority,
  }));

  const blogEntries = blogPosts.map((post) => ({
    url: `${env.siteUrl}/blog/${post.slug}`,
    lastModified: post.date,
    changeFrequency: "monthly" as const,
    priority: 0.6,
  }));

  return [...staticEntries, ...blogEntries];
}
