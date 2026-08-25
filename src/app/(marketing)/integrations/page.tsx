import type { Metadata } from "next";
import Link from "next/link";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { Card, CardContent } from "@/components/ui/card";

export const metadata: Metadata = {
  title: "Integrations",
  description: "Connect PrimeWebKit to the tools you already use — webhooks, the REST API, Google sign-in, and one-script-tag embedding on any website platform.",
  alternates: { canonical: "/integrations" },
};

const integrations = [
  {
    title: "Webhooks",
    detail:
      "Register an endpoint URL and choose which events to receive — new leads, completed conversations, subscription changes, and more. Every delivery is signed so you can verify it genuinely came from PrimeWebKit.",
    href: "/docs",
    linkLabel: "Read the docs",
  },
  {
    title: "REST API",
    detail:
      "Scoped API keys with expiry give programmatic access to your bots, conversations, and leads — build your own dashboard, sync leads into a CRM, or automate anything the dashboard can do.",
    href: "/api",
    linkLabel: "View the API reference",
  },
  {
    title: "Google sign-in",
    detail: "Visitors and team members can sign in with Google instead of a password — one click, no separate account to manage.",
    href: "/register",
    linkLabel: "Try it",
  },
  {
    title: "Any website platform",
    detail:
      "The chat widget is one script tag — it works on WordPress, Shopify, Webflow, Squarespace, a hand-built site, or anywhere you can paste HTML, including via Google Tag Manager for platforms that restrict direct edits.",
    href: "/docs/install",
    linkLabel: "See platform guides",
  },
];

export default function IntegrationsPage() {
  return (
    <>
      <PageHeader
        eyebrow="Integrations"
        title="Connect the tools you already use"
        description="PrimeWebKit fits into an existing stack rather than replacing it — webhooks and an API for automation, plus a single script tag for the widget itself."
      />
      <section className="container-page py-20">
        <div className="grid gap-4 sm:grid-cols-2">
          {integrations.map((integration, index) => (
            <Reveal key={integration.title} delay={index * 0.05}>
              <Card className="flex h-full flex-col">
                <CardContent className="flex flex-1 flex-col p-6">
                  <h2 className="font-display text-base font-semibold">{integration.title}</h2>
                  <p className="mt-2 flex-1 text-sm text-muted-foreground">{integration.detail}</p>
                  <Link href={integration.href} className="mt-4 text-sm font-medium text-primary hover:underline">
                    {integration.linkLabel} →
                  </Link>
                </CardContent>
              </Card>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
