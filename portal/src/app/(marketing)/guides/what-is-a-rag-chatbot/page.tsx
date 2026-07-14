import type { Metadata } from "next";
import Link from "next/link";
import { PageHeader } from "@/components/marketing/page-header";
import { LegalContent } from "@/components/marketing/legal-content";
import { CtaSection } from "@/components/marketing/cta-section";
import { JsonLd } from "@/components/seo/json-ld";
import { breadcrumbSchema } from "@/lib/seo/schema";

export const metadata: Metadata = {
  title: "What Is a RAG Chatbot? Retrieval-Augmented Generation Explained",
  description:
    "A plain-language explanation of retrieval-augmented generation (RAG) — why it grounds chatbot answers in your real content instead of guessing, and how it's different from a generic AI chatbot.",
  alternates: { canonical: "/guides/what-is-a-rag-chatbot" },
  openGraph: {
    title: "What Is a RAG Chatbot? Retrieval-Augmented Generation Explained",
    description: "Why retrieval-augmented generation is what makes a chatbot's answers trustworthy.",
    url: "/guides/what-is-a-rag-chatbot",
    type: "article",
  },
};

export default function RagGuidePage() {
  return (
    <>
      <JsonLd
        data={breadcrumbSchema([
          { name: "Guides", url: "/guides" },
          { name: "What Is a RAG Chatbot?", url: "/guides/what-is-a-rag-chatbot" },
        ])}
      />
      <PageHeader
        eyebrow="Guide"
        title="What Is a RAG Chatbot?"
        description="Retrieval-augmented generation, in plain language — and why it's the reason PrimeWebKit's answers stay grounded in your actual content instead of guessing."
      />
      <LegalContent>
        <h2>The short version</h2>
        <p>
          RAG stands for <strong>retrieval-augmented generation</strong>. Instead of a language model answering purely
          from what it memorized during training, a RAG system first <em>retrieves</em>{" "}
          the most relevant chunks of your own content — help docs, product pages, PDFs, FAQs — and hands them to the
          model as context before it <em>generates</em>{" "}
          a reply. The answer is written in natural language, but it&rsquo;s grounded in text you actually control.
        </p>

        <h2>Why this matters more than model choice</h2>
        <p>
          A generic AI chatbot without retrieval will confidently answer questions about your refund policy or
          pricing tiers — using whatever it learned from the public internet, which has nothing to do with your
          actual business. That&rsquo;s the core cause of AI &ldquo;hallucination&rdquo; in a business context: not
          that the model is unreliable in general, but that it has no source of truth to check itself against.
        </p>
        <p>
          RAG fixes this at the architecture level. Every PrimeWebKit chatbot only answers from the knowledge sources
          you&rsquo;ve given it — a crawled site, uploaded documents, or Q&amp;A pairs you&rsquo;ve written — so
          answers stay traceable back to real content instead of the model&rsquo;s general training data. See{" "}
          <Link href="/guides/ai-chatbot-for-customer-support">how this plays out for support teams</Link>{" "}
          specifically.
        </p>

        <h2>How the pipeline actually works</h2>
        <ol>
          <li>Your content (website pages, PDFs, Q&amp;A pairs) is split into small chunks and indexed.</li>
          <li>
            When a visitor asks a question, the system retrieves the chunks most semantically similar to that
            question — not just keyword matches, but conceptually related passages.
          </li>
          <li>
            Those chunks are passed to the language model (Gemini, in PrimeWebKit&rsquo;s case) along with the
            question, and the model generates a grounded, streaming answer.
          </li>
        </ol>
        <p>
          The practical upshot: ask the same question five different ways, and you still get a consistent, correct
          answer — because retrieval matches on meaning, not exact phrasing. See{" "}
          <Link href="/blog/reduce-support-tickets-with-rag-chatbots">
            why retrieval-augmented chatbots reduce support tickets more than scripted ones
          </Link>{" "}
          for the deeper comparison against older decision-tree bots.
        </p>

        <h2>What you need to give it</h2>
        <p>
          A RAG chatbot is only as good as what it retrieves from. Read{" "}
          <Link href="/blog/how-to-train-a-chatbot-on-your-website-content">
            how to train a chatbot on your website content
          </Link>{" "}
          for the practical setup, and{" "}
          <Link href="/blog/data-privacy-and-ai-chatbots">what to know about data privacy</Link>{" "}
          before uploading anything containing customer information.
        </p>

        <h2>See it, don&rsquo;t just read about it</h2>
        <p>
          The chat widget on this site is a live RAG chatbot, trained on PrimeWebKit&rsquo;s own content — ask it
          something. Then see <Link href="/features">what else it can do</Link> or start building your own on the{" "}
          <Link href="/pricing">free plan</Link>.
        </p>
      </LegalContent>
      <CtaSection />
    </>
  );
}
