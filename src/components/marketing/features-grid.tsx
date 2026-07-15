import { BarChart3, Globe, MessageSquareText, Shield, Users, Webhook, Zap } from "lucide-react";
import { Reveal } from "@/components/marketing/reveal";

const features = [
  {
    icon: Globe,
    title: "Crawl your website",
    description: "Point PrimeWebKit at your site and it indexes every page as chatbot knowledge — no copy-pasting.",
  },
  {
    icon: MessageSquareText,
    title: "Documents & Q&A",
    description: "Upload PDFs, DOCX, or CSVs, or write Q&A pairs directly for the answers that matter most.",
  },
  {
    icon: Zap,
    title: "Streaming answers",
    description: "Responses stream token-by-token, powered by Google Gemini with retrieval-augmented context.",
  },
  {
    icon: Users,
    title: "Lead capture",
    description: "Collect name, email, and phone mid-conversation and route new leads straight to your CRM.",
  },
  {
    icon: BarChart3,
    title: "Analytics",
    description: "Track top questions, response times, satisfaction ratings, and lead conversion in one view.",
  },
  {
    icon: Webhook,
    title: "Webhooks & API",
    description: "React to events like new leads or completed chats in real time, or drive everything from your API.",
  },
  {
    icon: Shield,
    title: "Team roles & permissions",
    description: "Invite teammates with scoped roles — owner, manager, member — across shared chatbots.",
  },
  {
    icon: Globe,
    title: "White-label",
    description: "Remove PrimeWebKit branding and use your own domain and colors on paid plans.",
  },
];

export function FeaturesGrid() {
  return (
    <section id="features" className="container-page py-24">
      <Reveal className="mx-auto max-w-2xl text-center">
        <p className="text-sm font-semibold uppercase tracking-widest text-primary">Features</p>
        <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">
          Everything you need to launch a support agent
        </h2>
        <p className="mt-4 text-muted-foreground">
          From training to analytics, PrimeWebKit covers the full lifecycle of a production chatbot.
        </p>
      </Reveal>

      <div className="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {features.map((feature, index) => (
          <Reveal key={feature.title} delay={index * 0.05}>
            <div className="h-full rounded-2xl border border-border bg-surface p-6 transition-all hover:-translate-y-1 hover:border-border-strong hover:shadow-floating">
              <span className="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <feature.icon className="size-5" />
              </span>
              <h3 className="mt-4 font-display text-base font-semibold">{feature.title}</h3>
              <p className="mt-2 text-sm text-muted-foreground">{feature.description}</p>
            </div>
          </Reveal>
        ))}
      </div>
    </section>
  );
}
