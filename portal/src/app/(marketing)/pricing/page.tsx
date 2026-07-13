import { Check } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { FaqSection } from "@/components/marketing/faq-section";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { Button } from "@/components/ui/button";
import { pricingPlans } from "@/lib/content/pricing";
import { cn } from "@/lib/utils";

export const metadata: Metadata = {
  title: "Pricing",
  description: "Simple, transparent pricing for PrimeWebKit AI chatbots — start free, upgrade for more volume, team seats, and white-label branding.",
  alternates: { canonical: "/pricing" },
};

export default function PricingPage() {
  return (
    <>
      <PageHeader
        eyebrow="Pricing"
        title="Plans that scale with your support volume"
        description="Every plan includes streaming answers, lead capture, and analytics. Upgrade for more chatbots, messages, and white-label branding."
      />

      <section className="container-page py-20">
        <div className="grid gap-6 sm:grid-cols-3">
          {pricingPlans.map((plan, index) => (
            <Reveal key={plan.name} delay={index * 0.08}>
              <div
                className={cn(
                  "flex h-full flex-col rounded-2xl border border-border bg-surface p-6",
                  plan.highlighted && "border-primary shadow-glow",
                )}
              >
                {plan.highlighted && (
                  <span className="mb-3 inline-flex w-fit items-center rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
                    Most popular
                  </span>
                )}
                <p className="font-display text-lg font-semibold">{plan.name}</p>
                <p className="mt-1 text-sm text-muted-foreground">{plan.tagline}</p>
                <p className="mt-4 font-display text-3xl font-semibold">
                  ${plan.price}
                  <span className="text-sm font-normal text-muted-foreground">/mo</span>
                </p>
                <ul className="mt-6 flex-1 space-y-2.5 text-sm">
                  {plan.features.map((feature) => (
                    <li key={feature} className="flex items-start gap-2">
                      <Check className="mt-0.5 size-4 shrink-0 text-success" /> {feature}
                    </li>
                  ))}
                </ul>
                <Button asChild className="mt-6" variant={plan.highlighted ? "primary" : "outline"}>
                  <Link href="/register">{plan.cta}</Link>
                </Button>
              </div>
            </Reveal>
          ))}
        </div>
        <p className="mt-10 text-center text-sm text-muted-foreground">
          Need higher volume, SSO, or a custom contract?{" "}
          <Link href="/contact" className="font-medium text-primary hover:underline">
            Talk to us about Enterprise
          </Link>
          .
        </p>
      </section>

      <FaqSection />
    </>
  );
}
