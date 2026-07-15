import type { Metadata } from "next";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";

export const metadata: Metadata = {
  title: "Security",
  description: "How PrimeWebKit protects your data: authentication, SSRF protection, rate limiting, prompt-injection defenses, and how to report a vulnerability.",
  alternates: { canonical: "/security" },
};

const areas = [
  {
    title: "Authentication",
    detail: "Short-lived JWT access tokens with opaque, server-revocable refresh tokens stored hashed in the database. Account lockout after repeated failed logins.",
  },
  {
    title: "SQL injection",
    detail: "Every query runs through a parameterized query builder or prepared statements — user input is never concatenated into SQL.",
  },
  {
    title: "SSRF protection",
    detail: "Website crawling and outgoing webhook URLs are validated against private, loopback, link-local, and cloud-metadata address ranges — re-checked at every redirect hop, not just the initial request.",
  },
  {
    title: "Rate limiting",
    detail: "General API throttling plus a stricter, cost-aware limit on AI-generating endpoints to prevent abuse of the underlying model provider.",
  },
  {
    title: "Prompt-injection defense",
    detail: "Retrieved knowledge-base content is screened for injection patterns before being placed in a prompt; direct attempts in visitor messages are flagged.",
  },
  {
    title: "CORS & CSRF",
    detail: "Explicit origin allow-listing with credentialed responses only for exact matches, never a wildcard. Authentication is a Bearer token, never an ambient cookie, so cross-site request forgery doesn't apply.",
  },
];

export default function SecurityPage() {
  return (
    <>
      <PageHeader
        eyebrow="Security"
        title="Security is a first-class concern, not an afterthought"
        description="An overview of how PrimeWebKit protects your account, your chatbots, and your customers' data."
      />
      <section className="container-page py-20">
        <div className="grid gap-4 sm:grid-cols-2">
          {areas.map((area, index) => (
            <Reveal key={area.title} delay={index * 0.05}>
              <Card>
                <CardContent className="p-6">
                  <h2 className="font-display text-base font-semibold">{area.title}</h2>
                  <p className="mt-2 text-sm text-muted-foreground">{area.detail}</p>
                </CardContent>
              </Card>
            </Reveal>
          ))}
        </div>

        <Reveal delay={0.2} className="mx-auto mt-14 max-w-2xl rounded-2xl border border-border bg-surface-2 p-8 text-center">
          <Badge variant="primary" className="mx-auto w-fit">
            Responsible disclosure
          </Badge>
          <h2 className="mt-4 font-display text-xl font-semibold">Found a vulnerability?</h2>
          <p className="mt-2 text-sm text-muted-foreground">
            Email{" "}
            <a href="mailto:security@primewebkit.com" className="font-medium text-primary hover:underline">
              security@primewebkit.com
            </a>{" "}
            with reproduction steps and the affected endpoint. We investigate every report.
          </p>
        </Reveal>
      </section>
    </>
  );
}
