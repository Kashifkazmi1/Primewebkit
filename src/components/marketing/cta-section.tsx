import { ArrowRight } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Reveal } from "@/components/marketing/reveal";

export function CtaSection() {
  return (
    <section className="container-page pb-24">
      <Reveal>
        <div className="relative overflow-hidden rounded-3xl border border-border bg-gradient-to-br from-primary to-accent p-12 text-center text-primary-foreground shadow-glow sm:p-16">
          <div className="bg-grid absolute inset-0 opacity-10" aria-hidden />
          <h2 className="relative font-display text-3xl font-semibold tracking-tight sm:text-4xl">
            Your chatbot could be live in the next ten minutes
          </h2>
          <p className="relative mx-auto mt-4 max-w-xl text-primary-foreground/85">
            No credit card required. Crawl your site, customize the greeting, and embed a single script tag.
          </p>
          <div className="relative mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <Button asChild size="lg" variant="secondary" className="bg-white text-primary hover:bg-white/90">
              <Link href="/register">
                Start building for free <ArrowRight className="size-4" />
              </Link>
            </Button>
            <Button asChild size="lg" variant="outline" className="border-white/30 bg-transparent text-primary-foreground hover:bg-white/10">
              <Link href="/contact">Talk to sales</Link>
            </Button>
          </div>
        </div>
      </Reveal>
    </section>
  );
}
