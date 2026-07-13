import type { Metadata } from "next";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { Badge } from "@/components/ui/badge";

export const metadata: Metadata = {
  title: "API Reference",
  description: "REST API reference for PrimeWebKit — authentication, chatbots, knowledge sources, webhooks, and more.",
  alternates: { canonical: "/api" },
};

const methodVariant: Record<string, "success" | "primary" | "warning" | "danger"> = {
  GET: "primary",
  POST: "success",
  PUT: "warning",
  DELETE: "danger",
};

const groups = [
  {
    name: "Authentication",
    base: "/auth",
    endpoints: [
      ["POST", "/auth/register", "Create an account"],
      ["POST", "/auth/login", "Sign in with email and password"],
      ["POST", "/auth/google", "Sign in with a Google identity credential"],
      ["POST", "/auth/refresh", "Exchange a refresh token for a new access token"],
      ["POST", "/auth/logout", "Revoke the current refresh token"],
      ["GET", "/auth/me", "Get the current authenticated user"],
    ],
  },
  {
    name: "Chatbots",
    base: "/bots",
    endpoints: [
      ["GET", "/bots", "List your chatbots"],
      ["POST", "/bots", "Create a chatbot"],
      ["GET", "/bots/{uuid}", "Get a chatbot"],
      ["PUT", "/bots/{uuid}", "Update a chatbot"],
      ["DELETE", "/bots/{uuid}", "Delete a chatbot"],
      ["GET", "/bots/{uuid}/knowledge-sources", "List knowledge sources"],
      ["POST", "/bots/{uuid}/knowledge-sources/website", "Crawl a website as a knowledge source"],
      ["GET", "/bots/{uuid}/conversations", "List conversations"],
      ["GET", "/bots/{uuid}/analytics", "Get bot analytics"],
    ],
  },
  {
    name: "Webhooks",
    base: "/webhooks",
    endpoints: [
      ["GET", "/webhooks/events", "List supported event types"],
      ["GET", "/webhooks", "List your webhooks"],
      ["POST", "/webhooks", "Register a webhook endpoint"],
      ["PUT", "/webhooks/{uuid}", "Enable or disable a webhook"],
      ["DELETE", "/webhooks/{uuid}", "Remove a webhook"],
      ["GET", "/webhooks/{uuid}/logs", "View delivery logs"],
    ],
  },
  {
    name: "Public widget",
    base: "/widget",
    endpoints: [
      ["GET", "/widget/{botUuid}/config", "Get public bot + widget configuration"],
      ["POST", "/widget/{botUuid}/messages", "Send a visitor message"],
      ["POST", "/widget/{botUuid}/messages/stream", "Send a message with a streamed response"],
      ["POST", "/widget/{botUuid}/leads", "Capture a lead"],
    ],
  },
  {
    name: "Billing & teams",
    base: "/subscriptions, /teams, /api-keys",
    endpoints: [
      ["GET", "/subscriptions/plans", "List available plans"],
      ["GET", "/subscriptions/current", "Get your current subscription"],
      ["POST", "/teams/{uuid}/invite", "Invite a team member"],
      ["POST", "/api-keys", "Create an API key"],
    ],
  },
];

export default function ApiReferencePage() {
  return (
    <>
      <PageHeader
        eyebrow="API"
        title="REST API reference"
        description={
          <>All endpoints are versioned under <code>/api/v1</code>, return a consistent JSON envelope, and accept a Bearer JWT or API key.</>
        }
      />
      <section className="container-page space-y-10 py-20">
        {groups.map((group, index) => (
          <Reveal key={group.name} delay={index * 0.05}>
            <h2 className="font-display text-lg font-semibold">{group.name}</h2>
            <div className="mt-3 divide-y divide-border rounded-2xl border border-border bg-surface">
              {group.endpoints.map(([method, path, description]) => (
                <div key={path} className="flex flex-col gap-2 p-4 sm:flex-row sm:items-center">
                  <Badge variant={methodVariant[method]} className="w-fit font-mono">
                    {method}
                  </Badge>
                  <code className="font-mono text-sm">{path}</code>
                  <span className="text-sm text-muted-foreground sm:ml-auto">{description}</span>
                </div>
              ))}
            </div>
          </Reveal>
        ))}
      </section>
    </>
  );
}
