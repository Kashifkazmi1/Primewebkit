import { FileText, Globe, MessageSquareText, Rocket } from "lucide-react";
import { Reveal } from "@/components/marketing/reveal";

const steps = [
  { icon: Globe, title: "Add your content", description: "Crawl your website, upload documents, or write Q&A pairs." },
  { icon: MessageSquareText, title: "Customize behavior", description: "Set tone, personality, and a system prompt to match your brand." },
  { icon: FileText, title: "Preview & test", description: "Chat with your bot in a live preview before it goes public." },
  { icon: Rocket, title: "Embed anywhere", description: "One script tag, a full-page link, or an iframe — you choose." },
];

export function WorkflowShowcase() {
  return (
    <section className="bg-surface-2 py-24">
      <div className="container-page">
        <Reveal className="mx-auto max-w-2xl text-center">
          <p className="text-sm font-semibold uppercase tracking-widest text-primary">How it works</p>
          <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">From zero to live in four steps</h2>
        </Reveal>

        <div className="relative mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
          <div className="absolute left-0 right-0 top-6 hidden h-px bg-border lg:block" aria-hidden />
          {steps.map((step, index) => (
            <Reveal key={step.title} delay={index * 0.08} className="relative flex flex-col items-center text-center lg:items-start lg:text-left">
              <div className="relative z-10 flex size-12 items-center justify-center rounded-2xl border border-border bg-surface text-primary shadow-elevated">
                <step.icon className="size-5" />
              </div>
              <p className="mt-4 text-xs font-semibold text-muted-foreground">Step {index + 1}</p>
              <h3 className="mt-1 font-display text-lg font-semibold">{step.title}</h3>
              <p className="mt-2 text-sm text-muted-foreground">{step.description}</p>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}
