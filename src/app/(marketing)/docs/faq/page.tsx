import * as AccordionPrimitive from "@radix-ui/react-accordion";
import { ChevronDown } from "lucide-react";
import type { Metadata } from "next";
import { DocsShell } from "@/components/docs/docs-shell";
import { PageHeader } from "@/components/marketing/page-header";
import { JsonLd } from "@/components/seo/json-ld";
import { faqs } from "@/lib/content/faqs";
import { faqPageSchema } from "@/lib/seo/schema";

export const metadata: Metadata = {
  title: "FAQ",
  description: "Frequently asked questions about training, embedding, and managing PrimeWebKit chatbots.",
  alternates: { canonical: "/docs/faq" },
};

export default function DocsFaqPage() {
  return (
    <>
      <JsonLd data={faqPageSchema(faqs.map((f) => ({ question: f.question, answer: f.answer })))} />
      <PageHeader eyebrow="Documentation" title="Frequently asked questions" />
      <DocsShell active="/docs/faq">
        <AccordionPrimitive.Root type="single" collapsible className="space-y-3">
          {faqs.map((faq, index) => (
            <AccordionPrimitive.Item
              key={faq.question}
              value={`item-${index}`}
              className="rounded-2xl border border-border bg-surface px-5 data-[state=open]:shadow-elevated"
            >
              <AccordionPrimitive.Header>
                <AccordionPrimitive.Trigger className="group flex w-full items-center justify-between gap-4 py-4 text-left text-sm font-medium focus-visible:outline-none">
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
      </DocsShell>
    </>
  );
}
