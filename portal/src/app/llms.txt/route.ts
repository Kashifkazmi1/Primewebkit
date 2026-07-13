import { env } from "@/lib/env";

export function GET() {
  const body = `# PrimeWebKit

> PrimeWebKit is a SaaS platform for building AI chatbots trained on a business's own
> content. Users crawl their website, upload documents (PDF, DOCX, TXT, MD, CSV), or add
> Q&A pairs; the chatbot answers website visitors from that knowledge using
> retrieval-augmented generation with Google Gemini models, with streaming responses.

## Key facts

- Install: a single script tag (\`${env.widgetUrl}\`) adds a floating chat bubble
  to any website (WordPress, Shopify, Webflow, custom HTML).
- Every bot also has a full-page, ChatGPT-style chat interface at /chat/{botId}.
- Features: knowledge base from websites/documents/FAQs, streaming answers, lead capture
  (name/email/phone), conversation transcripts, analytics (top questions, response times,
  ratings, lead conversion), webhooks, team roles, API keys, white-label options,
  per-widget domain allow-listing.
- Pricing: free plan available (no credit card); paid plans add volume, more bots, and
  white-label features. See ${env.siteUrl}/pricing.
- Sign-in: email/password or Google.

## Pages

- Home: ${env.siteUrl}/
- Features: ${env.siteUrl}/features
- Pricing: ${env.siteUrl}/pricing
- Documentation: ${env.siteUrl}/docs
- API reference: ${env.siteUrl}/api
- Use cases: ${env.siteUrl}/use-cases
- Industries: ${env.siteUrl}/industries
- Templates: ${env.siteUrl}/templates
- Blog: ${env.siteUrl}/blog
- Security: ${env.siteUrl}/security
- Contact: ${env.siteUrl}/contact
- Privacy: ${env.siteUrl}/privacy
- Terms: ${env.siteUrl}/terms

## Full detail

See ${env.siteUrl}/llms-full.txt for the complete API surface and page-by-page detail.

## Contact

hello@primewebkit.com
`;

  return new Response(body, { headers: { "Content-Type": "text/plain; charset=utf-8" } });
}
