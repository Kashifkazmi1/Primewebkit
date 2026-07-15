import { BookOpenCheck, HeadphonesIcon, TrendingUp, Users2 } from "lucide-react";
import type { Metadata } from "next";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";

export const metadata: Metadata = {
  title: "Use Cases",
  description: "Support deflection, lead generation, internal knowledge bases, and onboarding — how teams use PrimeWebKit.",
  alternates: { canonical: "/use-cases" },
};

const cases = [
  {
    icon: HeadphonesIcon,
    title: "Support deflection",
    description: "Train on your help center so repetitive tickets get answered instantly, and only genuinely new questions reach your team.",
  },
  {
    icon: TrendingUp,
    title: "Lead generation",
    description: "Capture name, email, and phone mid-conversation and route new leads straight to your CRM via webhooks.",
  },
  {
    icon: BookOpenCheck,
    title: "Internal knowledge base",
    description: "Point a bot at internal docs and give your own team a fast way to find policies, processes, and product details.",
  },
  {
    icon: Users2,
    title: "Onboarding assistant",
    description: "Walk new users or new hires through setup steps conversationally, using your existing onboarding docs as the source of truth.",
  },
];

export default function UseCasesPage() {
  return (
    <>
      <PageHeader eyebrow="Use cases" title="More than a support widget" description="The same chatbot engine, applied to different jobs." />
      <section className="container-page py-20">
        <div className="grid gap-4 sm:grid-cols-2">
          {cases.map((useCase, index) => (
            <Reveal key={useCase.title} delay={index * 0.05}>
              <div className="h-full rounded-2xl border border-border bg-surface p-6">
                <span className="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                  <useCase.icon className="size-5" />
                </span>
                <h2 className="mt-4 font-display text-base font-semibold">{useCase.title}</h2>
                <p className="mt-2 text-sm text-muted-foreground">{useCase.description}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
