import type { Metadata } from "next";
import { PillarPage } from "@/components/marketing/pillar-page";
import { getPillar } from "@/lib/content/pillars";

const pillar = getPillar("ai-chatbot-pricing")!;

export const metadata: Metadata = {
  title: pillar.title,
  description: pillar.metaDescription,
  alternates: { canonical: `/${pillar.slug}` },
  openGraph: { title: pillar.title, description: pillar.metaDescription, type: "article" },
};

export default function AiChatbotPricingPage() {
  return <PillarPage pillar={pillar} />;
}
