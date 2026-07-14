"use client";

import { Check, Copy } from "lucide-react";
import { useState } from "react";
import { Reveal } from "@/components/marketing/reveal";
import { env } from "@/lib/env";

const SNIPPET = `<script src="${env.widgetUrl}"\n  data-bot-id="${env.demoBotId}"\n  async></script>`;

export function InstallBand() {
  const [copied, setCopied] = useState(false);

  async function copy() {
    await navigator.clipboard.writeText(SNIPPET);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  }

  return (
    <section className="bg-[#0f1729] py-20 text-white">
      <div className="container-page">
        <Reveal className="mx-auto max-w-3xl text-center">
          <p className="text-sm font-semibold uppercase tracking-widest text-white/60">One tag, no developer required</p>
          <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">
            Install your chatbot in under 60 seconds
          </h2>
          <p className="mt-4 text-white/70">
            Copy the script, paste it before <code className="text-white/90">&lt;/body&gt;</code>, refresh the page — your
            chatbot appears instantly. Works on WordPress, Shopify, Webflow, or any custom HTML site.
          </p>
        </Reveal>

        <Reveal delay={0.1} className="relative mx-auto mt-10 max-w-xl">
          <div className="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04] shadow-[0_24px_60px_-24px_rgba(0,0,0,0.6)]">
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
            <pre className="overflow-x-auto p-5 font-mono text-sm leading-relaxed text-white/90">
              <code>{SNIPPET}</code>
            </pre>
          </div>
          <p className="mt-4 text-center text-sm text-white/50">
            This isn&apos;t a mockup — it&apos;s the real widget, running live on this page right now. Look for the chat
            bubble in the corner.
          </p>
        </Reveal>
      </div>
    </section>
  );
}
