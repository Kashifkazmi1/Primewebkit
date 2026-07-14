import { ArrowRight, Headset, Sparkles, Target } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";

export const metadata: Metadata = {
  title: "Guides",
  description:
    "In-depth guides on AI chatbots for customer support and lead generation, and how retrieval-augmented generation (RAG) actually works.",
  alternates: { canonical: "/guides" },
};

const guides = [
  {
    href: "/guides/ai-chatbot-for-customer-support",
    icon: Headset,
    title: "AI Chatbot for Customer Support",
    description:
      "How support teams use AI chatbots to cut response times and ticket volume without sacrificing answer quality.",
  },
  {
    href: "/guides/ai-chatbot-for-lead-generation",
    icon: Target,
    title: "AI Chatbot for Lead Generation & Sales",
    description: "Turning anonymous website visitors into qualified leads with conversational, context-aware capture.",
  },
  {
    href: "/guides/what-is-a-rag-chatbot",
    icon: Sparkles,
    title: "What Is a RAG Chatbot?",
    description: "Retrieval-augmented generation, explained plainly — and why it's the difference between a chatbot that helps and one that hallucinates.",
  },
];

export default function GuidesPage() {
  return (
    <>
      <PageHeader
        eyebrow="Guides"
        title="Everything you need to know about AI chatbots"
        description="Deep, practical guides — not marketing fluff — on how businesses actually deploy AI chatbots for support and sales."
      />
      <section className="container-page py-20">
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {guides.map((guide, index) => (
            <Reveal key={guide.href} delay={index * 0.06}>
              <Link
                href={guide.href}
                className="group flex h-full flex-col rounded-2xl border border-border bg-surface p-6 shadow-card transition-shadow hover:shadow-elevated"
              >
                <span className="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                  <guide.icon className="size-5" />
                </span>
                <h2 className="mt-4 font-display text-lg font-semibold">{guide.title}</h2>
                <p className="mt-2 flex-1 text-sm text-muted-foreground">{guide.description}</p>
                <span className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-primary">
                  Read the guide{" "}
                  <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                </span>
              </Link>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
