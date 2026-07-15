import type { Metadata } from "next";
import { CtaSection } from "@/components/marketing/cta-section";
import { DashboardPreviewSection } from "@/components/marketing/dashboard-preview";
import { FaqSection } from "@/components/marketing/faq-section";
import { FeaturesGrid } from "@/components/marketing/features-grid";
import { Hero } from "@/components/marketing/hero";
import { IntegrationsSection } from "@/components/marketing/integrations-section";
import { LogosMarquee } from "@/components/marketing/logos-marquee";
import { PricingPreview } from "@/components/marketing/pricing-preview";
import { Testimonials } from "@/components/marketing/testimonials";
import { WorkflowShowcase } from "@/components/marketing/workflow-showcase";
import { JsonLd } from "@/components/seo/json-ld";
import { organizationSchema, softwareApplicationSchema, faqSchema } from "@/lib/seo/schema";

export const metadata: Metadata = {
  title: "AI Chatbots Trained on Your Content",
  description:
    "Build an AI chatbot trained on your website, docs, and FAQs in minutes. Streaming answers, lead capture, analytics, and a single script tag install.",
  alternates: { canonical: "/" },
};

export default function HomePage() {
  return (
    <>
      <JsonLd data={[organizationSchema, softwareApplicationSchema, faqSchema]} />
      <Hero />
      <LogosMarquee />
      <FeaturesGrid />
      <WorkflowShowcase />
      <DashboardPreviewSection />
      <IntegrationsSection />
      <Testimonials />
      <PricingPreview />
      <FaqSection />
      <CtaSection />
    </>
  );
}
