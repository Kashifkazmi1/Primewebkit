import { Check } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Reveal } from "@/components/marketing/reveal";
import { pricingPlans } from "@/lib/content/pricing";
import { cn } from "@/lib/utils";

export function PricingPreview() {
  return (
    <section className="container-page py-24">
      <Reveal className="mx-auto max-w-2xl text-center">
        <p className="text-sm font-semibold uppercase tracking-widest text-primary">Pricing</p>
        <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">
          Simple pricing that scales with you
        </h2>
        <p className="mt-4 text-muted-foreground">Start free. Upgrade when you need more volume or white-label branding.</p>
      </Reveal>

      <div className="mt-14 grid gap-6 sm:grid-cols-3">
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

      <p className="mt-8 text-center text-sm text-muted-foreground">
        Need something bigger?{" "}
        <Link href="/contact" className="font-medium text-primary hover:underline">
          Talk to us about Enterprise
        </Link>
        .
      </p>
    </section>
  );
}
