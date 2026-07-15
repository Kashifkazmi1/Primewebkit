const platforms = ["WordPress", "Shopify", "Webflow", "Squarespace", "Wix", "Custom HTML", "Zapier", "Slack"];

export function LogosMarquee() {
  return (
    <section className="border-y border-border bg-surface-2 py-10">
      <div className="container-page">
        <p className="text-center text-xs font-semibold uppercase tracking-widest text-muted-foreground">
          Works everywhere your customers already are
        </p>
        <div className="relative mt-6 overflow-hidden mask-fade-x">
          <div className="flex w-max animate-marquee items-center gap-12">
            {[...platforms, ...platforms].map((platform, index) => (
              <span
                key={`${platform}-${index}`}
                className="shrink-0 font-display text-lg font-semibold whitespace-nowrap text-muted-foreground/70"
              >
                {platform}
              </span>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
