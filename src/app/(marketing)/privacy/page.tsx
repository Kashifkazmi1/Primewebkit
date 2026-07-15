import type { Metadata } from "next";
import { LegalContent } from "@/components/marketing/legal-content";
import { PageHeader } from "@/components/marketing/page-header";

export const metadata: Metadata = {
  title: "Privacy Policy",
  description: "How PrimeWebKit collects, uses, and protects your data.",
  alternates: { canonical: "/privacy" },
  robots: { index: true, follow: true },
};

export default function PrivacyPage() {
  return (
    <>
      <PageHeader eyebrow="Legal" title="Privacy Policy" description="Last updated July 2026" />
      <LegalContent>
        <h2>1. Information we collect</h2>
        <p>
          When you create a PrimeWebKit account, we collect your name, email address, and password (stored as a
          salted hash, never in plain text). If you sign in with Google, we receive your name, email, and profile
          picture from Google&apos;s identity service.
        </p>
        <p>
          When you configure a chatbot, we store the content you provide for training — crawled website pages,
          uploaded documents, and Q&amp;A pairs — along with the conversations your chatbot has with your website
          visitors and any lead information (name, email, phone) those visitors choose to share.
        </p>

        <h2>2. How we use information</h2>
        <ul>
          <li>To provide and operate the chatbot service you&apos;ve configured.</li>
          <li>To generate chatbot responses using Google Gemini, which processes the retrieved content and your visitor&apos;s message to produce an answer.</li>
          <li>To send transactional email (verification, password reset, billing notices).</li>
          <li>To monitor for abuse, enforce rate limits, and maintain the security of the platform.</li>
        </ul>

        <h2>3. Data sharing</h2>
        <p>
          We do not sell your data. Chatbot messages are sent to our AI provider (Google Gemini) solely to generate
          responses. Webhook endpoints you configure receive the event payloads you subscribe to. We may disclose
          information if required by law.
        </p>

        <h2>4. Data retention</h2>
        <p>
          Account and chatbot data is retained for as long as your account is active. You may delete your account
          at any time from Settings, which removes your profile and revokes access to your chatbots.
        </p>

        <h2>5. Your rights</h2>
        <p>
          Depending on your jurisdiction, you may have the right to access, correct, export, or delete your personal
          data. Contact us at{" "}
          <a href="mailto:privacy@primewebkit.com">privacy@primewebkit.com</a> to exercise these rights.
        </p>

        <h2>6. Security</h2>
        <p>
          See our <a href="/security">Security page</a> for details on how we protect your account and data,
          including authentication, encryption in transit, and SSRF protections on crawling and webhooks.
        </p>

        <h2>7. Changes to this policy</h2>
        <p>We&apos;ll post material changes to this page and update the &ldquo;last updated&rdquo; date above.</p>

        <h2>8. Contact</h2>
        <p>
          Questions about this policy? Email{" "}
          <a href="mailto:privacy@primewebkit.com">privacy@primewebkit.com</a>.
        </p>
      </LegalContent>
    </>
  );
}
