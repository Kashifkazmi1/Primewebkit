import { BookOpen, Code2, CreditCard, LifeBuoy, MessageCircleQuestion, Webhook } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";

export const metadata: Metadata = {
  title: "Help Center",
  description: "Find answers about getting started, billing, webhooks, and the PrimeWebKit API.",
  alternates: { canonical: "/help" },
};

const topics = [
  { icon: BookOpen, title: "Getting started", href: "/docs", description: "Create your first chatbot and go live." },
  { icon: Code2, title: "API & embedding", href: "/api", description: "Endpoints, authentication, and the widget script." },
  { icon: Webhook, title: "Webhooks", href: "/docs#webhooks", description: "Events, signing secrets, and delivery logs." },
  { icon: CreditCard, title: "Billing & plans", href: "/pricing", description: "Plan limits, upgrades, and invoices." },
  { icon: MessageCircleQuestion, title: "FAQ", href: "/#faq", description: "Common questions answered." },
  { icon: LifeBuoy, title: "Contact support", href: "/contact", description: "Can't find what you need? Reach out." },
];

export default function HelpCenterPage() {
  return (
    <>
      <PageHeader eyebrow="Help center" title="How can we help?" description="Browse by topic, or contact us directly." />
      <section className="container-page py-20">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {topics.map((topic, index) => (
            <Reveal key={topic.title} delay={index * 0.05}>
              <Link
                href={topic.href}
                className="flex h-full flex-col gap-3 rounded-2xl border border-border bg-surface p-6 transition-colors hover:border-border-strong hover:shadow-elevated"
              >
                <span className="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                  <topic.icon className="size-5" />
                </span>
                <div>
                  <p className="font-display text-base font-semibold">{topic.title}</p>
                  <p className="mt-1 text-sm text-muted-foreground">{topic.description}</p>
                </div>
              </Link>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
