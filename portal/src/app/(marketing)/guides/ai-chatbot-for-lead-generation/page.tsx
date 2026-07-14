import type { Metadata } from "next";
import Link from "next/link";
import { PageHeader } from "@/components/marketing/page-header";
import { LegalContent } from "@/components/marketing/legal-content";
import { CtaSection } from "@/components/marketing/cta-section";
import { JsonLd } from "@/components/seo/json-ld";
import { breadcrumbSchema } from "@/lib/seo/schema";

export const metadata: Metadata = {
  title: "AI Chatbot for Lead Generation & Sales — The Complete Guide",
  description:
    "How to use an AI chatbot to qualify and capture leads from website visitors in real time, without gating the conversation behind a form.",
  alternates: { canonical: "/guides/ai-chatbot-for-lead-generation" },
  openGraph: {
    title: "AI Chatbot for Lead Generation & Sales — The Complete Guide",
    description: "Turning anonymous website traffic into qualified leads with a conversational chatbot.",
    url: "/guides/ai-chatbot-for-lead-generation",
    type: "article",
  },
};

export default function LeadGenerationGuidePage() {
  return (
    <>
      <JsonLd
        data={breadcrumbSchema([
          { name: "Guides", url: "/guides" },
          { name: "AI Chatbot for Lead Generation & Sales", url: "/guides/ai-chatbot-for-lead-generation" },
        ])}
      />
      <PageHeader
        eyebrow="Guide"
        title="AI Chatbot for Lead Generation & Sales"
        description="Most website traffic leaves without ever being identified. Here's how a chatbot converts more of it into a name, an email, and a reason to follow up."
      />
      <LegalContent>
        <h2>The problem with lead forms</h2>
        <p>
          A static lead form asks visitors to commit before they&rsquo;ve gotten anything in return — no wonder
          conversion rates for cold forms sit in the low single digits. A chatbot flips the order: it answers the
          visitor&rsquo;s actual question first, and only asks for contact details once the conversation has
          established real intent — a pricing question, a request for a demo, or a question the bot can&rsquo;t fully
          answer on its own.
        </p>
        <p>
          We go deeper on this ordering in{" "}
          <Link href="/blog/chatbot-lead-capture-without-annoying-visitors">
            capturing leads from chat without turning the conversation into a form
          </Link>
          .
        </p>

        <h2>What qualifies as a good lead signal</h2>
        <ul>
          <li>Asking about pricing, plans, or enterprise features</li>
          <li>Asking whether the product supports a specific integration or use case</li>
          <li>Asking for a demo, callback, or human follow-up</li>
          <li>Any question the knowledge base genuinely can&rsquo;t answer</li>
        </ul>
        <p>
          A well-configured chatbot recognizes these moments and naturally shifts into &ldquo;let me get your email so
          our team can follow up&rdquo; rather than interrupting an unrelated question with a form.
        </p>

        <h2>Routing leads to where your team works</h2>
        <p>
          A lead sitting in a dashboard nobody checks is worse than no lead capture at all. Route new leads straight
          into your CRM or a Slack channel with a webhook — see{" "}
          <Link href="/blog/webhooks-vs-polling-for-chatbot-integrations">
            webhooks vs. polling for chatbot integrations
          </Link>{" "}
          for how that&rsquo;s wired up, or browse ready-made <Link href="/integrations">integrations</Link>.
        </p>

        <h2>Is your business ready for this?</h2>
        <p>
          Lead-gen chatbots pay off fastest for businesses with meaningful website traffic and a sales process that
          benefits from faster qualification. If you&rsquo;re earlier stage, start with support deflection instead —
          see{" "}
          <Link href="/blog/signs-your-business-needs-an-ai-chatbot">
            5 signs your business needs an AI chatbot
          </Link>
          .
        </p>

        <h2>Industry-specific playbooks</h2>
        <p>
          Lead qualification looks different for an ecommerce store than a SaaS product. See{" "}
          <Link href="/blog/ai-chatbot-for-shopify-stores">AI chatbots for Shopify stores</Link> and{" "}
          <Link href="/blog/ai-chatbot-for-saas-onboarding">chatbots for SaaS onboarding</Link>{" "}
          for concrete examples, or browse pre-built starting points in <Link href="/templates">templates</Link>.
        </p>
      </LegalContent>
      <CtaSection />
    </>
  );
}
