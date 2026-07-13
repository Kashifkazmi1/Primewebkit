import type { Metadata } from "next";
import { DashboardPreviewSection } from "@/components/marketing/dashboard-preview";
import { FeaturesGrid } from "@/components/marketing/features-grid";
import { IntegrationsSection } from "@/components/marketing/integrations-section";
import { PageHeader } from "@/components/marketing/page-header";
import { WorkflowShowcase } from "@/components/marketing/workflow-showcase";

export const metadata: Metadata = {
  title: "Features",
  description: "Website crawling, document training, streaming answers, lead capture, analytics, webhooks, and team roles — everything PrimeWebKit chatbots can do.",
  alternates: { canonical: "/features" },
};

export default function FeaturesPage() {
  return (
    <>
      <PageHeader
        eyebrow="Features"
        title="Everything a production chatbot needs"
        description="Train it on your content, customize how it talks, capture leads, and see what your visitors are actually asking."
      />
      <FeaturesGrid />
      <WorkflowShowcase />
      <DashboardPreviewSection />
      <IntegrationsSection />
    </>
  );
}
