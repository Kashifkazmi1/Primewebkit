import type { Metadata } from "next";
import { PillarPage } from "@/components/marketing/pillar-page";
import { getPillar } from "@/lib/content/pillars";

const pillar = getPillar("add-ai-chatbot-to-website")!;

export const metadata: Metadata = {
  title: pillar.title,
  description: pillar.metaDescription,
  alternates: { canonical: `/${pillar.slug}` },
  openGraph: { title: pillar.title, description: pillar.metaDescription, type: "article" },
};

export default function AddAiChatbotToWebsitePage() {
  return <PillarPage pillar={pillar} />;
}
