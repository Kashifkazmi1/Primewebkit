import type { Metadata } from "next";
import { Layers, ShieldCheck, Sparkles } from "lucide-react";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";

export const metadata: Metadata = {
  title: "About",
  description: "PrimeWebKit builds AI chatbots trained on your own content — our approach to support automation and why we built it this way.",
  alternates: { canonical: "/about" },
};

const principles = [
  {
    icon: Sparkles,
    title: "Grounded in your content",
    description:
      "We built PrimeWebKit around retrieval-augmented generation because generic chatbots that hallucinate answers erode trust. Every response is retrieved from content you control.",
  },
  {
    icon: Layers,
    title: "Built for real support teams",
    description:
      "Lead capture, webhooks, team roles, and analytics exist because support and growth teams need to act on conversations, not just have them.",
  },
  {
    icon: ShieldCheck,
    title: "Security as a first-class concern",
    description:
      "JWT auth, rate limiting, SSRF protection on crawling and webhooks, and prompt-injection defenses are built in from day one — see our Security page for the details.",
  },
];

export default function AboutPage() {
  return (
    <>
      <PageHeader
        eyebrow="About"
        title="We think support chatbots should actually know your product"
        description="PrimeWebKit exists because too many chatbots either hallucinate or can't be trusted with real customer conversations. We built a platform that trains on your actual content and gives your team the tools to act on what it learns."
      />
      <section className="container-page py-20">
        <div className="grid gap-6 sm:grid-cols-3">
          {principles.map((principle, index) => (
            <Reveal key={principle.title} delay={index * 0.08}>
              <div className="h-full rounded-2xl border border-border bg-surface p-6">
                <span className="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                  <principle.icon className="size-5" />
                </span>
                <h2 className="mt-4 font-display text-base font-semibold">{principle.title}</h2>
                <p className="mt-2 text-sm text-muted-foreground">{principle.description}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
