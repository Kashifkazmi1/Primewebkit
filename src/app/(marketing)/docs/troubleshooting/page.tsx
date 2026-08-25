import type { Metadata } from "next";
import { DocsShell } from "@/components/docs/docs-shell";
import { PageHeader } from "@/components/marketing/page-header";

export const metadata: Metadata = {
  title: "Troubleshooting",
  description: "Fixes for the most common issues installing and running a PrimeWebKit chatbot.",
  alternates: { canonical: "/docs/troubleshooting" },
};

const issues = [
  {
    problem: "The widget doesn't appear on my site",
    fix: "Confirm the snippet is placed before the closing </body> tag and that the page you're testing was actually rebuilt or cached content cleared. If the bot's allowed-domains list is set, make sure the exact domain you're testing on (including any subdomain like www or a staging environment) is included — the widget silently won't render on a domain that isn't on the list.",
  },
  {
    problem: "The bot answers questions incorrectly or too generically",
    fix: "This almost always traces back to the knowledge base. Check that a knowledge source has actually finished processing (status should show as ready, not pending) and that it covers the topic being asked about. If content changed recently, re-embed the bot from the bot's settings so retrieval picks up the update.",
  },
  {
    problem: "Lead capture isn't saving any leads",
    fix: "Confirm lead capture is toggled on for that specific bot — it's a per-bot setting, not a global one. Then check the capture instruction: an instruction that's too narrow (\"only if they explicitly ask for a callback\") will correctly result in very few captures. Test with a message designed to trigger the instruction directly.",
  },
  {
    problem: "Google sign-in shows an error or does nothing",
    fix: "This typically means the Google OAuth client ID isn't configured for the domain you're testing on, or the backend's Google sign-in endpoint isn't yet enabled in this environment. The sign-in form falls back gracefully — email and password sign-in continues to work regardless.",
  },
  {
    problem: "I'm seeing CORS errors in the browser console",
    fix: "The API and the frontend run on separate origins by design. If requests are being blocked, the API's allowed-origins configuration needs to include the exact origin your frontend is served from (protocol, domain, and port all have to match).",
  },
  {
    problem: "API requests intermittently fail with 401 after working fine",
    fix: "Access tokens are short-lived by design. The app automatically retries a failed request once after refreshing the token — if it still fails after that, the refresh token itself has likely expired or been revoked, and signing in again resolves it.",
  },
  {
    problem: "The widget looks wrong on mobile or overlaps other elements",
    fix: "Check the widget's position setting against anything else fixed to that same corner of the screen — a cookie-consent banner or sticky mobile nav is the most common culprit. The position setting and custom CSS field can both be used to nudge it clear.",
  },
];

export default function DocsTroubleshootingPage() {
  return (
    <>
      <PageHeader eyebrow="Documentation" title="Troubleshooting" description="Fixes for the issues that come up most." />
      <DocsShell active="/docs/troubleshooting">
        <div className="space-y-5">
          {issues.map((issue) => (
            <div key={issue.problem} className="rounded-2xl border border-border bg-surface p-6">
              <h2 className="font-display text-base font-semibold">{issue.problem}</h2>
              <p className="mt-2 text-sm text-muted-foreground">{issue.fix}</p>
            </div>
          ))}
        </div>
      </DocsShell>
    </>
  );
}
