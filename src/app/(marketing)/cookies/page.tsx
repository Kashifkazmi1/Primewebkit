import type { Metadata } from "next";
import { LegalContent } from "@/components/marketing/legal-content";
import { PageHeader } from "@/components/marketing/page-header";

export const metadata: Metadata = {
  title: "Cookie Policy",
  description: "How PrimeWebKit uses cookies and local storage.",
  alternates: { canonical: "/cookies" },
};

export default function CookiesPage() {
  return (
    <>
      <PageHeader eyebrow="Legal" title="Cookie Policy" description="Last updated July 2026" />
      <LegalContent>
        <h2>What we use</h2>
        <p>
          PrimeWebKit keeps things simple. We use browser local storage — not tracking cookies — to keep you signed
          in and to remember your theme preference (light, dark, or system). This data stays on your device and is
          sent only to our own API, never to third parties.
        </p>

        <h2>Embedded chatbot widgets</h2>
        <p>
          When a chatbot is embedded on a customer&apos;s website, the widget uses local storage on that website to
          maintain a conversation session and a device fingerprint, so a visitor&apos;s chat history persists across
          a page reload.
        </p>

        <h2>Analytics</h2>
        <p>
          If we add privacy-respecting product analytics in the future, this page will be updated before that
          change takes effect.
        </p>

        <h2>Managing local storage</h2>
        <p>
          You can clear local storage at any time through your browser&apos;s settings. Doing so will sign you out
          and reset your theme preference.
        </p>

        <h2>Contact</h2>
        <p>
          Questions about this policy? Email <a href="mailto:privacy@primewebkit.com">privacy@primewebkit.com</a>.
        </p>
      </LegalContent>
    </>
  );
}
