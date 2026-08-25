import { ArrowRight, CheckCircle2, Mail, User } from "lucide-react";
import Link from "next/link";
import { Reveal } from "@/components/marketing/reveal";

export function LeadCaptureHighlight() {
  return (
    <section className="container-page py-24">
      <div className="grid items-center gap-12 lg:grid-cols-2">
        <Reveal>
          <p className="text-sm font-semibold uppercase tracking-widest text-primary">Get leads on autopilot</p>
          <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">
            The bot asks for a name and email — you never have to
          </h2>
          <p className="mt-4 text-muted-foreground">
            Once the chatbot has actually helped a visitor, it naturally asks for their contact details before
            sharing pricing or booking a follow-up. No gated form, no popup — just a normal next step in a
            conversation that&apos;s already gone well.
          </p>
          <ul className="mt-6 space-y-3 text-sm">
            <li className="flex items-center gap-2">
              <span className="size-1.5 rounded-full bg-primary" /> Choose exactly which fields to collect
            </li>
            <li className="flex items-center gap-2">
              <span className="size-1.5 rounded-full bg-primary" /> Write a one-line instruction for when to ask
            </li>
            <li className="flex items-center gap-2">
              <span className="size-1.5 rounded-full bg-primary" /> Captured leads land on your dashboard instantly
            </li>
          </ul>
          <Link
            href="/ai-chatbot-for-lead-generation"
            className="mt-6 inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
          >
            See how lead capture works <ArrowRight className="size-4" />
          </Link>
        </Reveal>

        <Reveal delay={0.1}>
          <div className="mx-auto w-full max-w-sm rounded-3xl border border-border bg-surface shadow-floating">
            <div className="flex h-64 flex-col gap-3 overflow-hidden p-4">
              <div className="flex justify-start">
                <div className="max-w-[85%] rounded-2xl rounded-bl-sm bg-muted px-3.5 py-2 text-sm">
                  Our Growth plan starts at $99/mo — want me to email you the full breakdown?
                </div>
              </div>
              <div className="flex justify-end">
                <div className="max-w-[85%] rounded-2xl rounded-br-sm bg-primary px-3.5 py-2 text-sm text-primary-foreground">
                  Sure, it&apos;s alex@northline.co
                </div>
              </div>
              <div className="flex justify-start">
                <div className="max-w-[85%] rounded-2xl rounded-bl-sm bg-muted px-3.5 py-2 text-sm">
                  Got it — sending that over now. Anything else I can help with?
                </div>
              </div>
            </div>
            <div className="border-t border-border p-4">
              <div className="flex items-center gap-2 rounded-xl border border-success/30 bg-success/10 px-3.5 py-2.5 text-xs font-medium text-success">
                <CheckCircle2 className="size-4 shrink-0" /> Lead captured automatically
              </div>
              <div className="mt-3 grid grid-cols-2 gap-2">
                <div className="flex items-center gap-2 rounded-lg border border-border bg-surface-2 px-3 py-2 text-xs">
                  <User className="size-3.5 text-muted-foreground" /> Alex
                </div>
                <div className="flex items-center gap-2 rounded-lg border border-border bg-surface-2 px-3 py-2 text-xs">
                  <Mail className="size-3.5 text-muted-foreground" /> alex@northline.co
                </div>
              </div>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
