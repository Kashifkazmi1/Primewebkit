import type { Metadata } from "next";
import Link from "next/link";
import { DocsShell } from "@/components/docs/docs-shell";
import { PageHeader } from "@/components/marketing/page-header";

export const metadata: Metadata = {
  title: "Lead capture",
  description: "How to enable lead capture on a chatbot, choose which fields to collect, and find captured leads.",
  alternates: { canonical: "/docs/lead-capture" },
};

const steps = [
  {
    title: "1. Turn it on",
    body: "Open a bot's Settings tab and find the Lead capture card. Flip the switch — no other configuration is required for it to start working with sensible defaults (name and email).",
  },
  {
    title: "2. Choose which fields to collect",
    body: "Check any combination of name, email, and phone. Only checked fields are ever asked for — leave phone unchecked, for instance, if you only want an email address.",
  },
  {
    title: "3. Write capture instructions",
    body: "The instructions field (up to 500 characters) tells the AI when and how to ask. Keep it behavioral rather than scripted: \"ask for their email once you've answered their question, if they seem interested in pricing\" works better than a rigid script.",
  },
  {
    title: "4. Leads appear automatically",
    body: "Once a visitor provides the requested details, a lead record is created automatically and shows up on that bot's Leads tab within seconds — no extra step, no manual entry.",
  },
];

export default function DocsLeadCapturePage() {
  return (
    <>
      <PageHeader eyebrow="Documentation" title="Lead capture" description="Turn conversations into leads without a form." />
      <DocsShell active="/docs/lead-capture">
        <div className="space-y-6">
          {steps.map((step) => (
            <div key={step.title} className="rounded-2xl border border-border bg-surface p-6">
              <h2 className="font-display text-base font-semibold">{step.title}</h2>
              <p className="mt-2 text-sm text-muted-foreground">{step.body}</p>
            </div>
          ))}
        </div>

        <div className="space-y-4">
          <h2 className="font-display text-xl font-semibold">Where leads show up</h2>
          <p className="text-sm text-muted-foreground">
            Every captured lead — whether it came from mid-conversation capture or a manual submission — lands on
            that bot&apos;s Leads tab with its source, so you can tell the two apart. From there you can page through
            captured leads or export the full list to CSV at any time.
          </p>
        </div>

        <div className="rounded-2xl border border-dashed border-border p-6 text-sm text-muted-foreground">
          Want the full reasoning behind how to phrase the capture instruction so it doesn&apos;t feel like a form? Read
          the{" "}
          <Link href="/ai-chatbot-for-lead-generation" className="font-medium text-primary hover:underline">
            lead generation guide
          </Link>
          .
        </div>
      </DocsShell>
    </>
  );
}
