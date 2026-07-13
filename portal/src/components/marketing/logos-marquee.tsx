const platforms = ["WordPress", "Shopify", "Webflow", "Squarespace", "Wix", "Custom HTML", "Zapier", "Slack"];

export function LogosMarquee() {
  return (
    <section className="border-y border-border bg-surface-2 py-10">
      <div className="container-page">
        <p className="text-center text-xs font-semibold uppercase tracking-widest text-muted-foreground">
          Works everywhere your customers already are
        </p>
        <div className="relative mt-6 overflow-hidden bg-radial-fade">
          <div className="flex w-max animate-marquee gap-12">
            {[...platforms, ...platforms].map((platform, index) => (
              <span key={`${platform}-${index}`} className="font-display text-lg font-semibold text-muted-foreground/70">
                {platform}
              </span>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
