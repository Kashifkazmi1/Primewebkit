export interface BlogPost {
  slug: string;
  title: string;
  excerpt: string;
  tag: string;
  date: string;
  readingTime: string;
  relatedPillar: string;
  body: string[];
}

export const blogPosts: BlogPost[] = [
  {
    slug: "reduce-support-tickets-with-rag-chatbots",
    title: "Why retrieval-augmented chatbots reduce support tickets more than scripted ones",
    excerpt:
      "Scripted decision-tree bots break the moment a customer phrases a question differently. Here's why grounding answers in your actual content changes that.",
    tag: "Support",
    date: "2026-06-02",
    readingTime: "7 min read",
    relatedPillar: "ai-customer-support-agent",
    body: [
      "Most early chatbot deployments failed for the same reason: they matched exact phrases against a decision tree, and customers don't phrase questions the way product teams expect. Ask the same question five different ways — \"how do I cancel,\" \"can I get a refund,\" \"I want to stop my subscription\" — and a scripted bot gives you three different failures and, if you're lucky, one match. The problem was never that chatbots couldn't hold a conversation. It was that they were built to recognize wording, not meaning.",
      "Retrieval-augmented generation (RAG) changes the shape of the problem entirely. Instead of matching phrasing against a script, the system retrieves the most relevant chunks of your actual content — help docs, product pages, FAQs, internal runbooks — and passes them to a language model as context for generating an answer. The model handles the variation in how people ask; your content stays the source of truth for what gets said back. A visitor asking \"can I get a refund\" and one asking \"I want my money back\" both retrieve the same refund-policy passage and get functionally the same, correct answer, phrased naturally for each question.",
      "The practical effect for support teams is fewer 'I don't understand' dead ends and fewer tickets for questions that are already answered somewhere on the site — the bot just has to find them, not have been explicitly trained on that exact wording. In practice, the tickets that get deflected are rarely the hard, judgment-call ones. They're the repetitive ones: what are your hours, do you ship internationally, how do I reset my password. Those account for a disproportionate share of ticket volume at most support desks, and they're exactly the category a well-grounded chatbot handles without help.",
      "The tradeoff is that answer quality is bounded by content quality. A RAG chatbot trained on thin or outdated docs will confidently retrieve thin or outdated context — it doesn't know your refund policy changed last quarter unless the page it's reading from was updated too. This is the part teams underestimate when they first set one up: the chatbot isn't a separate knowledge source you maintain once. It's a mirror of whatever you've already written, refreshed each time you re-embed it. Treat the knowledge base as a living asset, not a one-time upload, and the bot's answers stay as current as your docs.",
      "There's also a design decision worth making deliberately: what should the bot do when retrieval comes up empty, or when the retrieved content is too tangential to answer confidently? The honest answer — and the one that protects trust — is to say so rather than fill the gap with something plausible-sounding. A system prompt instruction as simple as \"if you're not confident the knowledge base covers this, say so and offer to connect them with the team\" turns an unanswerable question into a clean handoff instead of a wrong answer a customer might act on.",
      "That handoff is also where lead capture and support overlap in a useful way. If a bot is instructed to collect an email whenever it can't fully answer a question, every one of those gaps becomes a record — not just a support ticket, but a signal about exactly what's missing from the knowledge base. Teams that review that list periodically end up with a prioritized to-do for what to write next, driven by real questions instead of guesswork about what customers might want to know.",
      "None of this requires picking between a chatbot and a human support team — the deflection argument isn't about replacing people, it's about triaging volume so the questions that reach a person are the ones that actually need judgment. A retrieval-grounded bot handling the repetitive third of your ticket volume frees up a support team's time for the harder two-thirds, and it does that from day one, using content you've probably already written.",
    ],
  },
  {
    slug: "chatbot-lead-capture-without-annoying-visitors",
    title: "Capturing leads from chat without turning the conversation into a form",
    excerpt: "The best-converting chatbots ask for contact details after they've already been useful, not before.",
    tag: "Lead generation",
    date: "2026-05-14",
    readingTime: "6 min read",
    relatedPillar: "ai-chatbot-for-lead-generation",
    body: [
      "Gating a chatbot behind a lead form before the first message defeats the point of a chatbot — visitors bounce before they even see what it can do. It's a strange thing teams do to their own funnel: build a conversational interface meant to feel more natural than a form, then put a form in front of it. The visitor doesn't get to ask their actual question until they've already paid the cost the chatbot was supposed to remove.",
      "A better pattern: let the bot answer the visitor's actual question first, and only ask for a name and email once it's clear the conversation warrants a follow-up — a pricing question, a request for a callback, or a question the bot genuinely can't answer on its own. This ordering matters more than the form fields themselves. Visitors who've already gotten value from the conversation are far more willing to leave contact details than ones facing a wall before they've typed a single word. The ask stops feeling like a toll and starts feeling like a natural next step in something that's already gone well.",
      "The trigger you choose for the ask matters as much as the ordering. \"Ask for an email immediately\" and \"never ask unless they explicitly request a callback\" are both real configurations teams use, and both are usually wrong for their goals — the first repeats the form problem in a different shape, and the second leaves obviously interested visitors uncaptured because they didn't think to ask. The middle ground that tends to convert best: instruct the bot to ask once it detects buying intent — a pricing question, a comparison to a competitor, a question about implementation timeline — rather than at a fixed point in the conversation or not at all.",
      "It's worth being explicit, too, about what happens if a visitor declines. A bot that asks once, gets a \"no thanks,\" and drops the subject reads as respectful. One that repeats the ask a few messages later reads as exactly the pushy sales pattern that made the visitor hesitant in the first place. The instruction \"ask once per conversation, and don't bring it up again if they decline\" is a small detail that has an outsized effect on how the whole interaction feels.",
      "Once captured, route the lead somewhere your team actually looks — a webhook into your CRM or Slack beats a spreadsheet nobody checks. This is the step that's easy to skip and expensive to skip. A lead sitting in a dashboard nobody opens is functionally the same as a lead that was never captured; the value only exists once someone acts on it. A webhook firing into a Slack channel the moment a lead comes in, or straight into a CRM's inbound queue, turns capture into pipeline instead of a report someone might check at the end of the week.",
      "Timing also interacts with what you're asking for. A first-touch visitor who's asked one question is a reasonable candidate for an email — low commitment, easy to give. A phone number is a bigger ask, and works better reserved for a visitor who's explicitly requested a callback or demo, where the value exchange is obvious rather than assumed. Matching what you ask for to how much trust has actually been built in the conversation so far avoids the mismatch that makes visitors hesitate.",
      "The net result, done well, looks less like lead-gen software and more like a good salesperson: helpful first, curious about whether there's a real need second, and only asking for contact details once both of those are true. That's a higher bar than a static form ever had to meet, and it's exactly why it converts better when it's done right.",
    ],
  },
  {
    slug: "webhooks-vs-polling-for-chatbot-integrations",
    title: "Webhooks vs. polling: how to actually wire up chatbot events",
    excerpt: "If your integration checks the API every few minutes for new leads, you're doing more work for a worse result. Here's the alternative.",
    tag: "Integrations",
    date: "2026-04-22",
    readingTime: "6 min read",
    relatedPillar: "add-ai-chatbot-to-website",
    body: [
      "Polling an API on a timer for new conversations or leads means you're always trading latency against request volume — poll less often and you're slow to react, poll more often and you're burning rate-limit budget on mostly-empty responses. It's a pattern that made sense when webhooks weren't universally available, and it persists mostly out of habit: a cron job that hits an endpoint every five minutes, checks if anything new showed up, and does nothing on the vast majority of runs.",
      "Webhooks flip the model: your endpoint sits idle until an event actually happens, then receives a payload the moment it does. For a lead-capture flow, that's the difference between a Slack notification seconds after a visitor submits their email and one that shows up on the next scheduled sync — which, depending on your polling interval, could be minutes later, well after the visitor has closed the tab and moved on. For time-sensitive events like a new lead or a conversation flagged for follow-up, that delay is the whole difference between a fast response and a missed one.",
      "Setting one up is usually a five-minute task: register an endpoint URL, choose which events you want — lead.created, chat.completed, subscription.updated, and a handful of others — and save the signing secret shown at creation time. That secret is shown exactly once; if you lose it, you'll need to rotate it rather than retrieve it, so store it in whatever secrets manager your team already uses rather than a chat message or a sticky note.",
      "Verification is the part worth not skipping. Every delivery includes a signature header computed from the payload and your signing secret — checking it on your receiving end confirms the request actually came from the platform and wasn't spoofed by something watching your public endpoint URL. Skipping verification because \"it's probably fine\" is the kind of shortcut that's invisible right up until it isn't; it costs a few lines of code to check an HMAC and a real amount of risk to skip it.",
      "The one thing polling still does better is resilience against a flaky receiving endpoint. If your webhook URL is down when an event fires, that delivery needs retry logic on the sending side — which a reasonable webhook system handles for you with backoff — and a way to reconcile via the delivery log after the fact, in case a delivery genuinely never lands. Treat the delivery log as your safety net, not your primary integration path: check it when something looks like it's missing, not as a substitute for acting on webhooks in real time.",
      "In practice, most integrations are best served by webhooks for real-time action and an occasional API read for reconciliation — not one or the other exclusively. A Slack notification on lead.created gets someone's attention immediately; a once-a-day pull of the full leads list catches anything a webhook delivery genuinely missed, whether from an outage on your end or a delivery that failed all its retries. Neither replaces the other; together they cover both the common case and the edge case.",
      "The broader lesson generalizes past chatbots: any system emitting events that matter to you in near-real-time is a candidate for webhooks over polling, and the switch is almost always a net simplification once it's wired up, not an added complexity. You write the receiving handler once, and from then on you're reacting to what actually happened instead of asking on a timer whether anything did.",
    ],
  },
  {
    slug: "what-to-put-in-your-chatbots-knowledge-base",
    title: "What actually belongs in your chatbot's knowledge base (and what doesn't)",
    excerpt: "More content isn't automatically better. A knowledge base full of noise retrieves noise — here's how to decide what to feed it.",
    tag: "Support",
    date: "2026-07-08",
    readingTime: "6 min read",
    relatedPillar: "ai-customer-support-agent",
    body: [
      "The instinct when setting up a new chatbot is to point it at everything — the whole help center, every product page, every internal wiki article that seems remotely related — on the theory that more content means better answers. In practice, this usually backfires. Retrieval finds the most relevant chunks of whatever it's given, and if a third of what it's given is outdated, contradictory, or written for a completely different audience, some fraction of retrieved context on any given question will be exactly that.",
      "Start with what customers actually ask, not what you have written. Your existing support tickets, if you have them, are the single best source for this — they tell you the real distribution of questions, which is usually narrower and more repetitive than a full help center implies. A handful of Q&A pairs written directly for your ten most common questions will outperform an entire lightly-curated wiki, because every one of those ten answers is exact, current, and written for the question it's answering.",
      "Crawled website content earns its place for anything that changes on its own and that you'd otherwise have to remember to re-upload — pricing pages, feature lists, a changelog. The advantage of a crawl over a manual upload is that re-crawling picks up whatever's live on the page right now, so a pricing update or a new feature announcement flows through without a separate step. The tradeoff is that crawled pages often carry marketing language and navigation cruft alongside the actual facts, which can dilute what gets retrieved. Uploaded documents and hand-written Q&A pairs are the tools for content that's precise and doesn't change often — policies, procedures, anything where the exact wording matters.",
      "Internal documentation is worth a harder look before it goes in. A wiki page written for your own support team, full of internal shorthand and assumptions about context a customer doesn't have, can produce answers that are technically drawn from a true source but confusing or wrong-sounding when surfaced directly to a visitor. If internal docs are the best source of truth for something customers ask about, it's usually worth rewriting the relevant parts in customer-facing language rather than uploading the internal version as-is.",
      "Outdated content is the most common quiet failure mode. A knowledge base that hasn't been touched since launch will still retrieve confidently — the model doesn't know the pricing page it's reading from is six months stale, it just answers from what's there. The fix isn't complicated, just easy to neglect: whenever a policy, price, or feature changes, re-crawl or re-upload the relevant source and re-embed. Treat that step as part of shipping the change, not an afterthought.",
      "A useful audit, once a bot has been live for a few weeks: pull the conversations where it clearly struggled or where a visitor asked a follow-up that suggests the first answer missed the mark. That list is a direct map of gaps in the knowledge base — questions being asked that nothing currently answers well. Closing those gaps one at a time, driven by real conversations instead of a guess at what might be useful, is a far better use of time than trying to anticipate every possible question up front.",
      "The overall principle is closer to curating a reference shelf than filling a warehouse: fewer, more precise, more current sources beat a comprehensive but stale and noisy one, every time retrieval has to pick between them.",
    ],
  },
  {
    slug: "embed-checklist-before-going-live",
    title: "A practical checklist for embedding a chatbot without breaking your site",
    excerpt: "The install itself is one script tag. Here's everything worth checking before you call it done.",
    tag: "Installation",
    date: "2026-07-29",
    readingTime: "5 min read",
    relatedPillar: "add-ai-chatbot-to-website",
    body: [
      "Adding a chatbot to a website is, mechanically, one script tag pasted before a closing body tag. That simplicity is real, but it also means the parts most likely to go wrong aren't the install itself — they're the handful of things around it that are easy to skip because the widget appears to be working the moment the tag is in place.",
      "Start with where the tag actually lives. A single paste into a shared footer template covers every page built from that template, but sites with multiple templates — a marketing site built on one system and a docs site or app on another — need the tag in each one separately. It's a common gap: the widget shows up everywhere the marketing team's pages render and is quietly missing from a support or docs subdomain that uses a different template entirely.",
      "Domain restrictions are worth setting before launch, not after. Anyone who views page source can see the embed snippet, and without an allowed-domains restriction, that snippet works exactly the same wherever it's pasted — including somewhere you didn't put it. Setting the allow-list to the exact domains and subdomains the site is actually served from closes that off with no ongoing effort once it's configured.",
      "Test the actual conversation, not just that the bubble renders. A widget that loads and opens is only step one — the real test is asking it a handful of the exact questions customers ask most, and checking that the answers are accurate and current. This is also the moment to test lead capture, if it's enabled: send a message that should trigger the ask, and confirm the resulting lead actually shows up where it's supposed to a few seconds later.",
      "Check appearance in both light and dark mode if your site supports both, and at the mobile breakpoint specifically — a floating bubble that sits comfortably in a corner on desktop can end up overlapping a sticky mobile nav bar or a cookie-consent banner if nobody checked that combination. This is a five-minute check that catches a surprising number of real launch-day issues.",
      "Finally, decide who owns the widget's settings going forward — the greeting message, the color, the knowledge base — before launch, not during an incident. Because none of this requires a redeploy (widget settings and knowledge sources are read live), it's tempting to leave ownership vague. In practice, that just means the first time something needs fixing, several people independently discover they can do it and none of them knew that going in. A five-minute conversation about who's the primary owner avoids that.",
      "None of these steps are hard individually, and skipping all of them still usually results in a widget that technically works. The difference they make is in whether it works well from day one instead of needing a round of fixes after a customer or teammate notices something off.",
    ],
  },
];
