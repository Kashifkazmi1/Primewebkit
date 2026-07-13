import { Reveal } from "@/components/marketing/reveal";
import * as AccordionPrimitive from "@radix-ui/react-accordion";
import { ChevronDown } from "lucide-react";
import { faqs } from "@/lib/content/faqs";

export function FaqSection() {
  return (
    <section id="faq" className="container-page py-24">
      <Reveal className="mx-auto max-w-2xl text-center">
        <p className="text-sm font-semibold uppercase tracking-widest text-primary">FAQ</p>
        <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Frequently asked questions</h2>
      </Reveal>

      <Reveal delay={0.1} className="mx-auto mt-12 max-w-2xl">
        <AccordionPrimitive.Root type="single" collapsible className="space-y-3">
          {faqs.map((faq, index) => (
            <AccordionPrimitive.Item
              key={faq.question}
              value={`item-${index}`}
              className="rounded-2xl border border-border bg-surface px-5 data-[state=open]:shadow-elevated"
            >
              <AccordionPrimitive.Header>
                <AccordionPrimitive.Trigger className="group flex w-full items-center justify-between py-4 text-left text-sm font-medium focus-visible:outline-none">
                  {faq.question}
                  <ChevronDown className="size-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-180" />
                </AccordionPrimitive.Trigger>
              </AccordionPrimitive.Header>
              <AccordionPrimitive.Content className="overflow-hidden pb-4 text-sm text-muted-foreground data-[state=open]:animate-fade-in">
                {faq.answer}
              </AccordionPrimitive.Content>
            </AccordionPrimitive.Item>
          ))}
        </AccordionPrimitive.Root>
      </Reveal>
    </section>
  );
}
