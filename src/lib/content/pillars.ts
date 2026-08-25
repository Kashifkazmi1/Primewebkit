export interface PillarSubsection {
  heading: string;
  paragraphs: string[];
}

export interface PillarSection {
  id: string;
  heading: string;
  paragraphs: string[];
  subsections?: PillarSubsection[];
}

export interface PillarFaq {
  question: string;
  answer: string;
}

export interface Pillar {
  slug: string;
  eyebrow: string;
  title: string;
  metaDescription: string;
  intro: string[];
  sections: PillarSection[];
  faqs: PillarFaq[];
  relatedPillars: string[];
  relatedPosts: string[];
}

export const pillars: Pillar[] = [
  {
    slug: "ai-chatbot-for-lead-generation",
    eyebrow: "Lead generation",
    title: "AI chatbot for lead generation: capture leads without a form",
    metaDescription:
      "How to turn your website chatbot into a lead-generation channel — capturing name, email, and phone naturally inside the conversation, with no gate before the first message.",
    intro: [
      "Most websites lose visitors to a choice between two bad options: a contact form nobody fills out, or a chatbot that answers questions and lets every one of those visitors leave anonymous. An AI chatbot built for lead generation closes that gap — it answers the visitor's question first, then asks for a name and email only once the conversation has earned the right to ask.",
      "This page covers how that actually works in PrimeWebKit: how the bot decides when to ask, what happens to the details once they're given, and how to get those leads into the systems your sales or support team already uses.",
    ],
    sections: [
      {
        id: "why-forms-lose-leads",
        heading: "Why lead forms convert worse than a conversation",
        paragraphs: [
          "A static lead form asks for information before it has given anything back. A visitor lands on a pricing page, sees a form asking for their email before they can even see plan details, and closes the tab. The form isn't wrong to want the email — it's just asking at the worst possible moment, before there's any relationship to justify the exchange.",
          "A chatbot flips that order. It can answer the visitor's actual question — pricing, features, whether it integrates with their stack — using content trained from your own site and docs, and only bring up contact details once it's clear the conversation is worth continuing. That reordering, not any particular UI trick, is most of why chat-based capture outperforms a gated form.",
        ],
      },
      {
        id: "how-capture-works",
        heading: "How lead capture actually works in a PrimeWebKit chatbot",
        paragraphs: [
          "Every bot has a Lead Capture section in its settings with three controls: an on/off toggle, a choice of which fields to collect (name, email, phone — any subset), and a short instruction telling the AI when and how to ask. That instruction might be as simple as \"ask for an email before discussing pricing\" or as specific as \"only ask for a phone number if the visitor mentions wanting a callback.\"",
          "During the conversation, the model follows that instruction as part of its system context — it isn't a separate form injected into the chat. When a visitor volunteers or is asked for their details, the response is saved automatically: a lead record gets created with whatever fields were captured, tagged with the conversation it came from, and immediately visible on the bot's Leads page. Nothing needs to be built or wired up for this to happen — enabling the toggle is the whole setup.",
        ],
        subsections: [
          {
            heading: "Manual capture, for widgets that need it",
            paragraphs: [
              "Not every capture has to happen mid-conversation. The same lead record can also be created directly — useful for a \"leave your email for a summary of this chat\" button inside a custom widget implementation. Either path lands in the same Leads list, distinguished by a source field so you can tell which came from the conversation itself and which were submitted directly.",
            ],
          },
        ],
      },
      {
        id: "writing-a-good-prompt",
        heading: "Writing a capture instruction that doesn't feel like a form",
        paragraphs: [
          "The capture prompt is the one piece of this you actually write, and it's worth getting right. The failure mode to avoid is asking too early — a bot that opens with \"Hi! What's your email?\" gets treated exactly like the form it was supposed to replace. Instead, anchor the ask to a moment of genuine intent: after the bot has answered two or three questions, when the visitor asks about pricing or availability, or when a question comes up the bot can't fully answer and a follow-up would help.",
          "Keep the instruction short and behavioral rather than scripted — describe the trigger and the tone, not exact wording. \"Once you've helped with their question, ask for their email so we can follow up if they need more detail — don't ask before that\" gives the model enough to work with while still sounding like the rest of the conversation, not a hand-off to a different script.",
        ],
      },
      {
        id: "routing-leads",
        heading: "Where captured leads go next",
        paragraphs: [
          "Every captured lead shows up on that bot's Leads page with the fields collected, the source, and a timestamp, and the list can be exported to CSV at any time for a quick import into a spreadsheet or CRM. For a live pipeline instead of a manual export, a webhook on the lead.created event pushes each new lead to an endpoint the moment it's captured — a Slack channel, a CRM's inbound-lead API, or an internal queue.",
          "That combination — a dashboard for browsing and exporting, a webhook for automation — covers both ends of how teams actually work: checking in on volume periodically, and reacting to a hot lead in real time without anyone needing to remember to look.",
        ],
      },
      {
        id: "getting-started",
        heading: "Setting it up",
        paragraphs: [
          "Create a chatbot, give it a system prompt describing what it should help with, and add the content it needs to answer questions — a crawled URL, uploaded docs, or a handful of Q&A pairs. Turn on lead capture, pick the fields you want, and write a one- or two-sentence capture instruction. Then paste the embed snippet from the Widget tab before the closing </body> tag on your site — see the full install guide for platform-specific steps on WordPress, Shopify, and plain HTML.",
        ],
      },
    ],
    faqs: [
      {
        question: "Does lead capture slow down or interrupt the conversation?",
        answer:
          "No — the request for contact details is woven into the model's normal response, following your capture instruction. There's no separate form or popup that halts the chat.",
      },
      {
        question: "Can I require a phone number but not a name?",
        answer: "Yes. The fields to collect are a checklist of name, email, and phone — pick any combination.",
      },
      {
        question: "Where do I see leads that came in overnight?",
        answer:
          "The Leads page on each bot lists every captured lead as it comes in, newest first, with pagination for higher volume. A webhook on lead.created also lets you push new leads to Slack or a CRM in real time instead of checking the dashboard.",
      },
      {
        question: "Will the AI ask for an email on every single visit?",
        answer:
          "Only if your capture instruction tells it to. Most teams get better results anchoring the ask to a specific moment — after a pricing question, for instance — rather than every conversation.",
      },
    ],
    relatedPillars: ["ai-customer-support-agent", "add-ai-chatbot-to-website"],
    relatedPosts: ["chatbot-lead-capture-without-annoying-visitors", "webhooks-vs-polling-for-chatbot-integrations"],
  },
  {
    slug: "ai-customer-support-agent",
    eyebrow: "Customer support",
    title: "AI customer support agent that answers from your own content",
    metaDescription:
      "How an AI support agent trained on your website and docs answers customer questions around the clock — and what to do about the questions it can't answer.",
    intro: [
      "\"24/7 support\" usually means a chatbot that can hold a conversation but doesn't actually know your product — it answers in generalities, or worse, makes something up. An AI support agent built the right way is grounded in your own content: your help docs, your FAQ, your product pages, whatever you point it at. It answers from that, not from a guess.",
      "This page walks through how that grounding works technically (retrieval-augmented generation, in the terms you'll see referenced elsewhere), what happens when the bot genuinely doesn't know something, and how to keep answer quality high as your product changes.",
    ],
    sections: [
      {
        id: "scripted-vs-grounded",
        heading: "Scripted bots vs. an agent grounded in your content",
        paragraphs: [
          "An older generation of chatbots worked off decision trees: match the visitor's exact phrasing to a pre-written branch, or fail. Ask the same question five different ways and you'd get five different outcomes, because the bot wasn't understanding the question — it was pattern-matching against a script.",
          "A retrieval-augmented agent works differently. When a visitor asks something, the system searches your knowledge base for the most relevant passages — from crawled pages, uploaded documents, or Q&A pairs you've written — and hands those to the language model as context for its answer. The model handles the variation in how people phrase things; your content stays the source of truth for what the answer actually says.",
        ],
      },
      {
        id: "training-the-agent",
        heading: "Training it on what you already have",
        paragraphs: [
          "There's no separate authoring step. Point a bot at a URL and it crawls the page; upload a PDF, DOCX, TXT, MD, or CSV and it's chunked and indexed; or write short Q&A pairs directly for the questions you already know come up. All three source types can be mixed on a single bot, and any of them can be added or removed at any time from the Knowledge tab.",
          "When you update the underlying content — a docs page changes, a new FAQ gets added — re-embedding refreshes what the bot can retrieve, so answers stay current without rebuilding the bot from scratch.",
        ],
        subsections: [
          {
            heading: "Setting the tone",
            paragraphs: [
              "Beyond the knowledge base, the system prompt and personality/tone settings control how the agent talks — formal or casual, terse or thorough, and what it should never do (quote a price it isn't sure of, for instance). These settings apply on top of retrieval; they shape delivery, not the facts being retrieved.",
            ],
          },
        ],
      },
      {
        id: "when-it-doesnt-know",
        heading: "What happens when it doesn't know the answer",
        paragraphs: [
          "Grounded answers also mean grounded limits — if the knowledge base doesn't cover something, a well-configured agent should say so rather than guess. That's a system-prompt instruction worth being explicit about: tell the bot to say it isn't sure and offer to connect the visitor with your team, rather than filling the gap with something plausible-sounding but wrong.",
          "This is also where lead capture and support meet: an instruction like \"if you can't answer, ask for their email so the team can follow up\" turns a dead end into a handoff instead of a dropped conversation. The visitor gets acknowledged, and you get a record of exactly what the agent couldn't cover — useful signal for what to add to the knowledge base next.",
        ],
      },
      {
        id: "quality-over-time",
        heading: "Keeping quality up as you scale",
        paragraphs: [
          "Every conversation can be closed and reviewed from the Conversations tab, and visitors can rate a conversation, giving you a running signal for where answers are landing and where they aren't. The bot's usage and analytics views show message volume and trends over time, so a spike in unanswered or poorly rated conversations about a particular topic is visible before it becomes a pattern of support tickets.",
          "Treat the knowledge base as something that keeps growing, not a one-time upload — the most common reason a support agent underperforms months in is that the product moved on and the training content didn't.",
        ],
      },
      {
        id: "getting-started",
        heading: "Setting it up",
        paragraphs: [
          "Create a bot, write a system prompt describing its role and what it should do when it's unsure, then add your help docs, key product pages, and any Q&A pairs for questions your team answers often. Set the welcome message and tone, then embed the widget — as a floating bubble via script tag, or as a standalone page for a dedicated support link. See the embedding guide for the exact steps on your platform.",
        ],
      },
    ],
    faqs: [
      {
        question: "How is this different from a keyword-matching FAQ bot?",
        answer:
          "A keyword bot fails the moment a question is phrased differently than expected. A RAG-based agent retrieves relevant content regardless of exact phrasing and generates an answer from it, so it handles the natural variation in how people actually ask things.",
      },
      {
        question: "Can it hand off to a human?",
        answer:
          "It doesn't place a live call to a human mid-chat, but you can instruct it to capture contact details and flag that a follow-up is needed whenever it can't answer — turning a dead end into a lead for your team to pick up.",
      },
      {
        question: "What happens if my product changes and the docs are outdated?",
        answer:
          "Re-crawl or re-upload the changed content and re-embed the bot; retrieval will pull from the updated material on the next question. Answer quality is only as good as the underlying content, so treat updates as routine maintenance.",
      },
      {
        question: "Can it support more than one language?",
        answer: "Yes — each bot has a language setting, and multiple bots can be run side by side for different languages or regions.",
      },
    ],
    relatedPillars: ["ai-chatbot-for-lead-generation", "add-ai-chatbot-to-website"],
    relatedPosts: ["reduce-support-tickets-with-rag-chatbots", "what-to-put-in-your-chatbots-knowledge-base"],
  },
  {
    slug: "add-ai-chatbot-to-website",
    eyebrow: "Installation",
    title: "How to add an AI chatbot to your website in minutes",
    metaDescription:
      "Step-by-step guide to embedding a PrimeWebKit AI chatbot on any website — plain HTML, WordPress, Shopify, and Google Tag Manager, plus how to restrict and customize the widget.",
    intro: [
      "Getting a chatbot live on a website is a one-line install: a single script tag, pasted once. This page covers the exact steps for the platforms most sites run on, plus how to lock the widget to your own domains and match it to your brand once it's live.",
    ],
    sections: [
      {
        id: "the-snippet",
        heading: "The embed snippet",
        paragraphs: [
          "Every bot has its own embed script on the Widget tab, already scoped to that bot's ID. It loads asynchronously, so it never blocks the rest of the page from rendering, and it renders a floating chat bubble in the corner of the screen once loaded.",
        ],
        subsections: [
          {
            heading: "Plain HTML",
            paragraphs: [
              "Paste the snippet immediately before the closing </body> tag of your site's template. If your site is a single static template shared across pages, one paste covers the whole site.",
            ],
          },
          {
            heading: "WordPress",
            paragraphs: [
              "Paste the snippet into your theme's footer — Appearance → Theme File Editor → footer.php, just above the closing </body> tag — or use a header/footer scripts plugin if you'd rather avoid editing theme files directly. Either way, it only needs to be added once and it appears on every page.",
            ],
          },
          {
            heading: "Shopify",
            paragraphs: [
              "Open Online Store → Themes → Edit code, find theme.liquid, and paste the snippet immediately before the closing </body> tag near the bottom of the file. This makes the widget appear across the whole storefront rather than a single page template.",
            ],
          },
          {
            heading: "Google Tag Manager",
            paragraphs: [
              "Create a new Custom HTML tag, paste the snippet as its content, set the trigger to \"All Pages,\" and publish the container. This is the best option if a content or marketing team manages the site's tags and you'd rather not touch the codebase directly.",
            ],
          },
        ],
      },
      {
        id: "restricting-domains",
        heading: "Restricting the widget to your own domains",
        paragraphs: [
          "The widget's config settings include an allowed-domains list — once set, the bot will only render on those origins, even if someone copies the script tag elsewhere. This matters more than it might seem: without it, anyone who views your page source could paste the same snippet onto an unrelated site and have your bot answer questions there too. Setting the allow-list once closes that off.",
        ],
      },
      {
        id: "customizing-appearance",
        heading: "Matching it to your brand",
        paragraphs: [
          "Widget settings cover the visual details that make it feel native to your site: theme (light or dark), position (which corner it docks to), a primary color that carries through the bubble and message bubbles, and the greeting and placeholder text shown before a visitor types anything. Custom CSS is available for anything the settings don't cover directly.",
          "None of this requires a rebuild or redeploy on your end — widget settings are read live each time the script loads, so a color or greeting change takes effect the next time a visitor loads the page.",
        ],
      },
      {
        id: "testing-checklist",
        heading: "Before you call it done",
        paragraphs: [
          "Load the page in an incognito window to confirm the bubble appears without any cached state, send a real question that should hit your knowledge base and confirm the answer is accurate, and check that the allowed-domains list includes every domain and subdomain the site is actually served from (including a staging domain, if you test there too). If lead capture is on, send a message that should trigger it and confirm the lead shows up on the Leads page a few seconds later.",
        ],
      },
    ],
    faqs: [
      {
        question: "Will the script slow down my site?",
        answer:
          "It loads asynchronously and doesn't block page rendering, so it shouldn't affect load performance in any way a visitor would notice.",
      },
      {
        question: "Can I use it on more than one domain?",
        answer: "Yes — add every domain the bot should run on to its allowed-domains list; there's no limit on how many you can add.",
      },
      {
        question: "Can I put the bot on its own dedicated page instead of a floating bubble?",
        answer:
          "Yes — every bot also has a full-page chat URL, useful for a dedicated \"Chat with us\" link in navigation or email instead of (or alongside) the floating widget.",
      },
      {
        question: "Do I need to redeploy my site to change how the widget looks?",
        answer: "No — appearance and behavior settings are read live from the widget config each time it loads, so changes take effect immediately.",
      },
    ],
    relatedPillars: ["ai-customer-support-agent", "ai-chatbot-for-lead-generation"],
    relatedPosts: ["webhooks-vs-polling-for-chatbot-integrations", "embed-checklist-before-going-live"],
  },
  {
    slug: "ai-chatbot-pricing",
    eyebrow: "Pricing",
    title: "AI chatbot pricing: what it actually costs to add one to your website",
    metaDescription:
      "What drives AI chatbot pricing, typical plan structures, the costs beyond the subscription, and how to judge whether it's worth it for your business.",
    intro: [
      "\"How much does a chatbot cost\" doesn't have a single answer, because the honest answer depends on how much of it you're actually using — message volume, number of bots, and which features matter to your business all move the number. This page breaks down what actually drives the price, what a typical structure looks like, and the costs that show up outside the subscription line.",
    ],
    sections: [
      {
        id: "what-drives-cost",
        heading: "What actually drives chatbot pricing",
        paragraphs: [
          "Three variables do most of the work in any chatbot pricing structure. Message volume is the big one — a bot answering a few hundred conversations a month costs a provider (and therefore you) far less to run than one handling tens of thousands, since every AI-generated reply has a real compute cost behind it. Number of chatbots is the second — a single-location business needs one bot, while a multi-location chain or an agency managing bots for several clients needs several, each with its own knowledge base and settings.",
          "The third is feature access rather than usage: lead capture, API access, white-label branding, and priority support are typically reserved for paid tiers rather than a free plan, because they represent real ongoing engineering and support cost, not just compute. A pricing page that lists these as tier differentiators — rather than usage caps — is telling you which features the provider considers premium, not just which are technically harder to build.",
        ],
      },
      {
        id: "typical-price-ranges",
        heading: "What a typical plan structure looks like",
        paragraphs: [
          "Most credible chatbot platforms — PrimeWebKit included — structure pricing as a free tier for trying the product with real limits (one bot, a capped number of monthly messages), then a small handful of paid tiers that scale up chatbot count, message volume, and feature access together. A free plan good enough to actually evaluate whether the product fits your content and use case, without needing a credit card, is the right bar to look for before ever paying anything — see our current plans on the pricing page for exact numbers, since those are the figures that stay accurate as plans evolve.",
          "Be skeptical of pricing that scales purely on \"number of bots\" with no message cap, since that structure makes it hard to predict your actual monthly cost. Message-based or hybrid caps are more transparent — you know upfront what a busy month costs versus a quiet one, rather than discovering it at renewal.",
        ],
      },
      {
        id: "hidden-costs",
        heading: "Costs that show up outside the subscription price",
        paragraphs: [
          "The subscription price is rarely the whole cost. Preparing content for the knowledge base — writing Q&A pairs for your most common questions, cleaning up outdated help docs before crawling them — takes real time, usually a few hours for a first pass rather than days, but it's not zero. If your plan has a monthly message cap, exceeding it either throttles the bot or requires an upgrade mid-cycle, so it's worth estimating your realistic monthly conversation volume before choosing a tier rather than after hitting a wall.",
          "The costs that don't show up: install time (a single script tag, so effectively free), and ongoing maintenance once content is current (checking in periodically, not a recurring engineering task). Compared to hiring even part-time support staff, the total cost of a chatbot — subscription plus setup time — is usually a fraction, which is the actual comparison worth making rather than treating the subscription price as the full cost of the alternative.",
        ],
      },
      {
        id: "is-it-worth-it",
        heading: "Whether it's worth it for your business",
        paragraphs: [
          "The return on a chatbot shows up in two places: deflected support volume (repetitive questions answered without a human touching them) and captured leads that would otherwise have left anonymously. Both are measurable — most platforms show conversation counts and lead counts directly in the dashboard — so the honest way to judge \"worth it\" after a month isn't a gut feeling, it's comparing captured leads and deflected conversations against the plan's monthly cost.",
          "For a business getting even a handful of extra leads a month that wouldn't have converted through a static contact form, or saving a few hours of repetitive support replies, a paid plan pays for itself well before the higher tiers' more advanced features come into play. Start on the free plan, measure for a few weeks, and let that data — not a sales page — decide whether upgrading makes sense.",
        ],
      },
    ],
    faqs: [
      {
        question: "Is there a free AI chatbot plan with no credit card required?",
        answer: "Yes — PrimeWebKit's free plan requires no credit card and includes enough to fully evaluate the product: one chatbot, a real (if capped) monthly message allowance, and one knowledge source.",
      },
      {
        question: "What happens if I go over my plan's monthly message limit?",
        answer: "You can upgrade at any time to raise your limit immediately — existing bots, settings, and training data carry over unchanged, nothing needs to be rebuilt.",
      },
      {
        question: "Do higher-priced plans just mean more messages, or more features too?",
        answer: "Both, typically. Higher tiers usually raise usage limits (bots, messages) and unlock features gated behind plan tier, like white-label branding, API access, and priority support.",
      },
      {
        question: "Can I cancel or downgrade at any time?",
        answer: "Yes — plans can be changed or cancelled from the billing page at any time; you keep access through the end of the current billing period.",
      },
      {
        question: "Is setup or onboarding an extra cost on top of the subscription?",
        answer: "No — installing the widget is a single script tag with no setup fee. The only real time cost is preparing your knowledge base content, which most businesses can do in a few hours using content they already have.",
      },
    ],
    relatedPillars: ["ai-chatbot-for-lead-generation", "ai-customer-support-agent"],
    relatedPosts: ["how-much-does-a-chatbot-cost", "free-vs-paid-chatbot-differences", "chatbot-for-small-business-website"],
  },
  {
    slug: "ai-chatbot-for-local-businesses",
    eyebrow: "Local business",
    title: "AI chatbots for local businesses: turning website visitors into booked customers",
    metaDescription:
      "How local, location-based businesses — clinics, contractors, agencies, and independent practices — use an AI chatbot to answer questions and capture leads around the clock.",
    intro: [
      "A local business website usually gets visitors at hours nobody's answering the phone — evenings, weekends, whenever someone finally gets around to searching for a provider near them. A chatbot trained on your services, pricing, and availability answers those visitors immediately, in the moment they're actually deciding whether to reach out, instead of losing them to whichever competitor answers first.",
    ],
    sections: [
      {
        id: "why-local-businesses",
        heading: "Why local businesses benefit disproportionately from a chatbot",
        paragraphs: [
          "A national e-commerce brand's chatbot competes with dozens of other tabs a visitor has open. A local business's chatbot often competes with nothing — the visitor found one or two providers in their area and is deciding between them right now, usually outside business hours. Being the one that answers instantly, with accurate details about services and pricing, is a disproportionately large advantage for a local business compared to a larger company where the chatbot is one channel among many.",
          "Local businesses also tend to have a narrower, more repetitive set of questions than a large product catalog would generate — service area, pricing ranges, availability, whether they handle a specific job — which makes them an especially good fit for a knowledge base that's quick to set up and immediately useful.",
        ],
      },
      {
        id: "common-use-cases",
        heading: "The most common local-business use cases",
        paragraphs: [
          "Answering the questions that would otherwise be a phone call: service area, typical pricing ranges, turnaround time, whether a specific job is something the business handles at all. These are the questions that determine whether a visitor calls or moves to the next search result, and they're exactly the kind of narrow, factual questions a chatbot answers well.",
          "Booking and lead capture for businesses that take appointments — a chatbot that answers a question and then asks \"want us to call you back to schedule?\" converts an anonymous visitor into a named lead with contact details, without needing a separate booking widget or a form the visitor has to find and fill out unprompted.",
        ],
      },
      {
        id: "setup-for-local",
        heading: "Setting one up for a local business",
        paragraphs: [
          "The knowledge base for a local business is usually small and quick to build: a handful of Q&A pairs covering service area, pricing, and availability, plus a crawl of the existing website if it already has service pages. Most local businesses have this live within an hour, which is a meaningfully lower setup cost than most other channels claiming to generate leads around the clock.",
        ],
      },
    ],
    faqs: [
      {
        question: "Does a chatbot make sense for a business that only serves one city or region?",
        answer: "Yes — if anything, a narrow service area makes the knowledge base simpler to build, since there are fewer variations in service area and pricing to cover.",
      },
      {
        question: "Can the chatbot tell visitors whether I serve their specific area?",
        answer: "Yes, if that information is in the knowledge base — a Q&A pair listing exact service areas, or a crawled service-area page, lets the bot answer that directly.",
      },
      {
        question: "Does it work for businesses that book appointments rather than sell products?",
        answer: "Yes — combined with lead capture, a chatbot can collect contact details and preferred timing from a visitor who wants to book, creating a lead your team can confirm rather than requiring the bot to manage a live calendar itself.",
      },
    ],
    relatedPillars: ["ai-chatbot-for-lead-generation", "ai-customer-support-agent"],
    relatedPosts: [
      "chatbot-for-business-in-london",
      "chatbot-for-business-in-manchester",
      "chatbot-for-business-in-birmingham",
      "chatbot-for-business-in-glasgow",
      "chatbot-for-business-in-leeds",
      "chatbot-for-business-in-edinburgh",
      "chatbot-for-business-in-new-york",
      "chatbot-for-business-in-los-angeles",
      "chatbot-for-business-in-chicago",
      "chatbot-for-business-in-miami",
      "chatbot-for-business-in-austin",
      "chatbot-for-business-in-seattle",
    ],
  },
];

export function getPillar(slug: string): Pillar | undefined {
  return pillars.find((p) => p.slug === slug);
}
