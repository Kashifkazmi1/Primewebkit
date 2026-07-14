"use client";

import { Check, Copy, MessageCircle, PanelsTopLeft } from "lucide-react";
import { useState } from "react";
import { Reveal } from "@/components/marketing/reveal";
import { env } from "@/lib/env";

const IFRAME_SNIPPET = `<iframe
  src="${env.chatHtmlUrl}?bot=${env.demoBotId}"
  style="width:100%;height:600px;border:0;border-radius:12px"
  allow="clipboard-write"
  title="Chat with us">
</iframe>`;

export function TwoFormatsSection() {
  const [copied, setCopied] = useState(false);

  async function copy() {
    await navigator.clipboard.writeText(IFRAME_SNIPPET);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  return (
    <section className="container-page py-24">
      <div className="grid items-center gap-12 lg:grid-cols-2">
        <Reveal className="min-w-0">
          <p className="text-sm font-semibold uppercase tracking-widest text-primary">Two ways to deploy</p>
          <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">
            Floating bubble, or a full page — your choice
          </h2>
          <p className="mt-4 text-muted-foreground">
            The same chatbot works as a compact floating widget tucked into the corner of your site, or as a dedicated
            full-page chat experience you can link to or embed anywhere.
          </p>

          <div className="mt-8 space-y-4">
            <div className="flex gap-4 rounded-2xl border border-border bg-surface p-4 shadow-card">
              <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <MessageCircle className="size-5" />
              </span>
              <div>
                <h3 className="font-display text-base font-semibold">Floating widget</h3>
                <p className="mt-1 text-sm text-muted-foreground">
                  A small bubble in the corner of your site that expands into a chat window — the fastest way to add support
                  and lead capture without touching your layout.
                </p>
              </div>
            </div>
            <div className="flex gap-4 rounded-2xl border border-border bg-surface p-4 shadow-card">
              <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <PanelsTopLeft className="size-5" />
              </span>
              <div>
                <h3 className="font-display text-base font-semibold">Full-page chat</h3>
                <p className="mt-1 text-sm text-muted-foreground">
                  Drop the same chatbot into an iframe for a dedicated support page, a help center, or a link you share
                  directly with customers.
                </p>
              </div>
            </div>
          </div>
        </Reveal>

        <Reveal delay={0.1} className="min-w-0">
          <div className="min-w-0 overflow-hidden rounded-2xl border border-white/10 bg-[#0f1729] shadow-glow">
            <div className="flex items-center justify-between border-b border-white/10 px-4 py-2.5">
              <div className="flex gap-1.5">
                <span className="size-2.5 rounded-full bg-white/20" />
                <span className="size-2.5 rounded-full bg-white/20" />
                <span className="size-2.5 rounded-full bg-white/20" />
              </div>
              <button
                type="button"
                onClick={copy}
                className="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-white/60 transition-colors hover:bg-white/10 hover:text-white"
              >
                {copied ? <Check className="size-3.5 text-emerald-400" /> : <Copy className="size-3.5" />}
                {copied ? "Copied" : "Copy"}
              </button>
            </div>
            <pre className="overflow-x-auto p-5 font-mono text-xs leading-relaxed text-white/90 sm:text-sm">
              <code>{IFRAME_SNIPPET}</code>
            </pre>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
