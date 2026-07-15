import type { Metadata } from "next";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { Badge } from "@/components/ui/badge";

export const metadata: Metadata = {
  title: "Changelog",
  description: "What's shipped in PrimeWebKit, release by release.",
  alternates: { canonical: "/changelog" },
};

const releases = [
  {
    tag: "Current",
    title: "Production hardening",
    items: [
      "Fixed a boolean-coercion bug where toggling a webhook off could be miscast to on.",
      "Webhooks no longer expose their signing secret after creation — it's shown exactly once.",
      "Closed an SSRF redirect bypass in the website crawler.",
      "Atomic usage-counter writes, removing a race condition under concurrent requests.",
      "Composite indexes for AI usage logs and message history — faster analytics queries.",
    ],
  },
  {
    tag: "Phase 5",
    title: "SaaS platform, monetization & administration",
    items: [
      "Super admin dashboard, user management, plans, and subscriptions.",
      "Teams, API key rotation, outgoing webhooks, analytics, notifications.",
      "White-label branding and plan-based feature gating.",
    ],
  },
  {
    tag: "Phase 4",
    title: "AI provider integration",
    items: [
      "Retrieval-augmented generation pipeline with Google Gemini.",
      "Streaming responses, regenerate, stop-generation, and suggested questions.",
      "Prompt-injection defenses and AI-specific rate limiting.",
    ],
  },
  {
    tag: "Phase 3",
    title: "Core chatbot domain",
    items: [
      "Bots, knowledge sources, document extraction, website crawler.",
      "Conversations, messages, widgets, leads, and API keys.",
    ],
  },
  {
    tag: "Phase 2",
    title: "Authentication & access control",
    items: ["JWT auth with refresh tokens, account lockout, and role-based access control."],
  },
];

export default function ChangelogPage() {
  return (
    <>
      <PageHeader eyebrow="Changelog" title="What's new" description="Every notable change, most recent first." />
      <section className="container-page py-20">
        <div className="mx-auto max-w-2xl space-y-10">
          {releases.map((release, index) => (
            <Reveal key={release.title} delay={index * 0.05} className="relative border-l border-border pl-6">
              <div className="absolute -left-[5px] top-1.5 size-2.5 rounded-full bg-primary" />
              <Badge variant="outline">{release.tag}</Badge>
              <h2 className="mt-2 font-display text-lg font-semibold">{release.title}</h2>
              <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                {release.items.map((item) => (
                  <li key={item} className="flex gap-2">
                    <span className="mt-1.5 size-1 shrink-0 rounded-full bg-muted-foreground" />
                    {item}
                  </li>
                ))}
              </ul>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
