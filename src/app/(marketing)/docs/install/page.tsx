import type { Metadata } from "next";
import Link from "next/link";
import { DocsShell } from "@/components/docs/docs-shell";
import { CodeBlock } from "@/components/marketing/code-block";
import { PageHeader } from "@/components/marketing/page-header";
import { env } from "@/lib/env";

export const metadata: Metadata = {
  title: "Install the widget",
  description: "Embed a PrimeWebKit chatbot on any website — plain HTML, WordPress, Shopify, or Google Tag Manager.",
  alternates: { canonical: "/docs/install" },
};

const snippet = `<script src="${env.widgetJsUrl}" data-bot-id="<BOT_UUID>" async></script>`;

const platforms = [
  {
    title: "Plain HTML",
    steps: ["Open your site's shared template or layout file.", "Paste the snippet immediately before the closing </body> tag.", "Save and deploy — no build step is required for the widget itself."],
  },
  {
    title: "WordPress",
    steps: [
      "Go to Appearance → Theme File Editor and open footer.php (or use a header/footer scripts plugin).",
      "Paste the snippet just above the closing </body> tag.",
      "Update the file — the widget appears on every page rendered from that theme.",
    ],
  },
  {
    title: "Shopify",
    steps: [
      "In your Shopify admin, go to Online Store → Themes → Edit code.",
      "Open theme.liquid and paste the snippet immediately before the closing </body> tag near the bottom of the file.",
      "Save — the widget now appears across your storefront.",
    ],
  },
  {
    title: "Google Tag Manager",
    steps: [
      "Create a new tag of type Custom HTML.",
      "Paste the snippet as the tag's HTML content.",
      "Set the trigger to All Pages, then submit and publish the container.",
    ],
  },
];

export default function DocsInstallPage() {
  return (
    <>
      <PageHeader eyebrow="Documentation" title="Install the widget" description="One script tag, pasted once, on any platform." />
      <DocsShell active="/docs/install">
        <div className="space-y-4">
          <h2 className="font-display text-xl font-semibold">The embed snippet</h2>
          <p className="text-sm text-muted-foreground">
            Every bot has its own copy of this snippet, with its real ID filled in, on the Widget tab of that bot&apos;s
            dashboard page. Replace <code>&lt;BOT_UUID&gt;</code> below with yours, or copy it directly from the dashboard.
          </p>
          <CodeBlock code={snippet} />
        </div>

        <div className="space-y-8">
          <h2 className="font-display text-xl font-semibold">Platform guides</h2>
          {platforms.map((platform) => (
            <div key={platform.title} className="rounded-2xl border border-border bg-surface p-6">
              <h3 className="font-display text-base font-semibold">{platform.title}</h3>
              <ol className="mt-3 space-y-2 text-sm text-muted-foreground">
                {platform.steps.map((step, i) => (
                  <li key={step} className="flex gap-2.5">
                    <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-[10px] font-semibold text-foreground">
                      {i + 1}
                    </span>
                    {step}
                  </li>
                ))}
              </ol>
            </div>
          ))}
        </div>

        <div className="rounded-2xl border border-dashed border-border p-6 text-sm text-muted-foreground">
          Want to restrict the widget to only your own domains, or match it to your brand&apos;s colors? See the{" "}
          <Link href="/add-ai-chatbot-to-website" className="font-medium text-primary hover:underline">
            full embedding guide
          </Link>
          , or configure allowed domains and appearance directly on the bot&apos;s Widget tab.
        </div>
      </DocsShell>
    </>
  );
}
