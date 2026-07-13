import { env } from "@/lib/env";
import { blogPosts } from "@/lib/content/blog";
import { faqs } from "@/lib/content/faqs";
import { pricingPlans } from "@/lib/content/pricing";

export function GET() {
  const pricing = pricingPlans
    .map((plan) => `- ${plan.name}: $${plan.price}/mo — ${plan.tagline}. Includes: ${plan.features.join(", ")}.`)
    .join("\n");

  const faqText = faqs.map((faq) => `Q: ${faq.question}\nA: ${faq.answer}`).join("\n\n");

  const posts = blogPosts.map((post) => `- ${post.title} (${post.date}): ${post.excerpt} — ${env.siteUrl}/blog/${post.slug}`).join("\n");

  const body = `# PrimeWebKit — full reference

## What it is

PrimeWebKit is a multi-tenant AI chatbot SaaS. Accounts crawl a website, upload documents,
or write Q&A pairs to build a chatbot's knowledge base. Visitor questions are answered via
retrieval-augmented generation (relevant chunks retrieved, then passed to Google Gemini for
generation), with streaming responses over Server-Sent Events. Chatbots embed via a script
tag (floating widget) or a full-page chat interface at /chat/{botId}.

## Pricing

${pricing}

## Core API (all versioned under /api/v1, JSON envelope: {status, success, message, data, errors, pagination})

Authentication: POST /auth/register, /auth/login, /auth/google (Google Identity Services
credential), /auth/refresh, /auth/logout. Bearer JWT access tokens (short-lived) plus an
opaque, server-revocable refresh token.

Chatbots: GET/POST /bots, GET/PUT/DELETE /bots/{uuid}, knowledge sources (text, Q&A,
website crawl, document upload) under /bots/{uuid}/knowledge-sources, widget config under
/bots/{uuid}/widget, conversations and leads under /bots/{uuid}/conversations and
/bots/{uuid}/leads, analytics under /bots/{uuid}/analytics.

Webhooks: GET /webhooks/events for supported event types (bot.created, bot.deleted,
chat.started, chat.completed, lead.created, subscription.created, subscription.updated,
user.created, knowledge.uploaded). GET/POST /webhooks, PUT /webhooks/{uuid} to toggle,
DELETE to remove, GET /webhooks/{uuid}/logs for delivery history. The signing secret is
returned exactly once, at creation.

Public widget (unauthenticated, used by the embedded script): GET
/widget/{botUuid}/config, POST /widget/{botUuid}/messages (and /messages/stream for SSE),
POST /widget/{botUuid}/leads to capture a lead, POST
/widget/{botUuid}/conversations/{id}/rate for visitor feedback.

Billing & teams: GET /subscriptions/plans, /subscriptions/current, /subscriptions/invoices;
POST /teams/{uuid}/invite; GET/POST /api-keys with rotation via POST /api-keys/{uuid}/rotate.

## Frequently asked questions

${faqText}

## Blog

${posts}

## Security

JWT authentication with account lockout after repeated failed logins, parameterized SQL
throughout, SSRF protection (with redirect-hop re-validation) on website crawling and
outgoing webhooks, rate limiting with a stricter cost-aware limit on AI-generating
endpoints, prompt-injection screening on retrieved knowledge-base content, and CORS with
credentialed responses only for explicit origin matches. Full detail: ${env.siteUrl}/security.

## Contact

General: hello@primewebkit.com
Security disclosures: security@primewebkit.com
`;

  return new Response(body, { headers: { "Content-Type": "text/plain; charset=utf-8" } });
}
