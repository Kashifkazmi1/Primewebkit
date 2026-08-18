import { ChevronDown } from "lucide-react";
import Link from "next/link";
import * as AccordionPrimitive from "@radix-ui/react-accordion";
import { CtaSection } from "@/components/marketing/cta-section";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { Button } from "@/components/ui/button";
import { JsonLd } from "@/components/seo/json-ld";
import { blogPosts } from "@/lib/content/blog";
import { getPillar, type Pillar } from "@/lib/content/pillars";
import { articleSchema, breadcrumbSchema, faqPageSchema } from "@/lib/seo/schema";

export function PillarPage({ pillar }: { pillar: Pillar }) {
  const midpoint = Math.ceil(pillar.sections.length / 2);
  const relatedPillars = pillar.relatedPillars.map((slug) => getPillar(slug)).filter((p): p is Pillar => Boolean(p));
  const relatedPosts = blogPosts.filter((post) => pillar.relatedPosts.includes(post.slug));

  return (
    <>
      <JsonLd
        data={[
          articleSchema({
            headline: pillar.title,
            description: pillar.metaDescription,
            url: `/${pillar.slug}`,
          }),
          faqPageSchema(pillar.faqs),
          breadcrumbSchema([{ name: pillar.title, url: `/${pillar.slug}` }]),
        ]}
      />

      <PageHeader eyebrow={pillar.eyebrow} title={pillar.title} description={pillar.intro[0]} />

      <article className="container-page py-16">
        <div className="mx-auto grid max-w-5xl gap-12 lg:grid-cols-[220px_1fr]">
          <nav aria-label="On this page" className="hidden lg:block">
            <div className="sticky top-24 space-y-3">
              <p className="text-xs font-semibold uppercase tracking-widest text-muted-foreground">On this page</p>
              <ul className="space-y-2 text-sm">
                {pillar.sections.map((section) => (
                  <li key={section.id}>
                    <a href={`#${section.id}`} className="text-muted-foreground hover:text-primary">
                      {section.heading}
                    </a>
                  </li>
                ))}
                <li>
                  <a href="#faq" className="text-muted-foreground hover:text-primary">
                    FAQ
                  </a>
                </li>
              </ul>
            </div>
          </nav>

          <div className="min-w-0 space-y-14">
            {pillar.intro.length > 1 && (
              <div className="prose prose-slate max-w-none dark:prose-invert prose-headings:font-display prose-a:text-primary">
                {pillar.intro.slice(1).map((paragraph, i) => (
                  <p key={i}>{paragraph}</p>
                ))}
              </div>
            )}

            {pillar.sections.map((section, index) => (
              <Reveal key={section.id} delay={Math.min(index * 0.03, 0.15)}>
                <section id={section.id} className="prose prose-slate max-w-none dark:prose-invert prose-headings:font-display prose-a:text-primary">
                  <h2>{section.heading}</h2>
                  {section.paragraphs.map((paragraph, i) => (
                    <p key={i}>{paragraph}</p>
                  ))}
                  {section.subsections?.map((sub) => (
                    <div key={sub.heading}>
                      <h3>{sub.heading}</h3>
                      {sub.paragraphs.map((paragraph, i) => (
                        <p key={i}>{paragraph}</p>
                      ))}
                    </div>
                  ))}
                </section>
                {index === midpoint - 1 && <MidCta />}
              </Reveal>
            ))}

            <section id="faq">
              <Reveal className="space-y-6">
                <h2 className="font-display text-2xl font-semibold tracking-tight">Frequently asked questions</h2>
                <AccordionPrimitive.Root type="single" collapsible className="space-y-3">
                  {pillar.faqs.map((faq, index) => (
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
              </Reveal>
            </section>

            {(relatedPillars.length > 0 || relatedPosts.length > 0) && (
              <div className="grid gap-4 border-t border-border pt-10 sm:grid-cols-2">
                {relatedPillars.length > 0 && (
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Related guides</p>
                    <ul className="mt-3 space-y-2">
                      {relatedPillars.map((p) => (
                        <li key={p.slug}>
                          <Link href={`/${p.slug}`} className="text-sm font-medium text-primary hover:underline">
                            {p.title}
                          </Link>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
                {relatedPosts.length > 0 && (
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-widest text-muted-foreground">From the blog</p>
                    <ul className="mt-3 space-y-2">
                      {relatedPosts.map((post) => (
                        <li key={post.slug}>
                          <Link href={`/blog/${post.slug}`} className="text-sm font-medium text-primary hover:underline">
                            {post.title}
                          </Link>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      </article>

      <CtaSection />
    </>
  );
}

function MidCta() {
  return (
    <div className="not-prose my-10 flex flex-col items-center gap-4 rounded-2xl border border-primary/20 bg-primary/5 p-8 text-center sm:flex-row sm:justify-between sm:text-left">
      <div>
        <p className="font-display text-base font-semibold">See it running on your own content</p>
        <p className="mt-1 text-sm text-muted-foreground">First month free — no credit card required.</p>
      </div>
      <Button asChild>
        <Link href="/register">Start free</Link>
      </Button>
    </div>
  );
}
