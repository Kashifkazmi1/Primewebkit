import Script from "next/script";
import { SiteFooter } from "@/components/marketing/site-footer";
import { SiteHeader } from "@/components/marketing/site-header";
import { env } from "@/lib/env";

export default function MarketingLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-screen flex-col">
      <SiteHeader />
      <main className="flex-1">{children}</main>
      <SiteFooter />
      {/* The real PrimeWebKit floating widget, loaded live on the marketing site
          itself — visitors see the actual product working, not a mockup. */}
      <Script src={env.widgetUrl} data-bot-id={env.demoBotId} strategy="lazyOnload" />
    </div>
  );
}
