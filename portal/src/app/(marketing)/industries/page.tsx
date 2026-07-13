import { Building2, GraduationCap, Home, ShoppingBag, Stethoscope, Wrench } from "lucide-react";
import type { Metadata } from "next";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";

export const metadata: Metadata = {
  title: "Industries",
  description: "How teams across ecommerce, SaaS, real estate, education, and services use PrimeWebKit chatbots.",
  alternates: { canonical: "/industries" },
};

const industries = [
  { icon: ShoppingBag, name: "Ecommerce", description: "Answer sizing, shipping, and return questions instantly, and capture leads for abandoned carts." },
  { icon: Building2, name: "SaaS", description: "Deflect repetitive setup and billing questions by training on your docs and help center." },
  { icon: Home, name: "Real estate", description: "Answer listing questions around the clock and capture buyer/renter leads automatically." },
  { icon: GraduationCap, name: "Education", description: "Help prospective students find program, admissions, and financial aid information." },
  { icon: Stethoscope, name: "Healthcare services", description: "Answer general clinic questions — hours, insurance accepted, appointment booking links." },
  { icon: Wrench, name: "Local services", description: "Qualify leads for quotes and answer service-area questions before a human ever picks up." },
];

export default function IndustriesPage() {
  return (
    <>
      <PageHeader eyebrow="Industries" title="Built for how your team actually works" description="A few of the ways teams put PrimeWebKit to work." />
      <section className="container-page py-20">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {industries.map((industry, index) => (
            <Reveal key={industry.name} delay={index * 0.05}>
              <div className="h-full rounded-2xl border border-border bg-surface p-6">
                <span className="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                  <industry.icon className="size-5" />
                </span>
                <h2 className="mt-4 font-display text-base font-semibold">{industry.name}</h2>
                <p className="mt-2 text-sm text-muted-foreground">{industry.description}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
