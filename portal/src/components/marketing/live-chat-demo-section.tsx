"use client";

import { ArrowRight, Sparkles } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Reveal } from "@/components/marketing/reveal";
import { env } from "@/lib/env";

const highlights = [
  "Trained on your own website, docs, and FAQs",
  "Streaming, ChatGPT-style answers powered by Gemini",
  "Captures leads mid-conversation, no forms required",
];

export function LiveChatDemoSection() {
  return (
    <section className="relative overflow-hidden py-24">
      <div
        className="pointer-events-none absolute -left-32 top-1/3 size-96 rounded-full bg-primary/20 blur-3xl"
        aria-hidden
      />
      <div
        className="pointer-events-none absolute -right-24 bottom-0 size-72 rounded-full bg-accent/20 blur-3xl"
        aria-hidden
      />

      <div className="container-page relative grid items-center gap-12 lg:grid-cols-[1.05fr_1fr]">
        <Reveal>
          <p className="text-sm font-semibold uppercase tracking-widest text-primary">Live demo</p>
          <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">
            This is a real chatbot. Go ahead, talk to it.
          </h2>
          <p className="mt-4 text-muted-foreground">
            No staged screenshots — the panel on the right is the actual PrimeWebKit chat experience, embedded with a
            single iframe. Ask it a question and watch it answer in real time.
          </p>
          <ul className="mt-6 space-y-3">
            {highlights.map((item) => (
              <li key={item} className="flex items-start gap-2.5 text-sm">
                <Sparkles className="mt-0.5 size-4 shrink-0 text-primary" />
                {item}
              </li>
            ))}
          </ul>
          <Button asChild size="lg" className="mt-8">
            <Link href="/register">
              Build your own in minutes <ArrowRight className="size-4" />
            </Link>
          </Button>
        </Reveal>

        <Reveal delay={0.1}>
          <div className="relative mx-auto w-full max-w-md overflow-hidden rounded-2xl border border-border bg-surface shadow-glow">
            <div className="flex items-center gap-2 border-b border-border bg-surface-2 px-4 py-2.5">
              <div className="flex gap-1.5">
                <span className="size-2.5 rounded-full bg-danger/60" />
                <span className="size-2.5 rounded-full bg-warning/60" />
                <span className="size-2.5 rounded-full bg-success/60" />
              </div>
              <div className="ml-2 flex-1 truncate rounded-md bg-surface px-3 py-1 text-center text-xs text-muted-foreground">
                yoursite.com
              </div>
            </div>
            <iframe
              src={`${env.chatHtmlUrl}?bot=${env.demoBotId}`}
              style={{ width: "100%", height: 600, border: 0 }}
              allow="clipboard-write"
              title="PrimeWebKit live chat demo"
              loading="lazy"
            />
          </div>
        </Reveal>
      </div>
    </section>
  );
}
