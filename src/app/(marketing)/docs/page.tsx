import { BookOpen, Code2, KeyRound, Rocket, Webhook } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";

export const metadata: Metadata = {
  title: "Documentation",
  description: "Guides for building, embedding, and integrating PrimeWebKit AI chatbots.",
  alternates: { canonical: "/docs" },
};

const guides = [
  {
    icon: Rocket,
    title: "Quickstart",
    description: "Create your first chatbot, add a knowledge source, and embed it — in under ten minutes.",
    steps: [
      "Register an account and verify your email.",
      "Create a chatbot and write a system prompt describing its role.",
      "Add a website URL to crawl, or upload documents / Q&A pairs.",
      "Copy the embed script from the Widget tab and paste it before </body> on your site.",
    ],
  },
  {
    icon: KeyRound,
    title: "Authentication",
    description: "The dashboard uses short-lived JWT access tokens plus a revocable refresh token.",
    steps: [
      "POST /auth/login or /auth/register returns access_token, refresh_token, and expires_in.",
      "Send Authorization: Bearer <access_token> on every authenticated request.",
      "When a request 401s, POST /auth/refresh with your refresh_token to get a new pair.",
      "Server-to-server integrations should use an API key instead — see the API reference.",
    ],
  },
  {
    icon: Code2,
    title: "Embedding your chatbot",
    description: "Two ways to put a trained chatbot in front of visitors.",
    steps: [
      "Script tag: paste the snippet from a bot's Widget tab for a floating chat bubble.",
      "Full-page chat: link directly to /chat?id={botId} for a standalone ChatGPT-style interface.",
      "Both respect any allowed-domains restriction you've set on the widget.",
    ],
  },
  {
    icon: Webhook,
    title: "Webhooks",
    description: "React to platform events in real time.",
    steps: [
      "Register an endpoint URL and choose events like lead.created or chat.completed.",
      "Save the signing secret shown once at creation — it's never shown again.",
      "Verify the X-Webhook-Signature header on incoming requests using that secret.",
      "Check delivery history any time from the Webhooks page's log view.",
    ],
  },
];

export default function DocsPage() {
  return (
    <>
      <PageHeader
        eyebrow="Documentation"
        title="Everything you need to build with PrimeWebKit"
        description="Practical guides for the dashboard, the embed script, and the API."
      />
      <section className="container-page py-20">
        <div className="grid gap-4 sm:grid-cols-2">
          {guides.map((guide, index) => (
            <Reveal key={guide.title} delay={index * 0.05}>
              <div className="h-full rounded-2xl border border-border bg-surface p-6">
                <span className="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                  <guide.icon className="size-5" />
                </span>
                <h2 className="mt-4 font-display text-base font-semibold">{guide.title}</h2>
                <p className="mt-2 text-sm text-muted-foreground">{guide.description}</p>
                <ol className="mt-4 space-y-2 text-sm text-muted-foreground">
                  {guide.steps.map((step, i) => (
                    <li key={step} className="flex gap-2.5">
                      <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-[10px] font-semibold text-foreground">
                        {i + 1}
                      </span>
                      {step}
                    </li>
                  ))}
                </ol>
              </div>
            </Reveal>
          ))}
        </div>
        <Reveal delay={0.2} className="mx-auto mt-12 flex max-w-xl items-center justify-center gap-2 rounded-2xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
          <BookOpen className="size-4 shrink-0" />
          Looking for full endpoint details?{" "}
          <Link href="/api" className="font-medium text-primary hover:underline">
            View the API reference
          </Link>
        </Reveal>
      </section>
    </>
  );
}
