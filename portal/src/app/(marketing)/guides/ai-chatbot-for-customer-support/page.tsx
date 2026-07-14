import type { Metadata } from "next";
import Link from "next/link";
import { PageHeader } from "@/components/marketing/page-header";
import { LegalContent } from "@/components/marketing/legal-content";
import { CtaSection } from "@/components/marketing/cta-section";
import { JsonLd } from "@/components/seo/json-ld";
import { breadcrumbSchema } from "@/lib/seo/schema";

export const metadata: Metadata = {
  title: "AI Chatbot for Customer Support — The Complete Guide",
  description:
    "How AI chatbots trained on your own content reduce support ticket volume and response times, without the scripted, decision-tree failures of older chatbots.",
  alternates: { canonical: "/guides/ai-chatbot-for-customer-support" },
  openGraph: {
    title: "AI Chatbot for Customer Support — The Complete Guide",
    description: "How support teams deploy AI chatbots that actually resolve tickets instead of frustrating customers.",
    url: "/guides/ai-chatbot-for-customer-support",
    type: "article",
  },
};

export default function CustomerSupportGuidePage() {
  return (
    <>
      <JsonLd
        data={breadcrumbSchema([
          { name: "Guides", url: "/guides" },
          { name: "AI Chatbot for Customer Support", url: "/guides/ai-chatbot-for-customer-support" },
        ])}
      />
      <PageHeader
        eyebrow="Guide"
        title="AI Chatbot for Customer Support"
        description="How support teams use AI chatbots trained on their own content to cut ticket volume and response time — without the dead-end failures of old scripted bots."
      />
      <LegalContent>
        <h2>Why most support chatbots fail</h2>
        <p>
          The first generation of support chatbots worked off decision trees: a fixed set of buttons and scripted
          replies matched against exact phrasing. The moment a customer asked something slightly outside the script,
          the bot dead-ended into &ldquo;I don&rsquo;t understand&rdquo; — and the customer opened a ticket anyway. That
          experience is why so many businesses still associate &ldquo;chatbot&rdquo; with frustration rather than help.
        </p>
        <p>
          Modern AI chatbots work differently. Instead of matching scripts, a{" "}
          <Link href="/guides/what-is-a-rag-chatbot">retrieval-augmented generation (RAG) chatbot</Link>{" "}
          retrieves the most relevant passages from your actual help docs, FAQs, and policies, then generates a
          natural-language answer grounded in that content. The customer can phrase the question five different ways
          and still get a correct answer, because the system is matching meaning, not exact text.
        </p>

        <h2>What an AI support chatbot actually replaces</h2>
        <ul>
          <li>
            <strong>Tier-1 ticket deflection</strong>{" "}
            — shipping status, return policy, pricing tiers, and account questions that make up the bulk of support
            volume for most businesses.
          </li>
          <li>
            <strong>After-hours coverage</strong>{" "}
            — instant answers at 2am instead of a &ldquo;we&rsquo;ll respond within 24 hours&rdquo; auto-reply.
          </li>
          <li>
            <strong>Repetitive onboarding questions</strong>{" "}
            — the same setup or &ldquo;how do I&rdquo; questions every new customer asks in their first week.
          </li>
        </ul>
        <p>
          It doesn&rsquo;t replace a human agent for edge cases, account-specific issues, or anything emotionally
          charged — the goal is to absorb the repetitive volume so your team has time for the tickets that actually
          need a person.
        </p>

        <h2>What to train it on</h2>
        <p>
          Answer quality is bounded by content quality. A support chatbot is only as good as what you give it to
          learn from:
        </p>
        <ul>
          <li>Your existing help center or documentation (crawled directly from your site)</li>
          <li>Your most common support tickets, rewritten as Q&amp;A pairs</li>
          <li>Policy documents — shipping, returns, refunds, SLAs — uploaded as PDFs or plain text</li>
        </ul>
        <p>
          See the full breakdown of source types in{" "}
          <Link href="/blog/how-to-train-a-chatbot-on-your-website-content">
            how to train a chatbot on your website content
          </Link>
          .
        </p>

        <h2>Measuring whether it&rsquo;s working</h2>
        <p>
          Don&rsquo;t just look at conversation count. The metrics that actually indicate a support chatbot is earning
          its keep are ticket deflection rate, average response time, and the ratio of resolved-without-escalation
          conversations. We cover exactly what to track in{" "}
          <Link href="/blog/measuring-roi-of-ai-customer-support-chatbots">
            the real ROI of AI customer support chatbots
          </Link>
          , and every PrimeWebKit bot ships with an analytics tab that tracks these automatically.
        </p>

        <h2>Getting started</h2>
        <p>
          A support chatbot built on <Link href="/features">PrimeWebKit</Link>{" "}
          goes live in three steps: crawl your existing help content, customize the tone and welcome message, and
          paste a single script tag onto your site. No developer required. See how teams in different industries
          structure this on the <Link href="/industries">industries</Link> page, or compare plans on{" "}
          <Link href="/pricing">pricing</Link>.
        </p>
      </LegalContent>
      <CtaSection />
    </>
  );
}
