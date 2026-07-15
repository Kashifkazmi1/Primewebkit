import { Briefcase, HeadphonesIcon, ShoppingCart, UserPlus } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { Button } from "@/components/ui/button";

export const metadata: Metadata = {
  title: "Chatbot Templates",
  description: "Pre-built system prompts for support, sales, lead generation, and onboarding chatbots — start from a template and customize.",
  alternates: { canonical: "/templates" },
};

const templates = [
  {
    icon: HeadphonesIcon,
    name: "Support Agent",
    tone: "Friendly, patient",
    description: "Answers product questions from your help center and escalates when it doesn't know.",
  },
  {
    icon: ShoppingCart,
    name: "Ecommerce Assistant",
    tone: "Helpful, concise",
    description: "Handles sizing, shipping, and returns; captures cart-abandonment leads.",
  },
  {
    icon: Briefcase,
    name: "Sales Qualifier",
    tone: "Consultative",
    description: "Asks qualifying questions and captures contact details for your sales team.",
  },
  {
    icon: UserPlus,
    name: "Onboarding Guide",
    tone: "Encouraging, clear",
    description: "Walks new users through setup steps using your onboarding documentation.",
  },
];

export default function TemplatesPage() {
  return (
    <>
      <PageHeader
        eyebrow="Templates"
        title="Start from a proven system prompt"
        description="Every template is a starting point — a tone, a role, and a set of guardrails you customize with your own content."
      />
      <section className="container-page py-20">
        <div className="grid gap-4 sm:grid-cols-2">
          {templates.map((template, index) => (
            <Reveal key={template.name} delay={index * 0.05}>
              <div className="flex h-full flex-col rounded-2xl border border-border bg-surface p-6">
                <span className="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                  <template.icon className="size-5" />
                </span>
                <h2 className="mt-4 font-display text-base font-semibold">{template.name}</h2>
                <p className="mt-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">{template.tone}</p>
                <p className="mt-2 flex-1 text-sm text-muted-foreground">{template.description}</p>
                <Button asChild variant="outline" size="sm" className="mt-4 w-fit">
                  <Link href="/register">Use this template</Link>
                </Button>
              </div>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
