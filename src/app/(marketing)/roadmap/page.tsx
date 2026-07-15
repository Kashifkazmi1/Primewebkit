import type { Metadata } from "next";
import { PageHeader } from "@/components/marketing/page-header";
import { Reveal } from "@/components/marketing/reveal";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";

export const metadata: Metadata = {
  title: "Roadmap",
  description: "What we're building next for PrimeWebKit.",
  alternates: { canonical: "/roadmap" },
};

const columns = [
  {
    status: "In progress",
    variant: "warning" as const,
    items: ["Streaming responses in the full-page chat interface", "Additional AI provider options alongside Gemini"],
  },
  {
    status: "Planned",
    variant: "primary" as const,
    items: ["Real Stripe billing integration", "Slack and Zapier native integrations", "Conversation search across bots"],
  },
  {
    status: "Exploring",
    variant: "neutral" as const,
    items: ["Voice input for the chat widget", "Multi-language auto-detection", "Custom vector database support"],
  },
];

export default function RoadmapPage() {
  return (
    <>
      <PageHeader
        eyebrow="Roadmap"
        title="What we're building next"
        description="Priorities can shift based on customer feedback — nothing here is a guaranteed ship date."
      />
      <section className="container-page py-20">
        <div className="grid gap-6 sm:grid-cols-3">
          {columns.map((column, index) => (
            <Reveal key={column.status} delay={index * 0.08}>
              <Card>
                <CardContent className="p-6">
                  <Badge variant={column.variant}>{column.status}</Badge>
                  <ul className="mt-4 space-y-3 text-sm text-muted-foreground">
                    {column.items.map((item) => (
                      <li key={item} className="rounded-xl border border-border bg-surface-2 p-3">
                        {item}
                      </li>
                    ))}
                  </ul>
                </CardContent>
              </Card>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
