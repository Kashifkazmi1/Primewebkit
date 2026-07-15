import type { ReactNode } from "react";
import { Reveal } from "@/components/marketing/reveal";

export function PageHeader({
  eyebrow,
  title,
  description,
}: {
  eyebrow?: string;
  title: string;
  description?: ReactNode;
}) {
  return (
    <section className="relative overflow-hidden border-b border-border bg-surface-2 py-16 sm:py-20">
      <div className="bg-grid bg-radial-fade absolute inset-0 opacity-40" aria-hidden />
      <div className="container-page relative">
        <Reveal className="mx-auto max-w-2xl text-center">
          {eyebrow && <p className="text-sm font-semibold uppercase tracking-widest text-primary">{eyebrow}</p>}
          <h1 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">{title}</h1>
          {description && <p className="mt-4 text-muted-foreground">{description}</p>}
        </Reveal>
      </div>
    </section>
  );
}
