import { Globe, Hash, MessageCircle, ShoppingBag, Webhook, Zap } from "lucide-react";
import { Reveal } from "@/components/marketing/reveal";

const integrations = [
  { icon: Globe, name: "WordPress", description: "Drop-in plugin or script tag" },
  { icon: ShoppingBag, name: "Shopify", description: "Embed on your storefront" },
  { icon: MessageCircle, name: "Webflow", description: "One custom code block" },
  { icon: Hash, name: "Slack", description: "Route leads to a channel" },
  { icon: Zap, name: "Zapier", description: "Connect to 6,000+ apps" },
  { icon: Webhook, name: "Webhooks", description: "Build your own integration" },
];

export function IntegrationsSection() {
  return (
    <section id="integrations" className="container-page py-24">
      <Reveal className="mx-auto max-w-2xl text-center">
        <p className="text-sm font-semibold uppercase tracking-widest text-primary">Integrations</p>
        <h2 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">
          Plug into the tools you already run on
        </h2>
      </Reveal>

      <div className="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {integrations.map((integration, index) => (
          <Reveal key={integration.name} delay={index * 0.05}>
            <div className="flex items-center gap-4 rounded-2xl border border-border bg-surface p-5 transition-colors hover:border-border-strong">
              <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <integration.icon className="size-5" />
              </span>
              <div>
                <p className="font-display text-sm font-semibold">{integration.name}</p>
                <p className="text-xs text-muted-foreground">{integration.description}</p>
              </div>
            </div>
          </Reveal>
        ))}
      </div>
    </section>
  );
}
