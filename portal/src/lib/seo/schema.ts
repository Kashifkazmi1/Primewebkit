import { env } from "@/lib/env";
import { faqs } from "@/lib/content/faqs";
import { pricingPlans } from "@/lib/content/pricing";

export const organizationSchema = {
  "@context": "https://schema.org",
  "@type": "Organization",
  name: "PrimeWebKit",
  url: env.siteUrl,
  logo: `${env.siteUrl}/logo.png`,
  sameAs: [
    "https://www.facebook.com/people/Prime-Webkit/61578131876843/",
    "https://www.linkedin.com/company/prime-webkit",
  ],
};

export const softwareApplicationSchema = {
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  name: "PrimeWebKit",
  applicationCategory: "BusinessApplication",
  operatingSystem: "Web",
  description:
    "AI chatbot SaaS platform: crawl your website, upload documents, or write Q&A pairs to train a chatbot that answers visitors using retrieval-augmented generation with streaming responses.",
  offers: pricingPlans.map((plan) => ({
    "@type": "Offer",
    name: plan.name,
    price: plan.price,
    priceCurrency: "USD",
  })),
  // Intentionally no aggregateRating here — add it only once real,
  // verifiable review data exists. Fabricated ratings in structured
  // data violate Google's review-snippet policy and can trigger a
  // manual action.
};

export const faqSchema = {
  "@context": "https://schema.org",
  "@type": "FAQPage",
  mainEntity: faqs.map((faq) => ({
    "@type": "Question",
    name: faq.question,
    acceptedAnswer: { "@type": "Answer", text: faq.answer },
  })),
};

export function breadcrumbSchema(items: { name: string; url: string }[]) {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: items.map((item, index) => ({
      "@type": "ListItem",
      position: index + 1,
      name: item.name,
      item: `${env.siteUrl}${item.url}`,
    })),
  };
}
