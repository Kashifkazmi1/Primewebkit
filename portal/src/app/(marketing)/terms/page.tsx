import type { Metadata } from "next";
import { LegalContent } from "@/components/marketing/legal-content";
import { PageHeader } from "@/components/marketing/page-header";

export const metadata: Metadata = {
  title: "Terms of Service",
  description: "The terms governing your use of PrimeWebKit.",
  alternates: { canonical: "/terms" },
};

export default function TermsPage() {
  return (
    <>
      <PageHeader eyebrow="Legal" title="Terms of Service" description="Last updated July 2026" />
      <LegalContent>
        <h2>1. Acceptance of terms</h2>
        <p>
          By creating an account or using PrimeWebKit, you agree to these Terms of Service and our{" "}
          <a href="/privacy">Privacy Policy</a>.
        </p>

        <h2>2. Your account</h2>
        <p>
          You&apos;re responsible for maintaining the confidentiality of your password and API keys, and for all
          activity under your account. Notify us immediately if you suspect unauthorized access.
        </p>

        <h2>3. Acceptable use</h2>
        <ul>
          <li>Don&apos;t use PrimeWebKit to train chatbots on content you don&apos;t have the right to use.</li>
          <li>Don&apos;t attempt to circumvent rate limits, usage limits, or security controls.</li>
          <li>Don&apos;t use the platform to generate unlawful, harassing, or deceptive content.</li>
          <li>Don&apos;t register webhooks or crawl targets pointing at infrastructure you don&apos;t own or have permission to access.</li>
        </ul>

        <h2>4. Plans and billing</h2>
        <p>
          Paid plans are billed on a recurring basis as described at checkout. Usage beyond your plan&apos;s limits
          (messages, chatbots, storage) may be restricted until you upgrade. You may cancel at any time; cancellation
          takes effect at the end of the current billing period.
        </p>

        <h2>5. Content and ownership</h2>
        <p>
          You retain ownership of the content you upload and the conversations your chatbots have. You grant us a
          license to process that content solely to provide the service (crawling, embedding, retrieval, and
          generating responses).
        </p>

        <h2>6. Third-party AI provider</h2>
        <p>
          Chatbot responses are generated using Google Gemini. Your use of PrimeWebKit is also subject to reasonable
          use of that underlying provider, and response quality depends on the content you provide for training.
        </p>

        <h2>7. Termination</h2>
        <p>
          We may suspend or terminate accounts that violate these terms, including abusive use of AI generation,
          SSRF attempts via crawling or webhooks, or non-payment on paid plans.
        </p>

        <h2>8. Disclaimer &amp; limitation of liability</h2>
        <p>
          The service is provided &ldquo;as is.&rdquo; PrimeWebKit is not liable for inaccurate chatbot responses,
          service interruptions, or indirect damages arising from your use of the platform, to the fullest extent
          permitted by law.
        </p>

        <h2>9. Changes</h2>
        <p>We may update these terms from time to time. Continued use after changes take effect constitutes acceptance.</p>

        <h2>10. Contact</h2>
        <p>
          Questions about these terms? Email <a href="mailto:legal@primewebkit.com">legal@primewebkit.com</a>.
        </p>
      </LegalContent>
    </>
  );
}
