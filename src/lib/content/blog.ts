export interface BlogPost {
  slug: string;
  title: string;
  excerpt: string;
  date: string;
  readingTime: string;
  body: string[];
}

export const blogPosts: BlogPost[] = [
  {
    slug: "reduce-support-tickets-with-rag-chatbots",
    title: "Why retrieval-augmented chatbots reduce support tickets more than scripted ones",
    excerpt:
      "Scripted decision-tree bots break the moment a customer phrases a question differently. Here's why grounding answers in your actual content changes that.",
    date: "2026-06-02",
    readingTime: "5 min read",
    body: [
      "Most early chatbot deployments failed for the same reason: they matched exact phrases against a decision tree, and customers don't phrase questions the way product teams expect. Ask the same question five different ways and a scripted bot gives you five different failures.",
      "Retrieval-augmented generation (RAG) changes the shape of the problem. Instead of matching phrasing, the system retrieves the most relevant chunks of your actual content — help docs, product pages, FAQs — and passes them to a language model as context for generating an answer. The model handles the phrasing variation; your content stays the source of truth.",
      "The practical effect for support teams is fewer 'I don't understand' dead ends and fewer tickets for questions that are already answered somewhere on the site — the bot just has to find them, not have been explicitly trained on that exact wording.",
      "The tradeoff is that answer quality is bounded by content quality. A RAG chatbot trained on thin or outdated docs will confidently retrieve thin or outdated context. Treat the knowledge base as a living asset, not a one-time upload.",
    ],
  },
  {
    slug: "chatbot-lead-capture-without-annoying-visitors",
    title: "Capturing leads from chat without turning the conversation into a form",
    excerpt: "The best-converting chatbots ask for contact details after they've already been useful, not before.",
    date: "2026-05-14",
    readingTime: "4 min read",
    body: [
      "Gating a chatbot behind a lead form before the first message defeats the point of a chatbot — visitors bounce before they even see what it can do.",
      "A better pattern: let the bot answer the visitor's actual question first, and only ask for a name and email once it's clear the conversation warrants a follow-up — a pricing question, a request for a callback, or a question the bot genuinely can't answer.",
      "This ordering matters more than the form fields themselves. Visitors who've already gotten value from the conversation are far more willing to leave contact details than ones facing a wall before they've typed a single word.",
      "Once captured, route the lead somewhere your team actually looks — a webhook into your CRM or Slack beats a spreadsheet nobody checks.",
    ],
  },
  {
    slug: "webhooks-vs-polling-for-chatbot-integrations",
    title: "Webhooks vs. polling: how to actually wire up chatbot events",
    excerpt: "If your integration checks the API every few minutes for new leads, you're doing more work for a worse result. Here's the alternative.",
    date: "2026-04-22",
    readingTime: "4 min read",
    body: [
      "Polling an API on a timer for new conversations or leads means you're always trading latency against request volume — poll less often and you're slow to react, poll more often and you're burning rate limit budget on mostly-empty responses.",
      "Webhooks flip the model: your endpoint sits idle until an event actually happens, then receives a payload the moment it does. For a lead-capture flow, that's the difference between a Slack notification seconds after a visitor submits their email and one that shows up on the next scheduled sync.",
      "The one thing polling still does better is resilience against a flaky receiving endpoint — if your webhook URL is down when an event fires, you need retry logic on the sending side (which PrimeWebKit handles) and a way to reconcile via the delivery log after the fact.",
      "In practice, most integrations are best served by webhooks for real-time action and an occasional API read for reconciliation — not one or the other exclusively.",
    ],
  },
];
